<?php
require_once __DIR__ . '/../includes/layout.php';

$whId = inp_int('wh');
$whWhere  = $whId ? ' AND b.warehouse_id = ' . (int)$whId : '';
$mvWhere  = $whId ? ' AND m.warehouse_id = ' . (int)$whId : '';

$totalProducts = (int)qv('SELECT COUNT(*) FROM products WHERE is_active = 1');
$totalWh       = (int)qv('SELECT COUNT(*) FROM warehouses WHERE is_active = 1');

$stockValue = (float)qv(
    "SELECT COALESCE(SUM(b.qty * p.cost_price), 0)
       FROM stock_balances b JOIN products p ON p.id = b.product_id
      WHERE 1 = 1 $whWhere"
);
$totalQty = (float)qv("SELECT COALESCE(SUM(b.qty),0) FROM stock_balances b WHERE 1 = 1 $whWhere");

$lowStock = qa(
    "SELECT p.id, p.sku, p.name, p.min_stock, u.name AS unit_name,
            COALESCE(SUM(b.qty), 0) AS qty
       FROM products p
       LEFT JOIN units u ON u.id = p.unit_id
       LEFT JOIN stock_balances b ON b.product_id = p.id" . ($whId ? ' AND b.warehouse_id = ' . (int)$whId : '') . "
      WHERE p.is_active = 1
      GROUP BY p.id, p.sku, p.name, p.min_stock, u.name
     HAVING qty <= p.min_stock
      ORDER BY (COALESCE(SUM(b.qty),0) - p.min_stock), p.name
      LIMIT " . (int)LOW_STOCK_LIMIT
);
$lowCount = (int)qv(
    "SELECT COUNT(*) FROM (
        SELECT p.id, COALESCE(SUM(b.qty),0) AS qty, p.min_stock
          FROM products p
          LEFT JOIN stock_balances b ON b.product_id = p.id" . ($whId ? ' AND b.warehouse_id = ' . (int)$whId : '') . "
         WHERE p.is_active = 1
         GROUP BY p.id, p.min_stock
        HAVING qty <= p.min_stock) t"
);

$today = date('Y-m-d');
$inToday  = (float)qv("SELECT COALESCE(SUM(m.qty_change),0) FROM stock_movements m
                        WHERE m.qty_change > 0 AND DATE(m.moved_at) = ? $mvWhere", [$today]);
$outToday = (float)qv("SELECT COALESCE(-SUM(m.qty_change),0) FROM stock_movements m
                        WHERE m.qty_change < 0 AND DATE(m.moved_at) = ? $mvWhere", [$today]);

$recentDocs = qa(
    "SELECT d.*, w.name AS wh_name, w2.name AS wh2_name, u.fullname
       FROM stock_docs d
       JOIN warehouses w ON w.id = d.warehouse_id
       LEFT JOIN warehouses w2 ON w2.id = d.to_warehouse_id
       LEFT JOIN users u ON u.id = d.user_id
      " . ($whId ? 'WHERE d.warehouse_id = ' . (int)$whId . ' OR d.to_warehouse_id = ' . (int)$whId : '') . "
      ORDER BY d.id DESC LIMIT 8"
);

$byWarehouse = qa(
    'SELECT w.id, w.code, w.name,
            COALESCE(SUM(b.qty), 0) AS qty,
            COALESCE(SUM(b.qty * p.cost_price), 0) AS value
       FROM warehouses w
       LEFT JOIN stock_balances b ON b.warehouse_id = w.id
       LEFT JOIN products p ON p.id = b.product_id
      WHERE w.is_active = 1
      GROUP BY w.id, w.code, w.name
      ORDER BY value DESC'
);
$maxWhValue = 0;
foreach ($byWarehouse as $r) {
    $maxWhValue = max($maxWhValue, (float)$r['value']);
}

$byType    = stock_by_type($whId);
$typeTotal = 0.0;
foreach ($byType as $r) {
    $typeTotal += (float)$r['value'];
}

$topMoving = qa(
    "SELECT p.sku, p.name, u.name AS unit_name, SUM(-m.qty_change) AS moved
       FROM stock_movements m
       JOIN products p ON p.id = m.product_id
       LEFT JOIN units u ON u.id = p.unit_id
      WHERE m.qty_change < 0 AND m.doc_type IN ('OUT','PROD')
        AND m.moved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) $mvWhere
      GROUP BY p.id, p.sku, p.name, u.name
      ORDER BY moved DESC LIMIT 5"
);

render_header('แดชบอร์ด', 'dashboard');
?>
<form method="get" class="filters" style="border-radius:12px;border:1px solid var(--line);margin-bottom:18px">
  <input type="hidden" name="p" value="dashboard">
  <label>มุมมองคลัง
    <select name="wh" data-autosubmit>
      <option value="">ทุกคลังรวมกัน</option>
      <?php foreach (all_warehouses() as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $whId === (int)$w['id'] ? 'selected' : '' ?>>
          <?= h($w['code'] . ' · ' . $w['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <span class="spacer"></span>
</form>

<div class="kpis">
  <div class="kpi">
    <span class="kico"><?= icon('chart') ?></span>
    <span><span class="kval"><?= money($stockValue) ?></span><span class="klab">มูลค่าสต๊อก (บาท)</span></span>
  </div>
  <div class="kpi ok">
    <span class="kico"><?= icon('box') ?></span>
    <span><span class="kval"><?= qty_fmt($totalQty) ?></span><span class="klab">จำนวนชิ้นคงคลัง · <?= number_format($totalProducts) ?> รายการสินค้า</span></span>
  </div>
  <div class="kpi <?= $lowCount ? 'danger' : '' ?>">
    <span class="kico"><?= icon('warn') ?></span>
    <span><span class="kval"><?= number_format($lowCount) ?></span><span class="klab">สินค้าถึงจุดสั่งซื้อ</span></span>
  </div>
  <div class="kpi warn">
    <span class="kico"><?= icon('in') ?></span>
    <span><span class="kval"><?= qty_fmt($inToday) ?> / <?= qty_fmt($outToday) ?></span><span class="klab">เข้า / ออก วันนี้</span></span>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>สต๊อกแยกตามประเภทสินค้า</h2>
    <span class="spacer"></span>
    <a class="btn sm ghost" href="<?= h(url('bom')) ?>"><?= icon('bom') ?> สูตรการผลิต</a>
    <a class="btn sm" href="<?= h(url('production')) ?>"><?= icon('prod') ?> เปิดใบผลิต</a>
  </div>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>ประเภท</th><th style="width:26%">สัดส่วนมูลค่า</th>
                 <th class="num">จำนวนรายการ</th><th class="num">คงเหลือรวม</th><th class="num">มูลค่า</th></tr></thead>
      <tbody>
      <?php foreach (PRODUCT_TYPES as $tk => $tl):
        $r   = $byType[$tk] ?? null;
        if (!$r) { continue; }
        $pct = $typeTotal > 0 ? round((float)$r['value'] / $typeTotal * 100) : 0; ?>
        <tr>
          <td><?= product_type_badge($tk) ?> <span class="small muted"><?= h($tk) ?></span></td>
          <td><div class="bar"><i style="width:<?= $pct ?>%"></i></div>
              <span class="small muted"><?= $pct ?>%</span></td>
          <td class="num"><?= number_format((int)$r['n_products']) ?></td>
          <td class="num"><?= qty_fmt($r['qty']) ?></td>
          <td class="num"><b><?= money($r['value']) ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="4">มูลค่าสต๊อกรวมทุกประเภท</td>
                 <td class="num"><?= money($typeTotal) ?></td></tr></tfoot>
    </table>
  </div>
</div>

<div class="grid c2" style="gap:18px">
  <div class="card">
    <div class="card-head"><h2>สินค้าถึงจุดสั่งซื้อ</h2>
      <span class="spacer"></span>
      <a class="btn sm ghost" href="<?= h(url('report_lowstock')) ?>">ดูทั้งหมด</a>
    </div>
    <?php if (!$lowStock): ?>
      <div class="empty">ทุกรายการมีสต๊อกสูงกว่าจุดสั่งซื้อ</div>
    <?php else: ?>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>สินค้า</th><th class="num">คงเหลือ</th><th class="num">จุดสั่งซื้อ</th></tr></thead>
          <tbody>
          <?php foreach ($lowStock as $r): ?>
            <tr>
              <td><a href="<?= h(url('stock_card', ['id' => $r['id']])) ?>"><?= h($r['name']) ?></a>
                  <div class="small muted"><?= h($r['sku']) ?></div></td>
              <td class="num"><span class="badge <?= (float)$r['qty'] <= 0 ? 'danger' : 'warn' ?>">
                  <?= qty_fmt($r['qty']) ?></span></td>
              <td class="num muted"><?= qty_fmt($r['min_stock']) ?> <?= h($r['unit_name'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>มูลค่าสต๊อกแยกตามคลัง</h2></div>
    <?php if (!$byWarehouse): ?>
      <div class="empty">ยังไม่มีคลังสินค้า</div>
    <?php else: ?>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>คลัง</th><th style="width:34%">สัดส่วน</th><th class="num">จำนวน</th><th class="num">มูลค่า</th></tr></thead>
          <tbody>
          <?php foreach ($byWarehouse as $r):
              $pct = $maxWhValue > 0 ? round((float)$r['value'] / $maxWhValue * 100) : 0; ?>
            <tr>
              <td><?= h($r['name']) ?><div class="small muted"><?= h($r['code']) ?></div></td>
              <td><div class="bar"><i style="width:<?= $pct ?>%"></i></div></td>
              <td class="num"><?= qty_fmt($r['qty']) ?></td>
              <td class="num"><?= money($r['value']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid c2" style="gap:18px">
  <div class="card">
    <div class="card-head"><h2>เอกสารล่าสุด</h2>
      <span class="spacer"></span>
      <a class="btn sm ghost" href="<?= h(url('docs')) ?>">ดูทั้งหมด</a>
    </div>
    <?php if (!$recentDocs): ?>
      <div class="empty">ยังไม่มีเอกสาร — เริ่มจาก<a href="<?= h(url('receive')) ?>">รับสินค้าเข้า</a></div>
    <?php else: ?>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>เลขที่</th><th>ประเภท</th><th>คลัง</th><th class="num">จำนวน</th><th>วันที่</th></tr></thead>
          <tbody>
          <?php foreach ($recentDocs as $d): ?>
            <tr<?= $d['status'] === 'void' ? ' style="opacity:.5"' : '' ?>>
              <td><a href="<?= h(url('doc_view', ['id' => $d['id']])) ?>"><?= h($d['doc_no']) ?></a></td>
              <td><?= doc_type_badge($d['doc_type']) ?></td>
              <td class="small"><?= h($d['wh_name']) ?><?= $d['wh2_name'] ? ' → ' . h($d['wh2_name']) : '' ?></td>
              <td class="num"><?= qty_fmt($d['total_qty']) ?></td>
              <td class="small muted nowrap"><?= h(thai_date($d['doc_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>สินค้าเคลื่อนไหวสูงสุด <span class="muted small">(30 วัน)</span></h2></div>
    <?php if (!$topMoving): ?>
      <div class="empty">ยังไม่มีการเบิกจ่ายในช่วง 30 วันที่ผ่านมา</div>
    <?php else: ?>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>สินค้า</th><th class="num">จ่ายออกรวม</th></tr></thead>
          <tbody>
          <?php foreach ($topMoving as $r): ?>
            <tr>
              <td><?= h($r['name']) ?><div class="small muted"><?= h($r['sku']) ?></div></td>
              <td class="num"><?= qty_fmt($r['moved']) ?> <span class="muted small"><?= h($r['unit_name'] ?? '') ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php render_footer(); ?>
