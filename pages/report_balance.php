<?php
require_once __DIR__ . '/../includes/layout.php';

$whId = inp_int('wh');
$cat  = inp_int('cat');
$kw   = (string)inp('kw');
$hide = inp('hidezero') !== '';

$ptype = (string)inp('ptype');

$where = ['p.is_active = 1'];
$args  = [];
if ($cat) { $where[] = 'p.category_id = ?'; $args[] = $cat; }
if (isset(PRODUCT_TYPES[$ptype])) { $where[] = 'p.product_type = ?'; $args[] = $ptype; }
if ($kw !== '') {
    $where[] = '(p.sku LIKE ? OR p.name LIKE ?)';
    array_push($args, "%$kw%", "%$kw%");
}

$joinBal = $whId
    ? 'LEFT JOIN stock_balances b ON b.product_id = p.id AND b.warehouse_id = ' . (int)$whId
    : 'LEFT JOIN stock_balances b ON b.product_id = p.id';

$having = $hide ? 'HAVING qty <> 0' : '';

$rows = qa(
    "SELECT p.id, p.sku, p.name, p.product_type, p.cost_price, p.min_stock,
            c.name AS cat_name, un.name AS unit_name,
            COALESCE(SUM(b.qty), 0) AS qty,
            COALESCE(SUM(b.qty), 0) * p.cost_price AS value
       FROM products p
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN units un ON un.id = p.unit_id
       $joinBal
      WHERE " . implode(' AND ', $where) . "
      GROUP BY p.id, p.sku, p.name, p.product_type, p.cost_price, p.min_stock, c.name, un.name
      $having
      ORDER BY value DESC, p.sku",
    $args
);

$totQty = $totVal = 0.0;
foreach ($rows as $r) { $totQty += (float)$r['qty']; $totVal += (float)$r['value']; }

$whName = $whId ? (string)qv('SELECT name FROM warehouses WHERE id = ?', [$whId], '') : 'ทุกคลังรวมกัน';

export_if_requested('stock_balance', 'รายงานยอดคงเหลือ',
    ['รหัส','ชื่อสินค้า','ประเภท','หมวดหมู่','หน่วย','คงเหลือ','จุดสั่งซื้อ','ทุน/หน่วย','มูลค่ารวม'],
    array_map(fn($r) => [$r['sku'], $r['name'], product_type_label($r['product_type']),
                         $r['cat_name'], $r['unit_name'],
                         qty_fmt($r['qty']), qty_fmt($r['min_stock']),
                         number_format((float)$r['cost_price'], 2), number_format((float)$r['value'], 2)], $rows),
    ['คลัง' => $whName, 'ณ วันที่' => thai_date(date('Y-m-d')),
     'มูลค่ารวม' => number_format($totVal, 2) . ' บาท']);

render_header('รายงานยอดคงเหลือ', 'report_balance');
?>
<div class="kpis">
  <div class="kpi"><span class="kico"><?= icon('chart') ?></span>
    <span><span class="kval"><?= money($totVal) ?></span><span class="klab">มูลค่าสต๊อกรวม (บาท)</span></span></div>
  <div class="kpi ok"><span class="kico"><?= icon('box') ?></span>
    <span><span class="kval"><?= qty_fmt($totQty) ?></span><span class="klab">จำนวนชิ้นรวม</span></span></div>
  <div class="kpi"><span class="kico"><?= icon('docs') ?></span>
    <span><span class="kval"><?= number_format(count($rows)) ?></span><span class="klab">รายการสินค้า · <?= h($whName) ?></span></span></div>
</div>

<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="report_balance">
    <label>คลัง
      <select name="wh">
        <option value="">ทุกคลังรวมกัน</option>
        <?php foreach (all_warehouses(false) as $x): ?>
          <option value="<?= (int)$x['id'] ?>" <?= $whId === (int)$x['id'] ? 'selected' : '' ?>>
            <?= h($x['code'] . ' · ' . $x['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ประเภทสินค้า
      <select name="ptype">
        <option value="">ทุกประเภท</option>
        <?php foreach (PRODUCT_TYPES as $tk => $tl): ?>
          <option value="<?= h($tk) ?>" <?= $ptype === $tk ? 'selected' : '' ?>><?= h($tl) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>หมวดหมู่
      <select name="cat">
        <option value="">ทั้งหมด</option>
        <?php foreach (all_categories() as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="grow">ค้นหา <input type="search" name="kw" value="<?= h($kw) ?>" placeholder="รหัส / ชื่อสินค้า"></label>
    <label style="min-width:auto;display:flex;gap:6px;align-items:center;padding-bottom:6px">
      <input type="checkbox" name="hidezero" value="1" <?= $hide ? 'checked' : '' ?> style="width:auto;margin:0">
      ซ่อนรายการที่ยอดเป็นศูนย์
    </label>
    <button class="btn primary">แสดง</button>
    <?= export_buttons() ?>
    <button type="button" class="btn ghost noprint" onclick="window.print()">พิมพ์</button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">ไม่พบข้อมูลตามเงื่อนไข</div>
  <?php else: ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>รหัส</th><th>ชื่อสินค้า</th><th>ประเภท</th><th>หมวดหมู่</th>
                 <th class="num">คงเหลือ</th><th class="num">จุดสั่งซื้อ</th>
                 <th class="num">ทุน/หน่วย</th><th class="num">มูลค่า</th><th style="width:12%">สัดส่วนมูลค่า</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $qty = (float)$r['qty'];
        $low = $qty <= (float)$r['min_stock'];
        $pct = $totVal > 0 ? min(100, round((float)$r['value'] / $totVal * 100)) : 0; ?>
        <tr>
          <td class="nowrap"><code><?= h($r['sku']) ?></code></td>
          <td><a href="<?= h(url('stock_card', ['id' => $r['id']])) ?>"><?= h($r['name']) ?></a></td>
          <td><?= product_type_badge($r['product_type']) ?></td>
          <td class="small muted"><?= h($r['cat_name'] ?? '—') ?></td>
          <td class="num"><span class="badge <?= $low ? ($qty <= 0 ? 'danger' : 'warn') : 'ok' ?>"><?= qty_fmt($qty) ?></span>
              <span class="muted small"><?= h($r['unit_name'] ?? '') ?></span></td>
          <td class="num muted"><?= qty_fmt($r['min_stock']) ?></td>
          <td class="num"><?= money($r['cost_price']) ?></td>
          <td class="num"><b><?= money($r['value']) ?></b></td>
          <td><div class="bar"><i style="width:<?= $pct ?>%"></i></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4">รวม <?= number_format(count($rows)) ?> รายการ</td>
            <td class="num"><?= qty_fmt($totQty) ?></td><td></td><td></td>
            <td class="num"><?= money($totVal) ?></td><td></td></tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
