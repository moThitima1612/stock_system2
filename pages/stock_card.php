<?php
require_once __DIR__ . '/../includes/layout.php';

$pid  = inp_int('id', 0);
$whId = inp_int('wh');
$from = (string)inp('from', date('Y-m-01'));
$to   = (string)inp('to', date('Y-m-d'));

$products = qa('SELECT id, sku, name FROM products ORDER BY sku');
if (!$pid && $products) {
    $pid = (int)$products[0]['id'];
}

$prod = $pid ? q1(
    'SELECT p.*, c.name AS cat_name, u.name AS unit_name
       FROM products p
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN units u ON u.id = p.unit_id
      WHERE p.id = ?', [$pid]) : null;

render_header('การ์ดสินค้า', 'stock_card');

if (!$prod) {
    echo '<div class="card"><div class="empty">ยังไม่มีสินค้าในระบบ '
       . '<a href="' . h(url('product_form')) . '">เพิ่มสินค้า</a></div></div>';
    render_footer();
    exit;
}

$balances = qa(
    'SELECT w.id, w.code, w.name, COALESCE(b.qty,0) AS qty
       FROM warehouses w
       LEFT JOIN stock_balances b ON b.warehouse_id = w.id AND b.product_id = ?
      WHERE w.is_active = 1 ORDER BY w.code', [$pid]);
$totalQty = 0;
foreach ($balances as $b) { $totalQty += (float)$b['qty']; }

/* ยอดยกมา ณ วันเริ่มต้นช่วง */
$argsB = [$pid, $from];
$whSql = $whId ? ' AND m.warehouse_id = ?' : '';
if ($whId) { $argsB[] = $whId; }
$opening = (float)qv(
    "SELECT COALESCE(SUM(m.qty_change),0) FROM stock_movements m
      WHERE m.product_id = ? AND DATE(m.moved_at) < ? $whSql", $argsB);

$argsM = [$pid, $from, $to];
if ($whId) { $argsM[] = $whId; }
$moves = qa(
    "SELECT m.*, w.code AS wh_code, u.fullname
       FROM stock_movements m
       JOIN warehouses w ON w.id = m.warehouse_id
       LEFT JOIN users u ON u.id = m.user_id
      WHERE m.product_id = ? AND DATE(m.moved_at) BETWEEN ? AND ? $whSql
      ORDER BY m.id", $argsM);

$run = $opening;
$sumIn = $sumOut = 0.0;
?>
<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="stock_card">
    <label class="grow">สินค้า
      <select name="id" data-autosubmit>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $pid === (int)$p['id'] ? 'selected' : '' ?>>
            <?= h($p['sku'] . ' · ' . $p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>คลัง
      <select name="wh">
        <option value="">ทุกคลัง</option>
        <?php foreach (all_warehouses(false) as $x): ?>
          <option value="<?= (int)$x['id'] ?>" <?= $whId === (int)$x['id'] ? 'selected' : '' ?>>
            <?= h($x['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ตั้งแต่ <input type="date" name="from" value="<?= h($from) ?>"></label>
    <label>ถึง <input type="date" name="to" value="<?= h($to) ?>"></label>
    <button class="btn primary">แสดง</button>
  </form>

  <div class="card-body">
    <div class="grid c4" style="gap:14px">
      <div><span class="small muted">รหัสสินค้า</span><div><b><code><?= h($prod['sku']) ?></code></b></div></div>
      <div><span class="small muted">ชื่อสินค้า</span><div><b><?= h($prod['name']) ?></b></div></div>
      <div><span class="small muted">หมวดหมู่ / หน่วย</span>
           <div><b><?= h($prod['cat_name'] ?? '—') ?> / <?= h($prod['unit_name'] ?? '—') ?></b></div></div>
      <div><span class="small muted">คงเหลือรวมทุกคลัง</span>
           <div><b style="font-size:1.15rem"><?= qty_fmt($totalQty) ?></b>
                <span class="muted small">จุดสั่งซื้อ <?= qty_fmt($prod['min_stock']) ?></span></div></div>
    </div>

    <div class="btnrow" style="margin-top:14px">
      <?php foreach ($balances as $b): ?>
        <span class="badge <?= (float)$b['qty'] > 0 ? 'info' : 'muted' ?>">
          <?= h($b['code']) ?>: <?= qty_fmt($b['qty']) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>การเคลื่อนไหว <?= h(thai_date($from)) ?> — <?= h(thai_date($to)) ?></h2>
    <span class="spacer"></span>
    <button class="btn sm ghost noprint" onclick="window.print()">พิมพ์</button>
  </div>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>วันที่</th><th>เอกสาร</th><th>ประเภท</th><th>คลัง</th>
                 <th class="num">รับ</th><th class="num">จ่าย</th><th class="num">คงเหลือสะสม</th><th>หมายเหตุ</th></tr></thead>
      <tbody>
        <tr style="background:var(--surface-2)">
          <td colspan="6"><b>ยอดยกมา</b> <span class="muted small">ก่อน <?= h(thai_date($from)) ?></span></td>
          <td class="num"><b><?= qty_fmt($opening) ?></b></td><td></td>
        </tr>
      <?php foreach ($moves as $m):
        $d    = (float)$m['qty_change'];
        $run += $d;
        if ($d > 0) { $sumIn += $d; } else { $sumOut += -$d; } ?>
        <tr>
          <td class="small muted nowrap"><?= h(thai_date($m['moved_at'], true)) ?></td>
          <td class="nowrap"><?php if ($m['doc_id']): ?>
              <a href="<?= h(url('doc_view', ['id' => $m['doc_id']])) ?>"><code><?= h($m['doc_no']) ?></code></a>
              <?php else: ?><code><?= h($m['doc_no']) ?></code><?php endif; ?></td>
          <td><?= doc_type_badge($m['doc_type']) ?></td>
          <td class="small"><?= h($m['wh_code']) ?></td>
          <td class="num"><?= $d > 0 ? qty_fmt($d) : '' ?></td>
          <td class="num"><?= $d < 0 ? qty_fmt(-$d) : '' ?></td>
          <td class="num"><b><?= qty_fmt($run) ?></b></td>
          <td class="small muted"><?= h($m['note'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$moves): ?>
        <tr><td colspan="8" class="empty">ไม่มีการเคลื่อนไหวในช่วงเวลานี้</td></tr>
      <?php endif; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4">รวมในช่วง</td>
            <td class="num"><?= qty_fmt($sumIn) ?></td>
            <td class="num"><?= qty_fmt($sumOut) ?></td>
            <td class="num"><?= qty_fmt($run) ?></td><td></td></tr>
      </tfoot>
    </table>
  </div>
</div>
<?php render_footer(); ?>
