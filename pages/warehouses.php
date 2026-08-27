<?php
$CRUD = [
    'table'  => 'warehouses',
    'title'  => 'คลังสินค้า',
    'key'    => 'warehouses',
    'order'  => 'code',
    'fields' => [
        'code'      => ['label' => 'รหัสคลัง', 'required' => true, 'unique' => true, 'placeholder' => 'เช่น WH01'],
        'name'      => ['label' => 'ชื่อคลัง', 'required' => true],
        'address'   => ['label' => 'ที่ตั้ง'],
        'is_active' => ['label' => 'เปิดใช้งาน', 'type' => 'checkbox', 'default' => 1],
    ],
    'columns' => [
        ['label' => 'จำนวนคงเหลือ', 'value' => fn($r) => qty_fmt(
            qv('SELECT COALESCE(SUM(qty),0) FROM stock_balances WHERE warehouse_id = ?', [$r['id']], 0))],
        ['label' => 'มูลค่า (บาท)', 'value' => fn($r) => money(
            qv('SELECT COALESCE(SUM(b.qty * p.cost_price),0) FROM stock_balances b
                  JOIN products p ON p.id = b.product_id WHERE b.warehouse_id = ?', [$r['id']], 0))],
    ],
    'guard' => function (int $id): ?string {
        $qty = (float)qv('SELECT COALESCE(SUM(ABS(qty)),0) FROM stock_balances WHERE warehouse_id = ?', [$id], 0);
        if ($qty > 0) {
            return 'ลบคลังนี้ไม่ได้ เพราะยังมีสินค้าคงเหลืออยู่ — ให้เบิกออกให้หมดก่อน หรือปิดใช้งานแทน';
        }
        if (qv('SELECT COUNT(*) FROM stock_docs WHERE warehouse_id = ? OR to_warehouse_id = ?', [$id, $id])) {
            return 'ลบคลังนี้ไม่ได้ เพราะมีเอกสารอ้างอิงอยู่ — แนะนำให้ปิดใช้งานแทน';
        }
        return null;
    },
];
require __DIR__ . '/_crud.php';
