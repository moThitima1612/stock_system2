<?php
$CRUD = [
    'table'  => 'suppliers',
    'title'  => 'ผู้ขาย / ซัพพลายเออร์',
    'key'    => 'suppliers',
    'order'  => 'name',
    'fields' => [
        'code'    => ['label' => 'รหัสผู้ขาย'],
        'name'    => ['label' => 'ชื่อผู้ขาย', 'required' => true],
        'phone'   => ['label' => 'โทรศัพท์'],
        'email'   => ['label' => 'อีเมล', 'type' => 'email'],
        'address' => ['label' => 'ที่อยู่'],
    ],
    'columns' => [
        ['label' => 'ใบรับเข้า', 'value' => fn($r) => number_format(
            (int)qv('SELECT COUNT(*) FROM stock_docs WHERE supplier_id = ? AND status = \'posted\'', [$r['id']]))],
        ['label' => 'ยอดซื้อรวม', 'value' => fn($r) => money(
            qv('SELECT COALESCE(SUM(total_amount),0) FROM stock_docs
                 WHERE supplier_id = ? AND status = \'posted\'', [$r['id']], 0))],
    ],
];
require __DIR__ . '/_crud.php';
