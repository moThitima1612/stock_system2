<?php
/**
 * ชุดข้อมูลตั้งต้นสำหรับ "โรงพิมพ์" — เรียกจาก install.php
 * MAT = กระดาษ / หมึก CMYK / เพลท CTP / เคมี / ฟิล์มเคลือบ
 * FG  = งานพิมพ์สำเร็จ พร้อมสูตรการผลิต (BOM) ต่อ 1 หน่วย
 */

function seed_printing_data(PDO $pdo): void
{
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
    foreach (['แผ่น', 'รีม', 'กก.', 'ลิตร', 'ม้วน', 'เพลท', 'ตร.ม.', 'เล่ม', 'ใบ', 'ชุด', 'กล่อง'] as $u) {
        $unit[$u] = $ins('INSERT INTO units (name) VALUES (?)', [$u]);
    }

    /* ---------- หมวดหมู่ ---------- */
    $cat = [];
    foreach ([
        'กระดาษ'            => 'กระดาษพิมพ์ทุกชนิด',
        'หมึกพิมพ์'          => 'หมึกออฟเซ็ต CMYK และหมึกพิเศษ',
        'เพลท & เคมีภัณฑ์'   => 'เพลท CTP น้ำยาฟาวน์เทน น้ำยาล้าง',
        'วัสดุหลังพิมพ์'      => 'ฟิล์มเคลือบ กาวไสสัน ลวดเย็บ',
        'บรรจุภัณฑ์'         => 'กล่อง ฟิล์มพันงาน',
        'งานพิมพ์สำเร็จ'      => 'งานที่ผลิตเสร็จพร้อมส่งลูกค้า',
    ] as $c => $note) {
        $cat[$c] = $ins('INSERT INTO categories (name, note) VALUES (?,?)', [$c, $note]);
    }

    /* ---------- คลัง (คลังเดียว — ไม่ใช้ระบบโอนย้าย) ---------- */
    $whId = $ins('INSERT INTO warehouses (code, name, address) VALUES (?,?,?)',
        ['WH01', 'คลังโรงพิมพ์', 'อาคารโรงพิมพ์ ชั้น 1']);

    /* ---------- ผู้ขาย ---------- */
    $sup = [];
    foreach ([
        ['SUP-PPR', 'บจก. ไทยเปเปอร์ ซัพพลาย',   '02-345-6789', 'sales@thaipaper.example'],
        ['SUP-INK', 'บจก. สยามอิงค์ ออฟเซ็ต',    '02-987-6543', 'order@siamink.example'],
        ['SUP-CTP', 'ร้าน ซี.ที.พี. เซอร์วิส',     '081-222-3344', null],
        ['SUP-LAM', 'บจก. ไทยลามิเนตฟิล์ม',       '034-555-100', null],
    ] as $s) {
        $sup[$s[0]] = $ins('INSERT INTO suppliers (code, name, phone, email) VALUES (?,?,?,?)', $s);
    }

    /* ---------- สินค้า ----------
       [sku, ชื่อ, ประเภท, หมวดหมู่, หน่วย, ทุน, ขาย, จุดสั่งซื้อ, ยอดยกมา] */
    $products = [
        // ---- กระดาษ (MAT) ----
        ['PPR-ART128',  'กระดาษอาร์ตมัน 128 แกรม 31×43 นิ้ว',      'MAT', 'กระดาษ', 'แผ่น',  4.20,   0, 2000,  8500],
        ['PPR-ART157',  'กระดาษอาร์ตมัน 157 แกรม 31×43 นิ้ว',      'MAT', 'กระดาษ', 'แผ่น',  5.10,   0, 2000,  6200],
        ['PPR-ART210',  'กระดาษอาร์ตการ์ด 210 แกรม 31×43 นิ้ว',    'MAT', 'กระดาษ', 'แผ่น',  7.30,   0, 1500,  3100],
        ['PPR-BOND80',  'กระดาษปอนด์ 80 แกรม 24×35 นิ้ว',          'MAT', 'กระดาษ', 'แผ่น',  1.85,   0, 3000, 12000],
        ['PPR-BOX350',  'กระดาษกล่องแป้งหลังขาว 350 แกรม 31×43',   'MAT', 'กระดาษ', 'แผ่น',  9.80,   0, 1000,   900],
        ['PPR-STICKER', 'สติกเกอร์ PVC ขาวเงา A3',                 'MAT', 'กระดาษ', 'แผ่น',  6.50,   0,  500,  1400],
        // ---- หมึก (MAT) ----
        ['INK-K', 'หมึกออฟเซ็ต ดำ (K)',        'MAT', 'หมึกพิมพ์', 'กก.', 320.00, 0, 10, 24],
        ['INK-C', 'หมึกออฟเซ็ต ฟ้า (C)',       'MAT', 'หมึกพิมพ์', 'กก.', 380.00, 0,  8, 15],
        ['INK-M', 'หมึกออฟเซ็ต ม่วงแดง (M)',   'MAT', 'หมึกพิมพ์', 'กก.', 380.00, 0,  8,  6],
        ['INK-Y', 'หมึกออฟเซ็ต เหลือง (Y)',    'MAT', 'หมึกพิมพ์', 'กก.', 360.00, 0,  8, 11],
        // ---- เพลท & เคมี (MAT) ----
        ['PLT-CTP',   'เพลท CTP 4 หน้ายก',   'MAT', 'เพลท & เคมีภัณฑ์', 'เพลท',  145.00, 0, 40, 120],
        ['CHM-FOUNT', 'น้ำยาฟาวน์เทน',        'MAT', 'เพลท & เคมีภัณฑ์', 'ลิตร',  210.00, 0, 20,  45],
        ['CHM-WASH',  'น้ำยาล้างลูกกลิ้ง',     'MAT', 'เพลท & เคมีภัณฑ์', 'ลิตร',  180.00, 0, 10,  18],
        // ---- หลังพิมพ์ (MAT) ----
        ['LAM-MATT',  'ฟิล์มเคลือบด้าน',       'MAT', 'วัสดุหลังพิมพ์', 'ตร.ม.',   3.80, 0, 300, 850],
        ['LAM-GLOSS', 'ฟิล์มเคลือบเงา',        'MAT', 'วัสดุหลังพิมพ์', 'ตร.ม.',   3.50, 0, 300, 640],
        ['GLU-BIND',  'กาวไสสันหลอมร้อน',      'MAT', 'วัสดุหลังพิมพ์', 'กก.',   165.00, 0,  15,  32],
        ['WIRE-STAP', 'ลวดเย็บมุงหลังคา',      'MAT', 'วัสดุหลังพิมพ์', 'กล่อง', 240.00, 0,   5,   9],
        // ---- บรรจุภัณฑ์ (PACK) ----
        ['PK-BOX',  'กล่องกระดาษบรรจุงานพิมพ์', 'PACK', 'บรรจุภัณฑ์', 'ใบ',   14.00, 0, 100, 260],
        ['PK-WRAP', 'ฟิล์มยืดพันพาเลท',         'PACK', 'บรรจุภัณฑ์', 'ม้วน', 320.00, 0,   5,  12],
        // ---- งานพิมพ์สำเร็จ (FG) — เริ่มที่ 0 แล้วเปิดใบผลิต ----
        ['FG-NAMECARD',   'นามบัตร 4 สี 2 หน้า เคลือบด้าน (100 ใบ/ชุด)', 'FG', 'งานพิมพ์สำเร็จ', 'ชุด',   0, 180.00, 5, 0],
        ['FG-FLYER-A5',   'ใบปลิว A5 4 สี 1 หน้า',                      'FG', 'งานพิมพ์สำเร็จ', 'ใบ',    0,   1.20, 0, 0],
        ['FG-BROCHURE',   'โบรชัวร์ A4 พับ 3 ตอน 4 สี 2 หน้า',           'FG', 'งานพิมพ์สำเร็จ', 'ใบ',    0,   6.50, 0, 0],
        ['FG-POSTER-A2',  'โปสเตอร์ A2 4 สี เคลือบเงา',                  'FG', 'งานพิมพ์สำเร็จ', 'แผ่น',  0,  45.00, 0, 0],
        ['FG-BOOK-A5',    'หนังสือ A5 100 หน้า ไสกาว ปกอาร์ตการ์ด',      'FG', 'งานพิมพ์สำเร็จ', 'เล่ม',  0,  95.00, 0, 0],
        ['FG-BOX-PRINT',  'กล่องบรรจุภัณฑ์พิมพ์ 4 สี',                   'FG', 'งานพิมพ์สำเร็จ', 'ใบ',    0,  12.00, 0, 0],
        ['FG-STICKER-A3', 'สติกเกอร์ไดคัท A3 4 สี',                      'FG', 'งานพิมพ์สำเร็จ', 'แผ่น',  0,  28.00, 0, 0],
    ];

    $id = [];
    $opening = [];
    foreach ($products as $p) {
        $pid = $ins(
            'INSERT INTO products (sku, name, product_type, category_id, unit_id, cost_price, sell_price, min_stock)
             VALUES (?,?,?,?,?,?,?,?)',
            [$p[0], $p[1], $p[2], $cat[$p[3]], $unit[$p[4]], $p[5], $p[6], $p[7]]
        );
        $id[$p[0]] = $pid;
        if ($p[8] > 0) {
            $opening[] = ['id' => $pid, 'qty' => $p[8], 'cost' => $p[5]];
        }
    }

    /* ---------- สูตรการผลิต (BOM) ต่อ 1 หน่วย ---------- */
    $boms = [
        'FG-NAMECARD' => [
            ['PPR-ART210', 1], ['INK-K', 0.004], ['INK-C', 0.004], ['INK-M', 0.004], ['INK-Y', 0.004],
            ['PLT-CTP', 0.04], ['LAM-MATT', 0.9],
        ],
        'FG-FLYER-A5' => [
            ['PPR-ART128', 0.125], ['INK-K', 0.0001], ['INK-C', 0.0001], ['INK-M', 0.0001], ['INK-Y', 0.0001],
            ['PLT-CTP', 0.0005],
        ],
        'FG-BROCHURE' => [
            ['PPR-ART157', 0.25], ['INK-K', 0.0003], ['INK-C', 0.0003], ['INK-M', 0.0003], ['INK-Y', 0.0003],
            ['PLT-CTP', 0.002], ['LAM-MATT', 0.13],
        ],
        'FG-POSTER-A2' => [
            ['PPR-ART157', 0.5], ['INK-K', 0.0015], ['INK-C', 0.0015], ['INK-M', 0.0015], ['INK-Y', 0.0015],
            ['PLT-CTP', 0.004], ['LAM-GLOSS', 0.26],
        ],
        'FG-BOOK-A5' => [
            ['PPR-BOND80', 6.25], ['PPR-ART210', 0.25], ['INK-K', 0.02],
            ['INK-C', 0.002], ['INK-M', 0.002], ['INK-Y', 0.002],
            ['PLT-CTP', 0.05], ['GLU-BIND', 0.012], ['LAM-MATT', 0.15],
        ],
        'FG-BOX-PRINT' => [
            ['PPR-BOX350', 0.25], ['INK-K', 0.0008], ['INK-C', 0.0008], ['INK-M', 0.0008], ['INK-Y', 0.0008],
            ['PLT-CTP', 0.002], ['LAM-GLOSS', 0.12],
        ],
        'FG-STICKER-A3' => [
            ['PPR-STICKER', 1], ['INK-K', 0.0006], ['INK-C', 0.0006], ['INK-M', 0.0006], ['INK-Y', 0.0006],
            ['PLT-CTP', 0.001],
        ],
    ];
    foreach ($boms as $fg => $lines) {
        foreach ($lines as [$mat, $qty]) {
            if (isset($id[$fg], $id[$mat])) {
                $ins('INSERT IGNORE INTO bom_items (fg_product_id, material_id, qty) VALUES (?,?,?)',
                    [$id[$fg], $id[$mat], $qty]);
            }
        }
    }

    // ตั้งราคาทุนของ FG ตามสูตร (ต้นทุนวัตถุดิบรวมต่อหน่วย)
    foreach (array_keys($boms) as $fg) {
        $st = $pdo->prepare('SELECT COALESCE(SUM(b.qty * p.cost_price),0)
                               FROM bom_items b JOIN products p ON p.id = b.material_id
                              WHERE b.fg_product_id = ?');
        $st->execute([$id[$fg]]);
        $pdo->prepare('UPDATE products SET cost_price = ? WHERE id = ?')
            ->execute([round((float)$st->fetchColumn(), 2), $id[$fg]]);
    }

    /* ---------- เอกสารยอดยกมา (รับเข้า) ---------- */
    $period  = date('Ym');
    $docNo   = sprintf('RC-%s-0001', $period);
    $docDate = date('Y-m-d', strtotime('-20 days'));
    $docId   = $ins(
        'INSERT INTO stock_docs (doc_no, doc_type, doc_date, warehouse_id, supplier_id, ref_no, contact, note, status, user_id)
         VALUES (?, \'IN\', ?, ?, ?, ?, ?, ?, \'posted\', 1)',
        [$docNo, $docDate, $whId, $sup['SUP-PPR'], 'OPENING', 'ระบบ', 'ยอดยกมาเริ่มต้นระบบ']
    );

    $totQty = $totAmt = 0.0;
    foreach ($opening as $o) {
        $ins('INSERT INTO stock_doc_items (doc_id, line_kind, product_id, qty, unit_cost) VALUES (?,\'MAIN\',?,?,?)',
            [$docId, $o['id'], $o['qty'], $o['cost']]);
        $ins('INSERT INTO stock_balances (product_id, warehouse_id, qty) VALUES (?,?,?)
              ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)', [$o['id'], $whId, $o['qty']]);
        $ins('INSERT INTO stock_movements
                (doc_id, doc_no, doc_type, product_id, warehouse_id, qty_change, balance_after, unit_cost, note, user_id, moved_at)
              VALUES (?,?,\'IN\',?,?,?,?,?,?,1,?)',
            [$docId, $docNo, $o['id'], $whId, $o['qty'], $o['qty'], $o['cost'], 'ยอดยกมา', $docDate . ' 09:00:00']);
        $totQty += $o['qty'];
        $totAmt += $o['qty'] * $o['cost'];
    }
    $pdo->prepare('UPDATE stock_docs SET total_qty = ?, total_amount = ? WHERE id = ?')
        ->execute([$totQty, $totAmt, $docId]);

    $pdo->prepare('INSERT INTO doc_counters (prefix, period, last_no) VALUES (\'RC\', ?, 1)
                   ON DUPLICATE KEY UPDATE last_no = 1')->execute([$period]);
}
