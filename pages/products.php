<?php
require_once __DIR__ . '/../includes/layout.php';

/* ---- ลบสินค้า ---- */
if (is_post() && inp('action') === 'delete' && can('admin')) {
    csrf_check();
    $id = inp_int('id', 0);
    $used = (int)qv('SELECT COUNT(*) FROM stock_movements WHERE product_id = ?', [$id]);
    if ($used) {
        q('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
        flash('warn', 'สินค้ามีประวัติการเคลื่อนไหวแล้ว ระบบจึงเปลี่ยนเป็น "ปิดใช้งาน" แทนการลบ');
    } else {
        q('DELETE FROM products WHERE id = ?', [$id]);
        flash('success', 'ลบสินค้าเรียบร้อยแล้ว', 'ลบสินค้าสำเร็จ');
    }
    redirect(url_with([]));
}

$kw    = (string)inp('kw');
$cat   = inp_int('cat');
$ptype = (string)inp('ptype');
$stat  = (string)inp('stat', 'active');
$page  = max(1, inp_int('page', 1));
$per   = ROWS_PER_PAGE;

$where = [];
$args  = [];
if ($kw !== '') {
    $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR p.barcode LIKE ?)';
    array_push($args, "%$kw%", "%$kw%", "%$kw%");
}
if ($cat) {
    $where[] = 'p.category_id = ?';
    $args[]  = $cat;
}
if (isset(PRODUCT_TYPES[$ptype])) {
    $where[] = 'p.product_type = ?';
    $args[]  = $ptype;
}
if ($stat === 'active')   { $where[] = 'p.is_active = 1'; }
if ($stat === 'inactive') { $where[] = 'p.is_active = 0'; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)qv("SELECT COUNT(*) FROM products p $w", $args);
$rows  = qa(
    "SELECT p.*, c.name AS cat_name, u.name AS unit_name,
            COALESCE((SELECT SUM(qty) FROM stock_balances WHERE product_id = p.id), 0) AS qty
       FROM products p
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN units u ON u.id = p.unit_id
       $w
      ORDER BY p.sku
      LIMIT $per OFFSET " . (($page - 1) * $per),
    $args
);

if (inp('export') !== '') {
    $all = qa(
        "SELECT p.sku, p.barcode, p.name, p.product_type, c.name AS cat_name, u.name AS unit_name,
                p.cost_price, p.sell_price, p.min_stock,
                COALESCE((SELECT SUM(qty) FROM stock_balances WHERE product_id = p.id), 0) AS qty
           FROM products p
           LEFT JOIN categories c ON c.id = p.category_id
           LEFT JOIN units u ON u.id = p.unit_id
           $w ORDER BY p.product_type, p.sku",
        $args
    );
    export_if_requested('products', 'รายการสินค้า',
        ['รหัส', 'บาร์โค้ด', 'ชื่อสินค้า', 'ประเภท', 'หมวดหมู่', 'หน่วย', 'ทุน', 'ขาย', 'จุดสั่งซื้อ', 'คงเหลือ'],
        array_map(fn($r) => [$r['sku'], $r['barcode'], $r['name'], product_type_label($r['product_type']),
                             $r['cat_name'], $r['unit_name'],
                             number_format((float)$r['cost_price'], 2), number_format((float)$r['sell_price'], 2),
                             qty_fmt($r['min_stock']), qty_fmt($r['qty'])], $all),
        ['จำนวนรายการ' => number_format(count($all)), 'ณ วันที่' => thai_date(date('Y-m-d'))]);
}

render_header('สินค้า', 'products');
?>
<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="products">
    <label class="grow">ค้นหา
      <input type="search" name="kw" value="<?= h($kw) ?>" placeholder="รหัสสินค้า / ชื่อ / บาร์โค้ด">
    </label>
    <label>ประเภทสินค้า
      <select name="ptype">
        <option value="">ทุกประเภท</option>
        <?php foreach (PRODUCT_TYPES as $tk => $tl): ?>
          <option value="<?= h($tk) ?>" <?= $ptype === $tk ? 'selected' : '' ?>>
            <?= h($tl) ?> (<?= h($tk) ?>)</option>
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
    <label>สถานะ
      <select name="stat">
        <option value="active"   <?= $stat === 'active' ? 'selected' : '' ?>>ใช้งาน</option>
        <option value="inactive" <?= $stat === 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
        <option value="all"      <?= $stat === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
      </select>
    </label>
    <button class="btn primary"><?= icon('search') ?> ค้นหา</button>
    <?= export_buttons() ?>
    <span class="spacer"></span>
    <?php if (can('staff')): ?>
      <a class="btn primary" href="<?= h(url('product_form')) ?>"><?= icon('plus') ?> เพิ่มสินค้า</a>
    <?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">ไม่พบสินค้าตามเงื่อนไขที่ค้นหา</div>
  <?php else: ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>รหัส</th><th>ชื่อสินค้า</th><th>ประเภท</th><th>หมวดหมู่</th>
          <th class="num">คงเหลือ</th><th class="num">จุดสั่งซื้อ</th>
          <th class="num">ทุน</th><th class="num">ขาย</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $qty = (float)$r['qty'];
        $low = $qty <= (float)$r['min_stock']; ?>
        <tr<?= $r['is_active'] ? '' : ' style="opacity:.55"' ?>>
          <td class="nowrap"><code><?= h($r['sku']) ?></code>
              <?= $r['is_active'] ? '' : ' <span class="badge muted">ปิด</span>' ?></td>
          <td><a href="<?= h(url('stock_card', ['id' => $r['id']])) ?>"><?= h($r['name']) ?></a>
              <?php if ($r['barcode']): ?><div class="small muted"><?= h($r['barcode']) ?></div><?php endif; ?></td>
          <td><?= product_type_badge($r['product_type']) ?></td>
          <td class="small"><?= h($r['cat_name'] ?? '—') ?></td>
          <td class="num"><span class="badge <?= $low ? ($qty <= 0 ? 'danger' : 'warn') : 'ok' ?>">
              <?= qty_fmt($qty) ?></span>
              <span class="muted small"><?= h($r['unit_name'] ?? '') ?></span></td>
          <td class="num muted"><?= qty_fmt($r['min_stock']) ?></td>
          <td class="num"><?= money($r['cost_price']) ?></td>
          <td class="num"><?= money($r['sell_price']) ?></td>
          <td class="right nowrap">
            <?php if (can('staff')): ?>
              <a class="btn sm ghost" href="<?= h(url('product_form', ['id' => $r['id']])) ?>">แก้ไข</a>
            <?php endif; ?>
            <?php if (can('admin')): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn sm ghost" style="color:var(--danger)"
                        data-confirm="ลบสินค้า <?= h($r['name']) ?> ?">ลบ</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginate_links($total, $page, $per) ?>
  <div class="small muted" style="padding:0 16px 14px">พบทั้งหมด <?= number_format($total) ?> รายการ</div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
