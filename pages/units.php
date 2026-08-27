<?php
$CRUD = [
    'table'  => 'units',
    'title'  => 'หน่วยนับ',
    'key'    => 'units',
    'order'  => 'name',
    'fields' => [
        'name' => ['label' => 'ชื่อหน่วยนับ', 'required' => true, 'unique' => true,
                   'placeholder' => 'เช่น ชิ้น, กล่อง, กิโลกรัม'],
    ],
    'columns' => [
        ['label' => 'จำนวนสินค้า', 'value' => fn($r) => number_format(
            (int)qv('SELECT COUNT(*) FROM products WHERE unit_id = ?', [$r['id']]))],
    ],
];
require __DIR__ . '/_crud.php';
