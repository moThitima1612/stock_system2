<?php
/**
 * แกนกลางของระบบสต๊อก
 * ทุกการเปลี่ยนแปลงยอดคงเหลือต้องผ่าน post_document() / void_document() เท่านั้น
 * เพื่อให้ stock_balances กับ stock_movements ตรงกันเสมอ
 */

const DOC_PREFIX = [
    'IN'     => 'RC',
    'OUT'    => 'IS',
    'ADJUST' => 'AJ',
    'PROD'   => 'PD',
];

/** ยอดคงเหลือของสินค้าในคลังหนึ่ง */
function get_balance(int $productId, int $warehouseId): float
{
    return (float)qv(
        'SELECT qty FROM stock_balances WHERE product_id = ? AND warehouse_id = ?',
        [$productId, $warehouseId],
        0
    );
}

/** ยอดคงเหลือรวมทุกคลัง */
function get_total_balance(int $productId): float
{
    return (float)qv('SELECT COALESCE(SUM(qty),0) FROM stock_balances WHERE product_id = ?', [$productId], 0);
}

/** ออกเลขที่เอกสารถัดไป เช่น RC-202608-0001 (ต้องเรียกภายใน transaction) */
function next_doc_no(string $docType): string
{
    $prefix = DOC_PREFIX[$docType] ?? 'DC';
    $period = date('Ym');

    $row = q1('SELECT last_no FROM doc_counters WHERE prefix = ? AND period = ? FOR UPDATE', [$prefix, $period]);
    if ($row) {
        $no = (int)$row['last_no'] + 1;
        q('UPDATE doc_counters SET last_no = ? WHERE prefix = ? AND period = ?', [$no, $prefix, $period]);
    } else {
        $no = 1;
        q('INSERT INTO doc_counters (prefix, period, last_no) VALUES (?, ?, 1)', [$prefix, $period]);
    }
    return sprintf('%s-%s-%04d', $prefix, $period, $no);
}

/**
 * เขียนการเคลื่อนไหว 1 บรรทัด + อัปเดตยอดคงเหลือ (ล็อกแถวกันชนกัน)
 * ต้องเรียกภายใน transaction เท่านั้น
 */
function apply_movement(
    ?int $docId,
    string $docNo,
    string $docType,
    int $productId,
    int $warehouseId,
    float $delta,
    float $unitCost = 0,
    ?string $note = null
): float {
    // สร้างแถวยอดคงเหลือถ้ายังไม่มี แล้วล็อกไว้
    q('INSERT IGNORE INTO stock_balances (product_id, warehouse_id, qty) VALUES (?, ?, 0)',
        [$productId, $warehouseId]);
    $cur = (float)qv(
        'SELECT qty FROM stock_balances WHERE product_id = ? AND warehouse_id = ? FOR UPDATE',
        [$productId, $warehouseId],
        0
    );

    $new = round($cur + $delta, 3);
    if ($new < 0 && !ALLOW_NEGATIVE_STOCK) {
        $p  = q1('SELECT sku, name FROM products WHERE id = ?', [$productId]);
        $wh = q1('SELECT name FROM warehouses WHERE id = ?', [$warehouseId]);
        throw new RuntimeException(sprintf(
            'สต๊อกไม่พอ: %s (%s) ที่ %s คงเหลือ %s แต่ต้องการตัด %s',
            $p['name'] ?? '#' . $productId,
            $p['sku'] ?? '-',
            $wh['name'] ?? '#' . $warehouseId,
            qty_fmt($cur),
            qty_fmt(abs($delta))
        ));
    }

    q('UPDATE stock_balances SET qty = ? WHERE product_id = ? AND warehouse_id = ?',
        [$new, $productId, $warehouseId]);

    q('INSERT INTO stock_movements
         (doc_id, doc_no, doc_type, product_id, warehouse_id, qty_change, balance_after, unit_cost, note, user_id, moved_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$docId, $docNo, $docType, $productId, $warehouseId, $delta, $new, $unitCost, $note, current_user()['id'] ?? null]);

    return $new;
}

/**
 * บันทึกเอกสารสต๊อกและตัด/เพิ่มยอดทันที
 *
 * @param array $h     doc_type, doc_date, warehouse_id, to_warehouse_id, supplier_id, ref_no, contact, note
 * @param array $items แต่ละรายการ: product_id, qty, unit_cost, note
 *                     - IN/OUT:          qty = จำนวนที่เคลื่อนไหว (ต้อง > 0)
 *                     - ADJUST:          qty = ยอดที่นับได้จริง (ระบบคำนวณผลต่างให้)
 * @return int         id ของเอกสาร
 * @throws RuntimeException
 */
function post_document(array $h, array $items): int
{
    $type = $h['doc_type'] ?? '';
    if (!isset(DOC_PREFIX[$type])) {
        throw new RuntimeException('ชนิดเอกสารไม่ถูกต้อง');
    }

    $whId   = (int)($h['warehouse_id'] ?? 0);
    $toWhId = null;   // เลิกใช้แล้ว (ถอดฟีเจอร์โอนย้ายออก) — คงคอลัมน์ไว้เพื่อความเข้ากันได้ของข้อมูลเดิม
    if ($whId <= 0) {
        throw new RuntimeException('กรุณาเลือกคลังสินค้า');
    }

    // รวมรายการซ้ำสินค้าเดียวกัน และคัดรายการว่างทิ้ง
    $clean = [];
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $qty = round((float)($it['qty'] ?? 0), 3);
        if ($type !== 'ADJUST' && $qty <= 0) {
            continue;
        }
        if ($qty < 0) {
            throw new RuntimeException('จำนวนต้องไม่ติดลบ');
        }
        if (isset($clean[$pid]) && $type !== 'ADJUST') {
            $clean[$pid]['qty'] += $qty;
        } else {
            $clean[$pid] = [
                'product_id' => $pid,
                'qty'        => $qty,
                'unit_cost'  => round((float)($it['unit_cost'] ?? 0), 2),
                'note'       => $it['note'] ?? null,
            ];
        }
    }
    if (!$clean) {
        throw new RuntimeException($type === 'PROD'
            ? 'กรุณาระบุสินค้าที่ผลิตได้อย่างน้อย 1 รายการ'
            : 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ');
    }

    // วัตถุดิบที่ใช้ไป (เฉพาะใบผลิต)
    $consume = [];
    if ($type === 'PROD') {
        foreach ((array)($h['consume'] ?? []) as $c) {
            $pid = (int)($c['product_id'] ?? 0);
            $qty = round((float)($c['qty'] ?? 0), 3);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            if (isset($consume[$pid])) {
                $consume[$pid]['qty'] += $qty;
            } else {
                $consume[$pid] = [
                    'product_id' => $pid,
                    'qty'        => $qty,
                    'unit_cost'  => round((float)($c['unit_cost'] ?? 0), 2),
                    'note'       => $c['note'] ?? null,
                ];
            }
        }
        if (!$consume) {
            throw new RuntimeException('กรุณาระบุวัตถุดิบที่ใช้อย่างน้อย 1 รายการ '
                . '(กดปุ่ม "ดึงสูตรการผลิต" เพื่อให้ระบบเติมให้อัตโนมัติ)');
        }
        foreach ($clean as $pid => $_) {
            if (isset($consume[$pid])) {
                throw new RuntimeException('สินค้ารายการเดียวกันเป็นทั้งผลผลิตและวัตถุดิบไม่ได้');
            }
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $docNo   = next_doc_no($type);
        $docDate = $h['doc_date'] ?? date('Y-m-d');

        q('INSERT INTO stock_docs
             (doc_no, doc_type, doc_date, warehouse_id, to_warehouse_id, supplier_id, ref_no,
              contact, ship_to, ship_tel, ship_date, note, status, user_id)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'posted\', ?)',
            [
                $docNo, $type, $docDate, $whId, $toWhId,
                ($h['supplier_id'] ?? null) ?: null,
                ($h['ref_no'] ?? null) ?: null,
                ($h['contact'] ?? null) ?: null,
                ($h['ship_to'] ?? null) ?: null,
                ($h['ship_tel'] ?? null) ?: null,
                ($h['ship_date'] ?? null) ?: null,
                ($h['note'] ?? null) ?: null,
                current_user()['id'] ?? null,
            ]);
        $docId = (int)$pdo->lastInsertId();

        $totalQty = 0.0;
        $totalAmt = 0.0;

        /* ---- ใบผลิต: ตัดวัตถุดิบก่อน แล้วจึงรับสินค้าสำเร็จรูปเข้าคลัง ---- */
        if ($type === 'PROD') {
            $matCost = 0.0;
            foreach ($consume as $c) {
                apply_movement($docId, $docNo, 'PROD', $c['product_id'], $whId,
                    -$c['qty'], $c['unit_cost'], 'ใช้ในการผลิต');
                q('INSERT INTO stock_doc_items (doc_id, line_kind, product_id, qty, unit_cost, note)
                   VALUES (?, \'CONSUME\', ?, ?, ?, ?)',
                    [$docId, $c['product_id'], $c['qty'], $c['unit_cost'], $c['note']]);
                $matCost += $c['qty'] * $c['unit_cost'];
            }

            $outQty = 0.0;
            foreach ($clean as $it) {
                $outQty += $it['qty'];
            }

            foreach ($clean as $it) {
                // ถ้าไม่ระบุต้นทุน ให้ถัวเฉลี่ยจากมูลค่าวัตถุดิบที่ใช้ไป
                $cost = $it['unit_cost'] > 0
                    ? $it['unit_cost']
                    : ($outQty > 0 ? round($matCost / $outQty, 2) : 0.0);

                apply_movement($docId, $docNo, 'PROD', $it['product_id'], $whId,
                    $it['qty'], $cost, 'ผลิตเข้าคลัง');
                q('INSERT INTO stock_doc_items (doc_id, line_kind, product_id, qty, unit_cost, note)
                   VALUES (?, \'MAIN\', ?, ?, ?, ?)',
                    [$docId, $it['product_id'], $it['qty'], $cost, $it['note']]);
                if ($cost > 0) {
                    q('UPDATE products SET cost_price = ? WHERE id = ?', [$cost, $it['product_id']]);
                }
                $totalQty += $it['qty'];
                $totalAmt += $it['qty'] * $cost;
            }
        }
        else foreach ($clean as $it) {
            $pid  = $it['product_id'];
            $qty  = $it['qty'];
            $cost = $it['unit_cost'];

            if ($type === 'ADJUST') {
                $cur   = get_balance($pid, $whId);
                $delta = round($qty - $cur, 3);
                if (abs($delta) < 0.0005) {
                    continue;   // ยอดตรงอยู่แล้ว ไม่ต้องบันทึกการเคลื่อนไหว
                }
                $note = sprintf('ปรับยอดจาก %s เป็น %s', qty_fmt($cur), qty_fmt($qty));
                apply_movement($docId, $docNo, 'ADJUST', $pid, $whId, $delta, $cost, $note);
                $totalQty += $delta;
                $totalAmt += $delta * $cost;
            } elseif ($type === 'IN') {
                apply_movement($docId, $docNo, 'IN', $pid, $whId, $qty, $cost, $it['note']);
                $totalQty += $qty;
                $totalAmt += $qty * $cost;
            } else { // OUT
                apply_movement($docId, $docNo, 'OUT', $pid, $whId, -$qty, $cost, $it['note']);
                $totalQty += $qty;
                $totalAmt += $qty * $cost;
            }

            q('INSERT INTO stock_doc_items (doc_id, line_kind, product_id, qty, unit_cost, note)
               VALUES (?, \'MAIN\', ?, ?, ?, ?)',
                [$docId, $pid, $qty, $cost, $it['note']]);

            // อัปเดตต้นทุนล่าสุดของสินค้าเมื่อรับเข้าและมีการระบุราคา
            if ($type === 'IN' && $cost > 0) {
                q('UPDATE products SET cost_price = ? WHERE id = ?', [$cost, $pid]);
            }
        }

        q('UPDATE stock_docs SET total_qty = ?, total_amount = ? WHERE id = ?',
            [round($totalQty, 3), round($totalAmt, 2), $docId]);

        $pdo->commit();
        return $docId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** ยกเลิกเอกสาร: กลับรายการเคลื่อนไหวทั้งหมดของเอกสารนั้น */
function void_document(int $docId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $doc = q1('SELECT * FROM stock_docs WHERE id = ? FOR UPDATE', [$docId]);
        if (!$doc) {
            throw new RuntimeException('ไม่พบเอกสาร');
        }
        if ($doc['status'] === 'void') {
            throw new RuntimeException('เอกสารนี้ถูกยกเลิกไปแล้ว');
        }

        $moves = qa('SELECT * FROM stock_movements WHERE doc_id = ? AND doc_type <> \'VOID\' ORDER BY id DESC', [$docId]);
        foreach ($moves as $m) {
            apply_movement(
                $docId,
                $doc['doc_no'],
                'VOID',
                (int)$m['product_id'],
                (int)$m['warehouse_id'],
                -1 * (float)$m['qty_change'],
                (float)$m['unit_cost'],
                'ยกเลิกเอกสาร ' . $doc['doc_no']
            );
        }

        q('UPDATE stock_docs SET status = \'void\', voided_at = NOW(), voided_by = ? WHERE id = ?',
            [current_user()['id'] ?? null, $docId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** ---------- ตัวช่วยดึงข้อมูลสำหรับหน้าจอ ---------- */

function all_warehouses(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM warehouses' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY code';
    return qa($sql);
}

function all_categories(): array
{
    return qa('SELECT * FROM categories ORDER BY name');
}

function all_units(): array
{
    return qa('SELECT * FROM units ORDER BY name');
}

function all_suppliers(): array
{
    return qa('SELECT * FROM suppliers ORDER BY name');
}

/**
 * สินค้าพร้อมยอดคงเหลือรวม (ใช้กับ dropdown ในฟอร์มเอกสาร)
 * @param string[]|null $types กรองเฉพาะประเภท เช่น ['MAT','PACK'] — null = ทุกประเภท
 */
function product_options(?array $types = null): array
{
    $where = 'p.is_active = 1';
    $args  = [];
    if ($types) {
        $where .= ' AND p.product_type IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
        $args   = $types;
    }
    return qa(
        "SELECT p.id, p.sku, p.name, p.product_type, p.cost_price, u.name AS unit_name,
                COALESCE((SELECT SUM(qty) FROM stock_balances WHERE product_id = p.id), 0) AS qty
           FROM products p
           LEFT JOIN units u ON u.id = p.unit_id
          WHERE $where
          ORDER BY FIELD(p.product_type,'MAT','PACK','WIP','FG','OTHER'), p.sku",
        $args
    );
}

/** ---------- สูตรการผลิต (BOM) ---------- */

/** วัตถุดิบทั้งหมดของสินค้าสำเร็จรูป 1 ตัว (ต่อการผลิต 1 หน่วย) */
function get_bom(int $fgProductId): array
{
    return qa(
        'SELECT b.*, p.sku, p.name, p.product_type, p.cost_price, u.name AS unit_name
           FROM bom_items b
           JOIN products p ON p.id = b.material_id
           LEFT JOIN units u ON u.id = p.unit_id
          WHERE b.fg_product_id = ?
          ORDER BY p.product_type, p.sku',
        [$fgProductId]
    );
}

/** ต้นทุนวัตถุดิบรวมต่อการผลิต 1 หน่วย */
function bom_unit_cost(int $fgProductId): float
{
    return (float)qv(
        'SELECT COALESCE(SUM(b.qty * p.cost_price), 0)
           FROM bom_items b JOIN products p ON p.id = b.material_id
          WHERE b.fg_product_id = ?',
        [$fgProductId],
        0
    );
}

/** สินค้าที่มีสูตรการผลิตแล้ว (ใช้ทำ dropdown ในหน้าผลิต) */
function products_with_bom(): array
{
    return qa(
        'SELECT p.id, p.sku, p.name, p.cost_price, u.name AS unit_name,
                COUNT(b.id) AS n_materials
           FROM products p
           JOIN bom_items b ON b.fg_product_id = p.id
           LEFT JOIN units u ON u.id = p.unit_id
          WHERE p.is_active = 1
          GROUP BY p.id, p.sku, p.name, p.cost_price, u.name
          ORDER BY p.sku'
    );
}

/** สรุปจำนวน/มูลค่าคงเหลือแยกตามประเภทสินค้า */
function stock_by_type(?int $warehouseId = null): array
{
    $join = $warehouseId
        ? 'LEFT JOIN stock_balances b ON b.product_id = p.id AND b.warehouse_id = ' . (int)$warehouseId
        : 'LEFT JOIN stock_balances b ON b.product_id = p.id';
    $rows = qa(
        "SELECT p.product_type,
                COUNT(DISTINCT p.id) AS n_products,
                COALESCE(SUM(b.qty), 0) AS qty,
                COALESCE(SUM(b.qty * p.cost_price), 0) AS value
           FROM products p
           $join
          WHERE p.is_active = 1
          GROUP BY p.product_type"
    );
    $out = [];
    foreach ($rows as $r) {
        $out[$r['product_type']] = $r;
    }
    return $out;
}
