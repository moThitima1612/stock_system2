<?php
/**
 * ฟอร์มเอกสารสต๊อกที่ใช้ร่วมกัน (รับเข้า / เบิกออก / ปรับปรุง)
 * ผู้เรียกต้องกำหนด: $DOC_TYPE, $PAGE_TITLE, $PAGE_KEY
 */
require_once __DIR__ . '/../includes/layout.php';

$type   = $DOC_TYPE;
$isIn   = $type === 'IN';
$isOut  = $type === 'OUT';
$isAdj  = $type === 'ADJUST';

$warehouses = all_warehouses();
if (!$warehouses) {
    render_header($PAGE_TITLE, $PAGE_KEY);
    echo '<div class="alert warn">ยังไม่มีคลังสินค้าในระบบ กรุณา<a href="' . h(url('warehouses')) . '">เพิ่มคลังสินค้า</a>ก่อน</div>';
    render_footer();
    exit;
}
if (!qv('SELECT COUNT(*) FROM products WHERE is_active = 1')) {
    render_header($PAGE_TITLE, $PAGE_KEY);
    echo '<div class="alert warn">ยังไม่มีสินค้าในระบบ กรุณา<a href="' . h(url('product_form')) . '">เพิ่มสินค้า</a>ก่อน</div>';
    render_footer();
    exit;
}

$err = '';
$old = [
    'doc_date'        => date('Y-m-d'),
    'warehouse_id'    => (int)($warehouses[0]['id']),
    'supplier_id'     => 0,
    'ref_no'          => '',
    'contact'         => '',
    'ship_to'         => '',
    'ship_tel'        => '',
    'ship_date'       => '',
    'note'            => '',
];

/* เปิดมาจากกระดิ่งใบ PO — เติมเลขที่อ้างอิงและชื่อผู้ติดต่อไว้ให้ล่วงหน้า */
$poId = inp_int('po', 0);
if (!is_post()) {
    foreach (['ref_no', 'contact'] as $k) {
        $v = (string)inp($k, '');
        if ($v !== '') {
            $old[$k] = $v;
        }
    }
}

if (is_post()) {
    csrf_check();
    foreach ($old as $k => $_) {
        $old[$k] = inp($k, $old[$k]);
    }

    $pids  = (array)($_POST['product_id'] ?? []);
    $qtys  = (array)($_POST['qty'] ?? []);
    $costs = (array)($_POST['unit_cost'] ?? []);
    $items = [];
    foreach ($pids as $i => $pid) {
        if ((int)$pid <= 0) {
            continue;
        }
        $qty = (string)($qtys[$i] ?? '');
        if ($qty === '' && !$isAdj) {
            continue;
        }
        $items[] = [
            'product_id' => (int)$pid,
            'qty'        => (float)str_replace(',', '', $qty),
            'unit_cost'  => (float)str_replace(',', '', (string)($costs[$i] ?? 0)),
        ];
    }

    try {
        $docId = post_document([
            'doc_type'        => $type,
            'doc_date'        => $old['doc_date'] ?: date('Y-m-d'),
            'warehouse_id'    => (int)$old['warehouse_id'],
            'supplier_id'     => (int)$old['supplier_id'] ?: null,
            'ref_no'          => $old['ref_no'],
            'contact'         => $old['contact'],
            'ship_to'         => $old['ship_to'],
            'ship_tel'        => $old['ship_tel'],
            'ship_date'       => $old['ship_date'],
            'note'            => $old['note'],
        ], $items);

        $doc = q1('SELECT doc_no FROM stock_docs WHERE id = ?', [$docId]);

        // ผูกใบรับเข้าเข้ากับใบ PO ที่เปิดมาจากกระดิ่ง แล้วถือว่าอ่านแล้ว
        if ($poId > 0) {
            try {
                q('UPDATE po_notifications SET doc_id = ?, is_read = 1, read_by = ?, read_at = NOW()
                    WHERE id = ?', [$docId, current_user()['id'] ?? null, $poId]);
            } catch (PDOException $e) { /* ยังไม่ได้อัปเกรดตาราง — ข้ามไป */ }
        }

        flash('success', 'บันทึกเอกสาร <b>' . h($doc['doc_no']) . '</b> เรียบร้อยแล้ว '
            . '<a href="' . h(url('doc_view', ['id' => $docId])) . '">ดูเอกสาร</a>',
            'บันทึกเอกสารสำเร็จ');
        redirect(url($PAGE_KEY));
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

$selWh    = (int)$old['warehouse_id'];
$products = product_options();
$jsProd   = array_map(fn($p) => [
    'id'   => (int)$p['id'],
    'sku'  => $p['sku'],
    'name' => $p['name'],
    'unit' => $p['unit_name'] ?? '',
    'cost' => (float)$p['cost_price'],
    'type' => $p['product_type'] ?? 'OTHER',
], $products);

$jsBal = [];
foreach (qa('SELECT warehouse_id, product_id, qty FROM stock_balances WHERE qty <> 0') as $b) {
    $jsBal[$b['warehouse_id'] . '_' . $b['product_id']] = (float)$b['qty'];
}

render_header($PAGE_TITLE, $PAGE_KEY);
?>
<?= error_alert($err) ?>

<form method="post" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="po" value="<?= (int)$poId ?>">

  <div class="card">
    <div class="card-head"><h2>ข้อมูลเอกสาร</h2>
      <span class="spacer"></span><?= doc_type_badge($type) ?></div>
    <div class="card-body">
      <div class="grid c3">
        <label>วันที่เอกสาร
          <input type="date" name="doc_date" value="<?= h($old['doc_date']) ?>" required>
        </label>

        <label>คลังสินค้า
          <select name="warehouse_id" id="whSel" required>
            <?php foreach ($warehouses as $w): ?>
              <option value="<?= (int)$w['id'] ?>" <?= $selWh === (int)$w['id'] ? 'selected' : '' ?>>
                <?= h($w['code'] . ' · ' . $w['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <?php if ($isIn): ?>
          <label>ผู้ขาย / ซัพพลายเออร์
            <select name="supplier_id">
              <option value="">— ไม่ระบุ —</option>
              <?php foreach (all_suppliers() as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$old['supplier_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                  <?= h($s['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>

        <label>เลขที่อ้างอิง
          <input type="text" name="ref_no" value="<?= h($old['ref_no']) ?>"
                 placeholder="<?= $isIn ? 'เลขที่ใบส่งของ / PO' : 'เลขที่ใบเบิก / อ้างอิง' ?>">
        </label>

        <?php if (!$isAdj): ?>
          <label><?= $isIn ? 'ผู้รับสินค้า' : 'ลูกค้า / ผู้เบิก' ?>
            <input type="text" name="contact" value="<?= h($old['contact']) ?>"
                   <?= $isOut ? 'placeholder="ชื่อลูกค้า — จะขึ้นบนใบส่งของ"' : '' ?>>
          </label>
        <?php endif; ?>

        <?php if ($isOut): ?>
          <label>วันที่ส่งของ
            <input type="date" name="ship_date" value="<?= h($old['ship_date']) ?>">
          </label>
          <label>โทรศัพท์ผู้รับ
            <input type="text" name="ship_tel" value="<?= h($old['ship_tel']) ?>" maxlength="50">
          </label>
        <?php endif; ?>
      </div>

      <?php if ($isOut): ?>
        <label>ที่อยู่จัดส่ง <span class="muted small">(ใช้พิมพ์ใบส่งของ)</span>
          <textarea name="ship_to" rows="2" maxlength="255"><?= h($old['ship_to']) ?></textarea>
        </label>
      <?php endif; ?>

      <label>หมายเหตุ
        <input type="text" name="note" value="<?= h($old['note']) ?>" maxlength="255">
      </label>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h2>รายการสินค้า</h2>
      <span class="spacer"></span>
      <button type="button" class="btn sm" id="addRow"><?= icon('plus') ?> เพิ่มแถว</button>
    </div>

    <div class="tablewrap">
      <table class="items">
        <thead>
          <tr>
            <th style="width:42%">สินค้า</th>
            <th style="width:16%">ยอดปัจจุบัน</th>
            <th class="num" style="width:14%"><?= $isAdj ? 'ยอดนับได้จริง' : 'จำนวน' ?></th>
            <th class="num" style="width:13%">ราคา/หน่วย</th>
            <th class="num" style="width:13%">มูลค่า</th>
            <th style="width:2%"></th>
          </tr>
        </thead>
        <tbody id="itemRows"></tbody>
      </table>
    </div>

    <div class="totalbar">
      <div><span>จำนวนแถว</span><b id="sumRows">0</b></div>
      <div><span><?= $isAdj ? 'รวมยอดนับ' : 'รวมจำนวน' ?></span><b id="sumQty">0</b></div>
      <div><span>รวมมูลค่า (บาท)</span><b id="sumAmt">0.00</b></div>
    </div>
  </div>

  <?php if ($isAdj): ?>
    <div class="alert info">
      ระบบจะเทียบ <b>ยอดนับได้จริง</b> กับยอดในระบบ แล้วบันทึกเฉพาะผลต่างเป็นการเคลื่อนไหว
      รายการที่ยอดตรงกันอยู่แล้วจะถูกข้าม
    </div>
  <?php endif; ?>

  <div class="btnrow">
    <button type="submit" class="btn primary">บันทึกเอกสาร</button>
    <a class="btn" href="<?= h(url('docs', ['type' => $type])) ?>">ดูเอกสารย้อนหลัง</a>
  </div>
</form>

<script>
PP.products = <?= json_encode($jsProd, JSON_UNESCAPED_UNICODE) ?>;
PP.balances = <?= json_encode($jsBal) ?>;
PP.initItems({ mode: '<?= h($type) ?>', warehouse: '#whSel' });
</script>
<?php render_footer(); ?>
