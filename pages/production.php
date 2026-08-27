<?php
/** ใบผลิต — ตัดวัตถุดิบ (MAT) แล้วรับสินค้าสำเร็จรูป (FG) เข้าคลังในเอกสารเดียว */
require_once __DIR__ . '/../includes/layout.php';

$warehouses = all_warehouses();
if (!$warehouses) {
    render_header('ใบผลิต', 'production');
    echo '<div class="alert warn">ยังไม่มีคลังสินค้า กรุณา<a href="' . h(url('warehouses')) . '">เพิ่มคลังสินค้า</a>ก่อน</div>';
    render_footer();
    exit;
}

$err = '';
$old = [
    'doc_date'     => date('Y-m-d'),
    'warehouse_id' => (int)$warehouses[0]['id'],
    'ref_no'       => '',
    'contact'      => '',
    'note'         => '',
];

if (is_post()) {
    csrf_check();
    foreach ($old as $k => $_) {
        $old[$k] = inp($k, $old[$k]);
    }

    $mk = function (string $pKey, string $qKey, string $cKey): array {
        $out  = [];
        $pids = (array)($_POST[$pKey] ?? []);
        $qtys = (array)($_POST[$qKey] ?? []);
        $cost = (array)($_POST[$cKey] ?? []);
        foreach ($pids as $i => $pid) {
            if ((int)$pid <= 0) {
                continue;
            }
            $qty = (float)str_replace(',', '', (string)($qtys[$i] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $out[] = [
                'product_id' => (int)$pid,
                'qty'        => $qty,
                'unit_cost'  => (float)str_replace(',', '', (string)($cost[$i] ?? 0)),
            ];
        }
        return $out;
    };

    try {
        $docId = post_document([
            'doc_type'     => 'PROD',
            'doc_date'     => $old['doc_date'] ?: date('Y-m-d'),
            'warehouse_id' => (int)$old['warehouse_id'],
            'ref_no'       => $old['ref_no'],
            'contact'      => $old['contact'],
            'note'         => $old['note'],
            'consume'      => $mk('c_product_id', 'c_qty', 'c_unit_cost'),
        ], $mk('product_id', 'qty', 'unit_cost'));

        $doc = q1('SELECT doc_no FROM stock_docs WHERE id = ?', [$docId]);
        flash('success', 'บันทึกใบผลิต <b>' . h($doc['doc_no']) . '</b> เรียบร้อยแล้ว '
            . '<a href="' . h(url('doc_view', ['id' => $docId])) . '">ดูเอกสาร</a>');
        redirect(url('production'));
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

/* ---------- ข้อมูลสำหรับฟอร์ม ---------- */
$outputs   = product_options(['FG', 'WIP']);
// เลือกวัตถุดิบได้ทุกประเภท (รองรับการประกอบชุด/kitting ที่ใช้ FG เป็นส่วนประกอบ)
$materials = product_options();

if (!$outputs) {
    render_header('ใบผลิต', 'production');
    echo '<div class="alert warn">ยังไม่มีสินค้าประเภท <b>สินค้าสำเร็จรูป (FG)</b> หรือ <b>กึ่งสำเร็จรูป (WIP)</b><br>'
       . 'ไปที่ <a href="' . h(url('products')) . '">หน้าสินค้า</a> แล้วตั้งประเภทสินค้าก่อน</div>';
    render_footer();
    exit;
}

$js = fn(array $rows) => array_map(fn($p) => [
    'id'   => (int)$p['id'],
    'sku'  => $p['sku'],
    'name' => $p['name'],
    'unit' => $p['unit_name'] ?? '',
    'cost' => (float)$p['cost_price'],
    'type' => $p['product_type'],
], $rows);

$jsBal = [];
foreach (qa('SELECT warehouse_id, product_id, qty FROM stock_balances WHERE qty <> 0') as $b) {
    $jsBal[$b['warehouse_id'] . '_' . $b['product_id']] = (float)$b['qty'];
}

$jsBom = [];
foreach (qa('SELECT fg_product_id, material_id, qty FROM bom_items') as $b) {
    $jsBom[(int)$b['fg_product_id']][] = ['id' => (int)$b['material_id'], 'qty' => (float)$b['qty']];
}

$preFg = inp_int('fg', 0);

render_header('ใบผลิต — แปลงวัตถุดิบเป็นสินค้าสำเร็จรูป', 'production');
?>
<?= error_alert($err) ?>

<?php if (!$jsBom): ?>
  <div class="alert info">ยังไม่ได้ตั้ง<b>สูตรการผลิต</b> — ตั้งที่หน้า
    <a href="<?= h(url('bom')) ?>">สูตรการผลิต (BOM)</a> แล้วระบบจะเติมวัตถุดิบให้อัตโนมัติ
    (ตอนนี้กรอกวัตถุดิบเองได้)</div>
<?php endif; ?>

<form method="post" autocomplete="off">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head"><h2>ข้อมูลใบผลิต</h2><span class="spacer"></span><?= doc_type_badge('PROD') ?></div>
    <div class="card-body">
      <div class="grid c3">
        <label>วันที่ผลิต
          <input type="date" name="doc_date" value="<?= h($old['doc_date']) ?>" required>
        </label>
        <label>คลังที่ใช้ผลิต / เก็บของ
          <select name="warehouse_id" id="whSel" required>
            <?php foreach ($warehouses as $w): ?>
              <option value="<?= (int)$w['id'] ?>" <?= (int)$old['warehouse_id'] === (int)$w['id'] ? 'selected' : '' ?>>
                <?= h($w['code'] . ' · ' . $w['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>เลขที่ใบสั่งผลิต
          <input type="text" name="ref_no" value="<?= h($old['ref_no']) ?>" placeholder="เช่น WO-2026-001">
        </label>
        <label>ผู้ควบคุมการผลิต
          <input type="text" name="contact" value="<?= h($old['contact']) ?>">
        </label>
      </div>
      <label>หมายเหตุ
        <input type="text" name="note" value="<?= h($old['note']) ?>" maxlength="255">
      </label>
      <div class="alert info" style="margin-bottom:0">
        วัตถุดิบที่ตัดออกและสินค้าสำเร็จรูปที่รับเข้า จะอยู่ใน<b>คลังเดียวกัน</b>ตามที่เลือกไว้ด้านบน
      </div>
    </div>
  </div>

  <!-- ================= ผลผลิต (FG) ================= -->
  <div class="card">
    <div class="card-head">
      <h2>① สินค้าที่ผลิตได้ <span class="muted small">(FG / WIP → เพิ่มเข้าสต๊อก)</span></h2>
      <span class="spacer"></span>
      <button type="button" class="btn sm" id="addOut"><?= icon('plus') ?> เพิ่มแถว</button>
    </div>
    <div class="tablewrap">
      <table class="items">
        <thead><tr>
          <th style="width:42%">สินค้าสำเร็จรูป</th><th style="width:16%">ยอดปัจจุบัน</th>
          <th class="num" style="width:14%">ผลิตได้</th>
          <th class="num" style="width:13%">ต้นทุน/หน่วย</th>
          <th class="num" style="width:13%">มูลค่า</th><th style="width:2%"></th>
        </tr></thead>
        <tbody id="outRows"></tbody>
      </table>
    </div>
    <div class="totalbar">
      <div><span>รายการ</span><b id="outRowsN">0</b></div>
      <div><span>ผลิตได้รวม</span><b id="outQty">0</b></div>
      <div><span>มูลค่าผลผลิต</span><b id="outAmt">0.00</b></div>
    </div>
  </div>

  <div class="btnrow" style="justify-content:center;margin:-6px 0 20px">
    <button type="button" class="btn primary" id="explodeBom">
      <?= icon('bom') ?> ดึงสูตรการผลิต → เติมวัตถุดิบอัตโนมัติ
    </button>
    <span class="small muted" id="bomInfo"></span>
  </div>

  <!-- ================= วัตถุดิบที่ใช้ (MAT) ================= -->
  <div class="card">
    <div class="card-head">
      <h2>② วัตถุดิบที่ใช้ไป <span class="muted small">(MAT / PACK → ตัดออกจากสต๊อก)</span></h2>
      <span class="spacer"></span>
      <button type="button" class="btn sm" id="addMat"><?= icon('plus') ?> เพิ่มแถว</button>
    </div>
    <div class="tablewrap">
      <table class="items">
        <thead><tr>
          <th style="width:42%">วัตถุดิบ</th><th style="width:16%">ยอดปัจจุบัน</th>
          <th class="num" style="width:14%">ใช้ไป</th>
          <th class="num" style="width:13%">ต้นทุน/หน่วย</th>
          <th class="num" style="width:13%">มูลค่า</th><th style="width:2%"></th>
        </tr></thead>
        <tbody id="matRows"></tbody>
      </table>
    </div>
    <div class="totalbar">
      <div><span>รายการ</span><b id="matRowsN">0</b></div>
      <div><span>ใช้ไปรวม</span><b id="matQty">0</b></div>
      <div><span>ต้นทุนวัตถุดิบ</span><b id="matAmt">0.00</b></div>
    </div>
  </div>

  <div class="card">
    <div class="card-body" style="display:flex;gap:28px;flex-wrap:wrap;justify-content:space-between;align-items:center">
      <div>
        <div class="small muted" style="text-transform:uppercase;letter-spacing:.12em">ต้นทุนต่อหน่วยที่ระบบจะคำนวณให้</div>
        <div style="font-family:var(--serif);font-size:1.7rem;font-weight:600" id="unitCostOut">—</div>
        <div class="small muted">ต้นทุนวัตถุดิบรวม ÷ จำนวนที่ผลิตได้ — ถ้ากรอกต้นทุน/หน่วยเองในตาราง ① ระบบจะใช้ค่าที่กรอก</div>
      </div>
      <div class="btnrow" style="margin:0">
        <button type="submit" class="btn primary">บันทึกใบผลิต</button>
        <a class="btn" href="<?= h(url('docs', ['type' => 'PROD'])) ?>">ใบผลิตย้อนหลัง</a>
        <a class="btn ghost" href="<?= h(url('bom')) ?>">จัดการสูตรการผลิต</a>
      </div>
    </div>
  </div>
</form>

<script>
PP.balances = <?= json_encode($jsBal) ?>;
PP.boms     = <?= json_encode($jsBom) ?>;

var OUT_PRODUCTS = <?= json_encode($js($outputs), JSON_UNESCAPED_UNICODE) ?>;
var MAT_PRODUCTS = <?= json_encode($js($materials), JSON_UNESCAPED_UNICODE) ?>;

var outTable = PP.initItems({
  tbody: '#outRows', addBtn: '#addOut', warehouse: '#whSel',
  mode: 'PROD_IN', products: OUT_PRODUCTS,
  names:  { product: 'product_id[]', qty: 'qty[]', cost: 'unit_cost[]' },
  totals: { rows: '#outRowsN', qty: '#outQty', amt: '#outAmt' },
  rows: [<?= $preFg ? '{product_id:' . (int)$preFg . '}' : 'null' ?>],
  onChange: refreshCost
});

var matTable = PP.initItems({
  tbody: '#matRows', addBtn: '#addMat', warehouse: '#whSel',
  mode: 'PROD_OUT', products: MAT_PRODUCTS, validate: false,
  names:  { product: 'c_product_id[]', qty: 'c_qty[]', cost: 'c_unit_cost[]' },
  totals: { rows: '#matRowsN', qty: '#matQty', amt: '#matAmt' },
  rows: [null, null],
  onChange: refreshCost
});

function num(sel) { return parseFloat((document.querySelector(sel).textContent || '0').replace(/,/g, '')) || 0; }

function refreshCost() {
  var q = num('#outQty'), c = num('#matAmt');
  document.getElementById('unitCostOut').textContent =
    q > 0 ? (c / q).toFixed(2) + ' บาท / หน่วย' : '—';
}

/* ดึงสูตรการผลิตของทุกแถวในตารางผลผลิต มาเติมตารางวัตถุดิบ */
document.getElementById('explodeBom').addEventListener('click', function () {
  var need = {}, missing = [], used = 0;

  outTable.tbody.querySelectorAll('tr').forEach(function (tr) {
    var pid = tr.querySelector('.p-sel').value;
    var qty = parseFloat(tr.querySelector('.p-qty').value) || 0;
    if (!pid || qty <= 0) return;
    var bom = PP.boms[pid];
    if (!bom || !bom.length) {
      var opt = tr.querySelector('.p-sel').selectedOptions[0];
      missing.push(opt ? opt.textContent.trim() : pid);
      return;
    }
    used++;
    bom.forEach(function (b) {
      need[b.id] = (need[b.id] || 0) + b.qty * qty;
    });
  });

  var rows = Object.keys(need).map(function (id) {
    return { product_id: id, qty: PP.fmtQty(need[id]) };
  });

  if (!rows.length) {
    document.getElementById('bomInfo').textContent =
      'ยังไม่พบสูตรการผลิต — กรอกวัตถุดิบเองด้านล่างได้เลย';
    return;
  }

  matTable.setRows(rows);
  document.getElementById('bomInfo').textContent =
    'เติมวัตถุดิบ ' + rows.length + ' รายการ จาก ' + used + ' สูตร' +
    (missing.length ? ' · ไม่มีสูตร: ' + missing.join(', ') : '');
  refreshCost();
});

refreshCost();
</script>
<?php render_footer(); ?>
