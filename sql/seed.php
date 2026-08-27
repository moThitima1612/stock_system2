<?php
/**
 * ข้อมูลตัวอย่างสำหรับทดลองใช้งาน — เรียกจาก install.php
 * เขียนด้วย SQL ตรง ๆ เพื่อไม่ผูกกับ session/ผู้ใช้
 */

function seed_demo_data(PDO $pdo): void
{
    // ข้ามถ้ามีสินค้าอยู่แล้ว
    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
        return;
    }

    $ins = function (string $sql, array $args = []) use ($pdo) {
        $st = $pdo->prepare($sql);
        $st->execute($args);
        return (int)$pdo->lastInsertId();
    };

    /* ---------- หน่วยนับ ---------- */
    $unit = [];
    foreach (['ชิ้น', 'กล่อง', 'แพ็ค', 'ม้วน', 'กิโลกรัม', 'ลัง'] as $u) {
        $unit[$u] = $ins('INSERT INTO units (name) VALUES (?)', [$u]);
    }

    /* ---------- หมวดหมู่ ---------- */
    $cat = [];
    foreach ([
        'อุปกรณ์สำนักงาน' => 'เครื่องเขียนและของใช้ในออฟฟิศ',
        'อุปกรณ์ไอที'     => 'คอมพิวเตอร์และอุปกรณ์ต่อพ่วง',
        'วัสดุบรรจุภัณฑ์' => 'กล่อง เทป วัสดุกันกระแทก',
        'ของใช้ทั่วไป'    => null,
    ] as $c => $note) {
        $cat[$c] = $ins('INSERT INTO categories (name, note) VALUES (?, ?)', [$c, $note]);
    }

    /* ---------- คลังสินค้า ---------- */
    $wh = [];
    foreach ([
        ['WH01', 'คลังกลาง สำนักงานใหญ่', 'อาคาร A ชั้น 1'],
        ['WH02', 'คลังสาขาย่อย',          'อาคาร B ชั้น 2'],
    ] as $w) {
        $wh[$w[0]] = $ins('INSERT INTO warehouses (code, name, address) VALUES (?,?,?)', $w);
    }

    /* ---------- ผู้ขาย ---------- */
    $sup = [];
    foreach ([
        ['SUP01', 'บริษัท ออฟฟิศเมท ซัพพลาย จำกัด', '02-111-2233', 'sales@officemate.example'],
        ['SUP02', 'ร้านไอทีโซลูชั่น',                 '081-234-5678', 'contact@itsol.example'],
        ['SUP03', 'โรงงานกล่องกระดาษไทย',            '034-555-000',  null],
    ] as $s) {
        $sup[$s[0]] = $ins('INSERT INTO suppliers (code, name, phone, email) VALUES (?,?,?,?)', $s);
    }

    /* ---------- สินค้า ---------- */
    // [sku, ชื่อ, หมวดหมู่, หน่วย, ทุน, ขาย, จุดสั่งซื้อ, ยอดเริ่ม WH01, ยอดเริ่ม WH02, ประเภท]
    $products = [
        ['A4-80',    'กระดาษถ่ายเอกสาร A4 80 แกรม',      'อุปกรณ์สำนักงาน', 'ลัง',      520.00,  650.00, 10,  35,  8, 'MAT'],
        ['PEN-BL',   'ปากกาลูกลื่น น้ำเงิน 0.5',          'อุปกรณ์สำนักงาน', 'กล่อง',     85.00,  120.00, 20,  48, 12, 'MAT'],
        ['PEN-RD',   'ปากกาลูกลื่น แดง 0.5',              'อุปกรณ์สำนักงาน', 'กล่อง',     85.00,  120.00, 20,  15,  4, 'MAT'],
        ['STPL-10',  'ลวดเย็บกระดาษ เบอร์ 10',            'อุปกรณ์สำนักงาน', 'กล่อง',     18.00,   30.00, 30,  22,  0, 'MAT'],
        ['FILE-A4',  'แฟ้มสันกว้าง A4 สีน้ำเงิน',          'อุปกรณ์สำนักงาน', 'ชิ้น',      65.00,   95.00, 25,  60, 20, 'MAT'],
        ['MOUSE-W',  'เมาส์ไร้สาย 2.4GHz',                'อุปกรณ์ไอที',    'ชิ้น',     290.00,  450.00, 10,  18,  6, 'FG'],
        ['KB-USB',   'คีย์บอร์ด USB มาตรฐาน',              'อุปกรณ์ไอที',    'ชิ้น',     390.00,  590.00,  8,   9,  2, 'FG'],
        ['HDMI-2M',  'สาย HDMI 2 เมตร',                   'อุปกรณ์ไอที',    'เส้น',     150.00,  250.00, 15,   6,  3, 'FG'],
        ['USB-32G',  'แฟลชไดรฟ์ 32GB',                    'อุปกรณ์ไอที',    'ชิ้น',     185.00,  290.00, 20,  40, 10, 'FG'],
        ['BOX-S',    'กล่องลูกฟูก ขนาดเล็ก',               'วัสดุบรรจุภัณฑ์', 'ใบ',        12.00,   20.00, 100, 250, 80, 'PACK'],
        ['TAPE-OPP', 'เทปใส OPP 2 นิ้ว',                  'วัสดุบรรจุภัณฑ์', 'ม้วน',      22.00,   35.00, 50,  30, 15, 'PACK'],
        ['BUBBLE',   'พลาสติกกันกระแทก 65 ซม. x 100 ม.',  'วัสดุบรรจุภัณฑ์', 'ม้วน',     480.00,  700.00,  5,   3,  1, 'PACK'],
        // สินค้าสำเร็จรูปที่ผลิต/ประกอบเอง — เริ่มต้นยอดเป็น 0 แล้วค่อยเปิดใบผลิต
        ['SET-OFFICE', 'ชุดเครื่องเขียนพนักงานใหม่',       'ของใช้ทั่วไป',    'ชุด',      247.20,  390.00,  5,   0,  0, 'FG'],
        ['SET-GIFT',   'ชุดของขวัญลูกค้า',                 'ของใช้ทั่วไป',    'ชุด',      186.00,  320.00,  5,   0,  0, 'FG'],
    ];

    // เพิ่มหน่วยที่ยังไม่มี (เส้น, ใบ, ชุด)
    foreach (['เส้น', 'ใบ', 'ชุด'] as $u) {
        if (!isset($unit[$u])) {
            $unit[$u] = $ins('INSERT INTO units (name) VALUES (?)', [$u]);
        }
    }

    $rows = [];
    $bySku = [];
    foreach ($products as $p) {
        $pid = $ins(
            'INSERT INTO products (sku, name, product_type, category_id, unit_id, cost_price, sell_price, min_stock)
             VALUES (?,?,?,?,?,?,?,?)',
            [$p[0], $p[1], $p[9], $cat[$p[2]], $unit[$p[3]], $p[4], $p[5], $p[6]]
        );
        $rows[]        = ['id' => $pid, 'cost' => $p[4], 'q1' => $p[7], 'q2' => $p[8]];
        $bySku[$p[0]]  = $pid;
    }

    /* ---------- สูตรการผลิต (BOM) ---------- */
    $boms = [
        'SET-OFFICE' => [['PEN-BL', 1], ['FILE-A4', 2], ['STPL-10', 1], ['BOX-S', 1], ['TAPE-OPP', 0.1]],
        'SET-GIFT'   => [['PEN-RD', 1], ['FILE-A4', 1], ['BOX-S', 1], ['BUBBLE', 0.05]],
    ];
    foreach ($boms as $fgSku => $lines) {
        if (!isset($bySku[$fgSku])) {
            continue;
        }
        foreach ($lines as [$matSku, $qty]) {
            if (!isset($bySku[$matSku])) {
                continue;
            }
            $ins('INSERT IGNORE INTO bom_items (fg_product_id, material_id, qty) VALUES (?,?,?)',
                [$bySku[$fgSku], $bySku[$matSku], $qty]);
        }
    }

    /* ---------- เอกสารยอดยกมา (รับเข้า) แยกตามคลัง ---------- */
    $period = date('Ym');
    $seq    = 0;

    foreach ([['WH01', 'q1'], ['WH02', 'q2']] as [$code, $qk]) {
        $items = array_values(array_filter($rows, fn($r) => $r[$qk] > 0));
        if (!$items) {
            continue;
        }
        $seq++;
        $docNo  = sprintf('RC-%s-%04d', $period, $seq);
        $whId   = $wh[$code];
        $docDate = date('Y-m-d', strtotime('-14 days'));

        $docId = $ins(
            'INSERT INTO stock_docs (doc_no, doc_type, doc_date, warehouse_id, supplier_id, ref_no, contact, note, status, user_id)
             VALUES (?, \'IN\', ?, ?, ?, ?, ?, ?, \'posted\', 1)',
            [$docNo, $docDate, $whId, $sup['SUP01'], 'OPENING-' . $code, 'ระบบ', 'ยอดยกมาเริ่มต้นระบบ']
        );

        $totQty = 0.0;
        $totAmt = 0.0;
        foreach ($items as $r) {
            $qty = (float)$r[$qk];
            $ins('INSERT INTO stock_doc_items (doc_id, product_id, qty, unit_cost) VALUES (?,?,?,?)',
                [$docId, $r['id'], $qty, $r['cost']]);
            $ins('INSERT INTO stock_balances (product_id, warehouse_id, qty) VALUES (?,?,?)
                  ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)',
                [$r['id'], $whId, $qty]);
            $ins('INSERT INTO stock_movements
                    (doc_id, doc_no, doc_type, product_id, warehouse_id, qty_change, balance_after, unit_cost, note, user_id, moved_at)
                  VALUES (?,?,\'IN\',?,?,?,?,?,?,1,?)',
                [$docId, $docNo, $r['id'], $whId, $qty, $qty, $r['cost'], 'ยอดยกมา', $docDate . ' 09:00:00']);
            $totQty += $qty;
            $totAmt += $qty * $r['cost'];
        }
        $pdo->prepare('UPDATE stock_docs SET total_qty = ?, total_amount = ? WHERE id = ?')
            ->execute([$totQty, $totAmt, $docId]);
    }

    $pdo->prepare('INSERT INTO doc_counters (prefix, period, last_no) VALUES (\'RC\', ?, ?)
                   ON DUPLICATE KEY UPDATE last_no = VALUES(last_no)')
        ->execute([$period, $seq]);

    /* ---------- ตัวอย่างการเบิกออก 2 ใบ ---------- */
    $issueSeq = 0;
    foreach ([
        ['days' => 7, 'wh' => 'WH01', 'contact' => 'ฝ่ายบัญชี',    'items' => [['A4-80', 5], ['PEN-BL', 3]]],
        ['days' => 2, 'wh' => 'WH01', 'contact' => 'ฝ่ายการตลาด', 'items' => [['BOX-S', 40], ['TAPE-OPP', 6]]],
    ] as $doc) {
        $issueSeq++;
        $docNo   = sprintf('IS-%s-%04d', $period, $issueSeq);
        $whId    = $wh[$doc['wh']];
        $docDate = date('Y-m-d', strtotime('-' . $doc['days'] . ' days'));

        $docId = $ins(
            'INSERT INTO stock_docs (doc_no, doc_type, doc_date, warehouse_id, contact, note, status, user_id)
             VALUES (?, \'OUT\', ?, ?, ?, ?, \'posted\', 1)',
            [$docNo, $docDate, $whId, $doc['contact'], 'เบิกใช้ภายใน']
        );

        $totQty = 0.0;
        $totAmt = 0.0;
        foreach ($doc['items'] as [$sku, $qty]) {
            $st = $pdo->prepare('SELECT id, cost_price FROM products WHERE sku = ?');
            $st->execute([$sku]);
            $p = $st->fetch();
            if (!$p) { continue; }

            $st = $pdo->prepare('SELECT qty FROM stock_balances WHERE product_id = ? AND warehouse_id = ?');
            $st->execute([$p['id'], $whId]);
            $cur = (float)$st->fetchColumn();
            $new = $cur - $qty;
            if ($new < 0) { continue; }

            $ins('INSERT INTO stock_doc_items (doc_id, product_id, qty, unit_cost) VALUES (?,?,?,?)',
                [$docId, $p['id'], $qty, $p['cost_price']]);
            $pdo->prepare('UPDATE stock_balances SET qty = ? WHERE product_id = ? AND warehouse_id = ?')
                ->execute([$new, $p['id'], $whId]);
            $ins('INSERT INTO stock_movements
                    (doc_id, doc_no, doc_type, product_id, warehouse_id, qty_change, balance_after, unit_cost, note, user_id, moved_at)
                  VALUES (?,?,\'OUT\',?,?,?,?,?,?,1,?)',
                [$docId, $docNo, $p['id'], $whId, -$qty, $new, $p['cost_price'], $doc['contact'], $docDate . ' 14:30:00']);
            $totQty += $qty;
            $totAmt += $qty * (float)$p['cost_price'];
        }
        $pdo->prepare('UPDATE stock_docs SET total_qty = ?, total_amount = ? WHERE id = ?')
            ->execute([$totQty, $totAmt, $docId]);
    }

    $pdo->prepare('INSERT INTO doc_counters (prefix, period, last_no) VALUES (\'IS\', ?, ?)
                   ON DUPLICATE KEY UPDATE last_no = VALUES(last_no)')
        ->execute([$period, $issueSeq]);
}
