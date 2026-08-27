<?php
$CRUD = [
    'table'  => 'categories',
    'title'  => 'หมวดหมู่สินค้า',
    'key'    => 'categories',
    'order'  => 'name',
    'fields' => [
        'name' => ['label' => 'ชื่อหมวดหมู่', 'required' => true, 'unique' => true],
        'note' => ['label' => 'หมายเหตุ'],
    ],
    'columns' => [
        ['label' => 'จำนวนสินค้า', 'value' => fn($r) => number_format(
            (int)qv('SELECT COUNT(*) FROM products WHERE category_id = ?', [$r['id']]))],
    ],
];
require __DIR__ . '/_crud.php';
