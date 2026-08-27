<?php
require_once __DIR__ . '/../includes/layout.php';

$whId = inp_int('wh');
$join = $whId
    ? 'LEFT JOIN stock_balances b ON b.product_id = p.id AND b.warehouse_id = ' . (int)$whId
    : 'LEFT JOIN stock_balances b ON b.product_id = p.id';

$rows = qa(
    "SELECT p.id, p.sku, p.name, p.min_stock, p.cost_price,
            c.name AS cat_name, un.name AS unit_name, s.name AS supp_name,
            COALESCE(SUM(b.qty), 0) AS qty
       FROM products p
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN units un ON un.id = p.unit_id
       LEFT JOIN (
            SELECT d.supplier_id, i.product_id, MAX(d.id) AS last_doc
              FROM stock_doc_items i JOIN stock_docs d ON d.id = i.doc_id
             WHERE d.doc_type = 'IN' AND d.supplier_id IS NOT NULL AND d.status = 'posted'
             GROUP BY d.supplier_id, i.product_id
       ) ls ON ls.product_id = p.id
       LEFT JOIN suppliers s ON s.id = ls.supplier_id
       $join
      WHERE p.is_active = 1
      GROUP BY p.id, p.sku, p.name, p.min_stock, p.cost_price, c.name, un.name, s.name
     HAVING qty <= p.min_stock
      ORDER BY (COALESCE(SUM(b.qty),0) - p.min_stock), p.sku"
);

$outOfStock = 0;
$needValue  = 0.0;
foreach ($rows as $r) {
    if ((float)$r['qty'] <= 0) { $outOfStock++; }
    $needValue += max(0, (float)$r['min_stock'] - (float)$r['qty']) * (float)$r['cost_price'];
}

export_if_requested('lowstock', 'สินค้าถึงจุดสั่งซื้อ',
    ['รหัส','ชื่อสินค้า','หมวดหมู่','หน่วย','คงเหลือ','จุดสั่งซื้อ','ต้องสั่งเพิ่ม','ผู้ขายล่าสุด'],
    array_map(fn($r) => [$r['sku'], $r['name'], $r['cat_name'], $r['unit_name'],
                         qty_fmt($r['qty']), qty_fmt($r['min_stock']),
                         qty_fmt(max(0, (float)$r['min_stock'] - (float)$r['qty'])), $r['supp_name']], $rows),
    ['ณ วันที่' => thai_date(date('Y-m-d')),
     'จำนวนรายการ' => number_format(count($rows)),
     'งบประมาณเติมสต๊อกโดยประมาณ' => number_format($needValue, 2) . ' บาท']);

render_header('สินค้าใกล้หมด / ถึงจุดสั่งซื้อ', 'report_lowstock');
?>
<div class="kpis">
  <div class="kpi danger"><span class="kico"><?= icon('warn') ?></span>
    <span><span class="kval"><?= number_format($outOfStock) ?></span><span class="klab">สินค้าหมดสต๊อก</span></span></div>
  <div class="kpi warn"><span class="kico"><?= icon('box') ?></span>
    <span><span class="kval"><?= number_format(count($rows)) ?></span><span class="klab">รายการถึงจุดสั่งซื้อ</span></span></div>
  <div class="kpi"><span class="kico"><?= icon('chart') ?></span>
    <span><span class="kval"><?= money($needValue) ?></span><span class="klab">งบประมาณเติมสต๊อกโดยประมาณ (บาท)</span></span></div>
</div>

<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="report_lowstock">
    <label>คลัง
      <select name="wh" data-autosubmit>
        <option value="">ทุกคลังรวมกัน</option>
        <?php foreach (all_warehouses(false) as $x): ?>
          <option value="<?= (int)$x['id'] ?>" <?= $whId === (int)$x['id'] ? 'selected' : '' ?>>
            <?= h($x['code'] . ' · ' . $x['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <span class="spacer"></span>
    <?= export_buttons() ?>
    <button type="button" class="btn ghost noprint" onclick="window.print()">พิมพ์ใบสั่งซื้อร่าง</button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">ยอดสต๊อกทุกรายการสูงกว่าจุดสั่งซื้อ</div>
  <?php else: ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>รหัส</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th>
                 <th class="num">คงเหลือ</th><th class="num">จุดสั่งซื้อ</th>
                 <th class="num">ควรสั่งเพิ่ม</th><th>ผู้ขายล่าสุด</th><th class="noprint"></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $qty  = (float)$r['qty'];
        $need = max(0, (float)$r['min_stock'] - $qty); ?>
        <tr>
          <td class="nowrap"><code><?= h($r['sku']) ?></code></td>
          <td><a href="<?= h(url('stock_card', ['id' => $r['id']])) ?>"><?= h($r['name']) ?></a></td>
          <td class="small muted"><?= h($r['cat_name'] ?? '—') ?></td>
          <td class="num"><span class="badge <?= $qty <= 0 ? 'danger' : 'warn' ?>"><?= qty_fmt($qty) ?></span></td>
          <td class="num muted"><?= qty_fmt($r['min_stock']) ?></td>
          <td class="num"><b><?= qty_fmt($need) ?></b> <span class="muted small"><?= h($r['unit_name'] ?? '') ?></span></td>
          <td class="small"><?= h($r['supp_name'] ?? '—') ?></td>
          <td class="right noprint">
            <?php if (can('staff')): ?>
              <a class="btn sm ghost" href="<?= h(url('receive')) ?>">รับเข้า</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
