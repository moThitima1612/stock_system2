<?php
require_once __DIR__ . '/../includes/layout.php';

$id  = inp_int('id', 0);
$row = $id ? q1('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$row) {
    flash('danger', 'ไม่พบสินค้าที่ต้องการแก้ไข');
    redirect(url('products'));
}

$f = $row ?: [
    'sku' => '', 'barcode' => '', 'name' => '', 'product_type' => 'FG',
    'category_id' => '', 'unit_id' => '',
    'cost_price' => '0.00', 'sell_price' => '0.00', 'min_stock' => '0', 'note' => '', 'is_active' => 1,
];
$err = '';

if (is_post()) {
    csrf_check();
    foreach (['sku','barcode','name','product_type','category_id','unit_id',
              'cost_price','sell_price','min_stock','note'] as $k) {
        $f[$k] = inp($k, '');
    }
    if (!isset(PRODUCT_TYPES[$f['product_type']])) {
        $f['product_type'] = 'FG';
    }
    $f['is_active'] = inp('is_active') ? 1 : 0;

    if ($f['sku'] === '' || $f['name'] === '') {
        $err = 'กรุณากรอกรหัสสินค้าและชื่อสินค้า';
    } else {
        $dupe = qv('SELECT id FROM products WHERE sku = ? AND id <> ?', [$f['sku'], $id]);
        if ($dupe) {
            $err = 'รหัสสินค้า "' . $f['sku'] . '" ถูกใช้ไปแล้ว';
        }
    }

    if (!$err) {
        $args = [
            $f['sku'], $f['barcode'] ?: null, $f['name'], $f['product_type'],
            $f['category_id'] ?: null, $f['unit_id'] ?: null,
            (float)$f['cost_price'], (float)$f['sell_price'], (float)$f['min_stock'],
            $f['note'] ?: null, $f['is_active'],
        ];
        if ($id) {
            $args[] = $id;
            q('UPDATE products SET sku=?, barcode=?, name=?, product_type=?, category_id=?, unit_id=?,
                      cost_price=?, sell_price=?, min_stock=?, note=?, is_active=? WHERE id=?', $args);
            flash('success', 'บันทึกการแก้ไขสินค้าเรียบร้อยแล้ว');
        } else {
            q('INSERT INTO products (sku, barcode, name, product_type, category_id, unit_id,
                      cost_price, sell_price, min_stock, note, is_active)
               VALUES (?,?,?,?,?,?,?,?,?,?,?)', $args);
            flash('success', 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว');
        }
        redirect(url('products'));
    }
}

$balances = $id ? qa(
    'SELECT w.code, w.name, b.qty FROM stock_balances b
       JOIN warehouses w ON w.id = b.warehouse_id
      WHERE b.product_id = ? ORDER BY w.code', [$id]) : [];

render_header($id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่', 'products');
?>
<?= error_alert($err) ?>

<form method="post" autocomplete="off">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-head"><h2>ข้อมูลสินค้า</h2></div>
    <div class="card-body">
      <div class="grid c2">
        <label>รหัสสินค้า (SKU) *
          <input type="text" name="sku" value="<?= h($f['sku']) ?>" required maxlength="50">
        </label>
        <label>บาร์โค้ด
          <input type="text" name="barcode" value="<?= h($f['barcode']) ?>" maxlength="50">
        </label>
      </div>

      <label>ชื่อสินค้า *
        <input type="text" name="name" value="<?= h($f['name']) ?>" required maxlength="200">
      </label>

      <div class="grid c2">
        <label>ประเภทสินค้า *
          <select name="product_type" required>
            <?php foreach (PRODUCT_TYPES as $tk => $tl): ?>
              <option value="<?= h($tk) ?>" <?= ($f['product_type'] ?? 'FG') === $tk ? 'selected' : '' ?>>
                <?= h($tl) ?> — <?= h($tk) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div style="align-self:end;margin-bottom:14px">
          <div class="alert info small" style="margin:0;padding:9px 12px">
            <b>MAT</b> วัตถุดิบที่ซื้อเข้ามาใช้ผลิต · <b>FG</b> สินค้าสำเร็จรูปที่ผลิต/ขาย ·
            <b>WIP</b> กึ่งสำเร็จรูป · <b>PACK</b> บรรจุภัณฑ์
          </div>
        </div>
      </div>

      <div class="grid c2">
        <label>หมวดหมู่
          <select name="category_id">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach (all_categories() as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$f['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                <?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>หน่วยนับ
          <select name="unit_id">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach (all_units() as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= (int)$f['unit_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                <?= h($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="grid c3">
        <label>ราคาทุน (บาท)
          <input type="number" step="0.01" min="0" name="cost_price" value="<?= h($f['cost_price']) ?>">
        </label>
        <label>ราคาขาย (บาท)
          <input type="number" step="0.01" min="0" name="sell_price" value="<?= h($f['sell_price']) ?>">
        </label>
        <label>จุดสั่งซื้อ (ขั้นต่ำ)
          <input type="number" step="0.001" min="0" name="min_stock" value="<?= h($f['min_stock']) ?>">
        </label>
      </div>

      <label>หมายเหตุ
        <input type="text" name="note" value="<?= h($f['note']) ?>" maxlength="255">
      </label>

      <label style="display:flex;gap:8px;align-items:center">
        <input type="checkbox" name="is_active" value="1" <?= $f['is_active'] ? 'checked' : '' ?>
               style="width:auto;margin:0">
        เปิดใช้งานสินค้านี้
      </label>
    </div>
  </div>

  <?php if ($balances): ?>
    <div class="card">
      <div class="card-head"><h2>ยอดคงเหลือปัจจุบัน</h2>
        <span class="spacer"></span>
        <a class="btn sm ghost" href="<?= h(url('stock_card', ['id' => $id])) ?>">ดูการ์ดสินค้า</a>
      </div>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>คลัง</th><th class="num">คงเหลือ</th></tr></thead>
          <tbody>
          <?php foreach ($balances as $b): ?>
            <tr><td><?= h($b['code'] . ' · ' . $b['name']) ?></td>
                <td class="num"><?= qty_fmt($b['qty']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="alert info" style="margin:14px 16px">
        การแก้ไขยอดคงเหลือทำผ่านเมนู <a href="<?= h(url('adjust')) ?>">ปรับปรุงยอดสต๊อก</a> เท่านั้น เพื่อให้มีร่องรอยตรวจสอบได้
      </div>
    </div>
  <?php endif; ?>

  <div class="btnrow">
    <button class="btn primary" type="submit">บันทึก</button>
    <a class="btn" href="<?= h(url('products')) ?>">ยกเลิก</a>
    <?php if ($id && in_array($f['product_type'] ?? '', ['FG', 'WIP'], true)): ?>
      <span class="spacer"></span>
      <a class="btn ghost" href="<?= h(url('bom', ['fg' => $id])) ?>"><?= icon('bom') ?> สูตรการผลิต</a>
      <a class="btn ghost" href="<?= h(url('production', ['fg' => $id])) ?>"><?= icon('prod') ?> เปิดใบผลิต</a>
    <?php endif; ?>
  </div>
</form>
<?php render_footer(); ?>
