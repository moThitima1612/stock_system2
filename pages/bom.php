<?php
/** สูตรการผลิต (BOM) — กำหนดว่าสินค้าสำเร็จรูป 1 หน่วย ใช้วัตถุดิบอะไรบ้าง */
require_once __DIR__ . '/../includes/layout.php';

$fgId = inp_int('fg', 0);

/* ---------- เพิ่มวัตถุดิบเข้าสูตร ---------- */
if (is_post() && inp('action') === 'add') {
    csrf_check();
    $fgId = inp_int('fg', 0);
    $mat  = inp_int('material_id', 0);
    $qty  = inp_dec('qty', 0);

    if ($fgId <= 0 || $mat <= 0) {
        flash('danger', 'กรุณาเลือกสินค้าและวัตถุดิบให้ครบ');
    } elseif ($mat === $fgId) {
        flash('danger', 'สินค้าเป็นวัตถุดิบของตัวเองไม่ได้');
    } elseif ($qty <= 0) {
        flash('danger', 'จำนวนวัตถุดิบต้องมากกว่า 0');
    } elseif (qv('SELECT id FROM bom_items WHERE fg_product_id = ? AND material_id = ?', [$fgId, $mat])) {
        q('UPDATE bom_items SET qty = ? WHERE fg_product_id = ? AND material_id = ?', [$qty, $fgId, $mat]);
        flash('success', 'ปรับจำนวนวัตถุดิบในสูตรเรียบร้อยแล้ว');
    } elseif (qv('SELECT id FROM bom_items WHERE fg_product_id = ? AND material_id = ?', [$mat, $fgId])) {
        flash('danger', 'สร้างสูตรวนกลับไปมาระหว่างสองสินค้านี้ไม่ได้');
    } else {
        q('INSERT INTO bom_items (fg_product_id, material_id, qty, note) VALUES (?,?,?,?)',
            [$fgId, $mat, $qty, inp('note') ?: null]);
        flash('success', 'เพิ่มวัตถุดิบเข้าสูตรเรียบร้อยแล้ว', 'เพิ่มวัตถุดิบเข้าสูตรแล้ว');
    }
    redirect(url('bom', ['fg' => $fgId]));
}

/* ---------- ลบวัตถุดิบออกจากสูตร ---------- */
if (is_post() && inp('action') === 'del') {
    csrf_check();
    $fgId = inp_int('fg', 0);
    q('DELETE FROM bom_items WHERE id = ? AND fg_product_id = ?', [inp_int('id', 0), $fgId]);
    flash('success', 'ลบวัตถุดิบออกจากสูตรแล้ว', 'ลบวัตถุดิบออกจากสูตรแล้ว');
    redirect(url('bom', ['fg' => $fgId]));
}

/* ---------- คัดลอกสูตรจากสินค้าอื่น ---------- */
if (is_post() && inp('action') === 'copy') {
    csrf_check();
    $fgId = inp_int('fg', 0);
    $src  = inp_int('src', 0);
    if ($src > 0 && $fgId > 0 && $src !== $fgId) {
        q('INSERT IGNORE INTO bom_items (fg_product_id, material_id, qty, note)
           SELECT ?, material_id, qty, note FROM bom_items WHERE fg_product_id = ?', [$fgId, $src]);
        flash('success', 'คัดลอกสูตรเรียบร้อยแล้ว', 'คัดลอกสูตรสำเร็จ');
    }
    redirect(url('bom', ['fg' => $fgId]));
}

$madeTypes  = ['FG', 'WIP'];
$fgList     = qa("SELECT id, sku, name, product_type FROM products
                   WHERE is_active = 1 AND product_type IN ('FG','WIP')
                   ORDER BY product_type, sku");
if (!$fgId && $fgList) {
    // เริ่มที่สินค้าที่มีสูตรอยู่แล้ว จะได้เห็นตัวอย่างทันที
    $fgId = (int)qv("SELECT p.id FROM products p JOIN bom_items b ON b.fg_product_id = p.id
                      WHERE p.is_active = 1 AND p.product_type IN ('FG','WIP')
                      ORDER BY p.sku LIMIT 1", [], $fgList[0]['id']);
}

$fg = $fgId ? q1('SELECT p.*, u.name AS unit_name FROM products p
                    LEFT JOIN units u ON u.id = p.unit_id WHERE p.id = ?', [$fgId]) : null;

$bom       = $fg ? get_bom($fgId) : [];
$unitCost  = 0.0;
foreach ($bom as $b) {
    $unitCost += (float)$b['qty'] * (float)$b['cost_price'];
}
$margin = $fg ? (float)$fg['sell_price'] - $unitCost : 0;

$withBom = products_with_bom();

render_header('สูตรการผลิต (BOM)', 'bom');

if (!$fgList) {
    echo '<div class="alert warn">ยังไม่มีสินค้าประเภท <b>สินค้าสำเร็จรูป (FG)</b> หรือ <b>กึ่งสำเร็จรูป (WIP)</b> ในระบบ<br>'
       . 'ไปที่หน้า <a href="' . h(url('products')) . '">สินค้า</a> แล้วตั้งประเภทสินค้าเป็น FG ก่อนสร้างสูตรการผลิต</div>';
    render_footer();
    exit;
}
?>
<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="bom">
    <label class="grow">สินค้าที่ต้องการกำหนดสูตร
      <select name="fg" data-autosubmit>
        <?php foreach ($fgList as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $fgId === (int)$p['id'] ? 'selected' : '' ?>>
            [<?= h($p['product_type']) ?>] <?= h($p['sku'] . ' · ' . $p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <span class="spacer"></span>
    <a class="btn" href="<?= h(url('production', ['fg' => $fgId])) ?>"><?= icon('prod') ?> เปิดใบผลิต</a>
  </form>
</div>

<div class="kpis">
  <div class="kpi"><span class="kico"><?= icon('bom') ?></span>
    <span><span class="kval"><?= count($bom) ?></span><span class="klab">รายการวัตถุดิบในสูตร</span></span></div>
  <div class="kpi warn"><span class="kico"><?= icon('chart') ?></span>
    <span><span class="kval"><?= money($unitCost) ?></span><span class="klab">ต้นทุนวัตถุดิบ / <?= h($fg['unit_name'] ?: 'หน่วย') ?></span></span></div>
  <div class="kpi ok"><span class="kico"><?= icon('box') ?></span>
    <span><span class="kval"><?= money($fg['sell_price']) ?></span><span class="klab">ราคาขายที่ตั้งไว้</span></span></div>
  <div class="kpi <?= $margin < 0 ? 'danger' : 'ok' ?>"><span class="kico"><?= icon('chart') ?></span>
    <span><span class="kval"><?= money($margin) ?></span><span class="klab">กำไรขั้นต้น / หน่วย
      <?= $unitCost > 0 ? '(' . number_format($margin / $unitCost * 100, 1) . '%)' : '' ?></span></span></div>
</div>

<div class="grid" style="grid-template-columns:minmax(290px,350px) 1fr;gap:20px;align-items:start">
  <div class="card">
    <div class="card-head"><h2>เพิ่มวัตถุดิบเข้าสูตร</h2></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="fg" value="<?= (int)$fgId ?>">

        <label>วัตถุดิบ / ส่วนประกอบ
          <select name="material_id" required>
            <option value="">— เลือกวัตถุดิบ —</option>
            <?php foreach (PRODUCT_TYPES as $tk => $tl):
              $opts = qa('SELECT id, sku, name FROM products
                           WHERE is_active = 1 AND product_type = ? AND id <> ?
                           ORDER BY sku', [$tk, $fgId]);
              if (!$opts) { continue; } ?>
              <optgroup label="<?= h($tl) ?>">
                <?php foreach ($opts as $o): ?>
                  <option value="<?= (int)$o['id'] ?>"><?= h($o['sku'] . ' · ' . $o['name']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </label>

        <label>ใช้จำนวน (ต่อการผลิต 1 <?= h($fg['unit_name'] ?: 'หน่วย') ?>)
          <input type="number" name="qty" step="0.001" min="0.001" value="1" required>
        </label>

        <label>หมายเหตุ
          <input type="text" name="note" maxlength="255" placeholder="เช่น เผื่อเสีย 2%">
        </label>

        <button class="btn primary block" type="submit"><?= icon('plus') ?> เพิ่มเข้าสูตร</button>
      </form>

      <?php if (count($withBom) > ($bom ? 1 : 0)): ?>
        <hr style="border:none;border-top:1px solid var(--line-2);margin:20px 0">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="copy">
          <input type="hidden" name="fg" value="<?= (int)$fgId ?>">
          <label>คัดลอกสูตรจากสินค้าอื่น
            <select name="src">
              <option value="">— เลือกสินค้าต้นแบบ —</option>
              <?php foreach ($withBom as $w): if ((int)$w['id'] === $fgId) { continue; } ?>
                <option value="<?= (int)$w['id'] ?>">
                  <?= h($w['sku'] . ' · ' . $w['name']) ?> (<?= (int)$w['n_materials'] ?> รายการ)</option>
              <?php endforeach; ?>
            </select>
          </label>
          <button class="btn block" type="submit">คัดลอกสูตร</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h2>สูตรของ <?= h($fg['name']) ?></h2>
      <?= product_type_badge($fg['product_type']) ?>
      <span class="spacer"></span>
      <code class="muted"><?= h($fg['sku']) ?></code>
    </div>

    <?php if (!$bom): ?>
      <div class="empty">ยังไม่มีวัตถุดิบในสูตร — เพิ่มรายการแรกจากฟอร์มด้านซ้าย</div>
    <?php else: ?>
    <div class="tablewrap">
      <table class="tbl">
        <thead><tr><th>วัตถุดิบ</th><th>ประเภท</th><th class="num">ใช้ / หน่วย</th>
                   <th class="num">ต้นทุน/หน่วย</th><th class="num">รวมต้นทุน</th>
                   <th class="num">คงเหลือในคลัง</th><th class="num">ผลิตได้สูงสุด</th><th></th></tr></thead>
        <tbody>
        <?php
        $maxBuild = null;
        foreach ($bom as $b):
            $need    = (float)$b['qty'];
            $onHand  = get_total_balance((int)$b['material_id']);
            $canMake = $need > 0 ? floor($onHand / $need) : null;
            if ($canMake !== null) {
                $maxBuild = $maxBuild === null ? $canMake : min($maxBuild, $canMake);
            }
        ?>
          <tr>
            <td><a href="<?= h(url('stock_card', ['id' => $b['material_id']])) ?>"><?= h($b['name']) ?></a>
                <div class="small muted"><?= h($b['sku']) ?><?= $b['note'] ? ' · ' . h($b['note']) : '' ?></div></td>
            <td><?= product_type_badge($b['product_type']) ?></td>
            <td class="num"><b><?= qty_fmt($need) ?></b> <span class="muted small"><?= h($b['unit_name'] ?? '') ?></span></td>
            <td class="num"><?= money($b['cost_price']) ?></td>
            <td class="num"><?= money($need * (float)$b['cost_price']) ?></td>
            <td class="num"><span class="badge <?= $onHand < $need ? 'danger' : 'ok' ?>"><?= qty_fmt($onHand) ?></span></td>
            <td class="num muted"><?= $canMake === null ? '—' : number_format($canMake) ?></td>
            <td class="right">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del">
                <input type="hidden" name="fg" value="<?= (int)$fgId ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn sm ghost" style="color:var(--danger)"
                        data-confirm="ลบ <?= h($b['name']) ?> ออกจากสูตร?">ลบ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4">ต้นทุนวัตถุดิบรวมต่อ 1 <?= h($fg['unit_name'] ?: 'หน่วย') ?></td>
            <td class="num"><?= money($unitCost) ?></td>
            <td></td>
            <td class="num">ผลิตได้ <?= $maxBuild === null ? '—' : number_format($maxBuild) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="alert info" style="margin:16px 20px">
      วัตถุดิบในคลังตอนนี้ผลิต <b><?= h($fg['name']) ?></b> ได้สูงสุด
      <b><?= $maxBuild === null ? '—' : number_format($maxBuild) ?></b> <?= h($fg['unit_name'] ?: 'หน่วย') ?>
      — <a href="<?= h(url('production', ['fg' => $fgId])) ?>">เปิดใบผลิตเลย</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php render_footer(); ?>
