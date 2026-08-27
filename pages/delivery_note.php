<?php
/**
 * ใบส่งของ — เลย์เอาต์ A4 สำหรับพิมพ์ / บันทึกเป็น PDF
 * ใช้กับเอกสารเบิก-จ่ายออก (OUT) เป็นหลัก แต่เปิดกับเอกสารชนิดอื่นได้
 * ?id=<doc_id>  &copies=2  (ต้นฉบับ/สำเนา)   &xls=1 (ส่งออก Excel)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id  = inp_int('id', 0);
$doc = q1(
    'SELECT d.*, w.code AS wh_code, w.name AS wh_name, s.name AS supp_name, u.fullname
       FROM stock_docs d
       JOIN warehouses w ON w.id = d.warehouse_id
       LEFT JOIN suppliers s ON s.id = d.supplier_id
       LEFT JOIN users u ON u.id = d.user_id
      WHERE d.id = ?', [$id]);

if (!$doc) {
    http_response_code(404);
    exit('ไม่พบเอกสาร');
}

$items = qa(
    "SELECT i.*, p.sku, p.name, un.name AS unit_name, p.sell_price
       FROM stock_doc_items i
       JOIN products p ON p.id = i.product_id
       LEFT JOIN units un ON un.id = p.unit_id
      WHERE i.doc_id = ? AND i.line_kind = 'MAIN'
      ORDER BY i.id", [$id]);

/* ราคาบนใบส่งของใช้ "ราคาขาย" ถ้ามี ไม่งั้นใช้ต้นทุนที่บันทึกไว้ */
$lines = [];
$total = 0.0;
foreach ($items as $it) {
    $price = (float)$it['sell_price'] > 0 ? (float)$it['sell_price'] : (float)$it['unit_cost'];
    $amt   = (float)$it['qty'] * $price;
    $total += $amt;
    $lines[] = $it + ['price' => $price, 'amount' => $amt];
}

/* ---------- ส่งออก Excel ---------- */
if (inp('xls') !== '') {
    $rows = [];
    $i = 0;
    foreach ($lines as $l) {
        $rows[] = [++$i, $l['sku'], $l['name'], qty_fmt($l['qty']), $l['unit_name'],
                   number_format($l['price'], 2), number_format($l['amount'], 2)];
    }
    $rows[] = ['', '', '', '', '', 'รวมเงิน', number_format($total, 2)];
    xls_download(
        'delivery_' . $doc['doc_no'] . '.xls',
        'ใบส่งของ เลขที่ ' . $doc['doc_no'],
        ['ลำดับ', 'รหัสสินค้า', 'รายการ', 'จำนวน', 'หน่วย', 'ราคา/หน่วย', 'จำนวนเงิน'],
        $rows,
        [
            'ลูกค้า'    => $doc['contact'] ?: '-',
            'ที่อยู่จัดส่ง' => $doc['ship_to'] ?: '-',
            'วันที่'     => thai_date($doc['ship_date'] ?: $doc['doc_date']),
            'อ้างอิง'    => $doc['ref_no'] ?: '-',
        ]
    );
}

$copies = max(1, min(3, inp_int('copies', 2)));
$labels = ['ต้นฉบับ (ลูกค้า)', 'สำเนา (บริษัท)', 'สำเนา (คลัง)'];
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ใบส่งของ <?= h($doc['doc_no']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#e9e8f0;font-family:"Sarabun","Leelawadee UI","Segoe UI",Tahoma,sans-serif;
       font-size:13px;color:#111;line-height:1.5}
  .toolbar{position:sticky;top:0;z-index:10;background:#1d1740;color:#ddd8f2;padding:12px 20px;
           display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  .toolbar b{color:#fff;margin-right:auto}
  .toolbar a,.toolbar button{font:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;
      padding:7px 14px;border-radius:7px;border:1px solid rgba(255,255,255,.25);
      background:transparent;color:#ddd8f2}
  .toolbar a:hover,.toolbar button:hover{background:rgba(255,255,255,.1)}
  .toolbar .go{background:linear-gradient(165deg,#ffdf7d,#e8bd3f 45%,#b98a12);color:#231a05;border-color:#b98a12}

  .sheet{width:210mm;min-height:297mm;margin:18px auto;background:#fff;padding:14mm 14mm 12mm;
         box-shadow:0 10px 30px -14px rgba(0,0,0,.4);position:relative}
  .cotitle{position:absolute;top:14mm;right:14mm;text-align:right}
  .cotitle h1{margin:0;font-size:20px;letter-spacing:.04em}
  .cotitle .sub{font-size:12px;color:#555}
  .co h2{margin:0 0 2px;font-size:16px}
  .co p{margin:0;font-size:12px;color:#444}
  .rule{border:0;border-top:2px solid #1d1740;margin:10px 0 12px}

  .meta{display:flex;gap:18px;margin-bottom:10px}
  .meta .box{flex:1;border:1px solid #bbb;border-radius:4px;padding:8px 10px;min-height:74px}
  .meta .box .lb{font-size:11px;color:#666;letter-spacing:.05em}
  .meta .box b{font-size:13px}
  .meta .side{width:62mm;flex:none}
  .kv{display:flex;justify-content:space-between;gap:8px;font-size:12px;padding:1px 0}
  .kv span:first-child{color:#666}

  table.items{width:100%;border-collapse:collapse;margin-top:4px}
  table.items th{background:#1d1740;color:#fff;font-size:11.5px;font-weight:600;padding:7px 8px;
                 border:1px solid #1d1740;letter-spacing:.03em}
  table.items td{border:1px solid #ccc;padding:6px 8px;vertical-align:top}
  table.items td.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
  table.items td.c{text-align:center;color:#666}
  table.items tr.blank td{height:26px}
  .totrow td{border:1px solid #ccc;padding:7px 8px;font-weight:700;background:#f6f4ee}
  .baht{font-size:12px;background:#f6f4ee;border:1px dashed #b98a12;border-radius:4px;padding:6px 10px;margin-top:8px}

  .signs{display:flex;gap:14px;margin-top:26px}
  .sign{flex:1;text-align:center;font-size:12px}
  .sign .line{border-bottom:1px dotted #666;height:40px;margin-bottom:6px}
  .sign .sub{color:#666;font-size:11px}
  .foot{margin-top:14px;font-size:10.5px;color:#777;display:flex;justify-content:space-between}
  .stamp{position:absolute;top:14mm;left:50%;transform:translateX(-50%);
         border:2px solid #b98a12;color:#b98a12;border-radius:4px;padding:2px 12px;font-size:11px;font-weight:700}
  .void{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none}
  .void span{font-size:80px;font-weight:800;color:rgba(217,37,70,.14);transform:rotate(-22deg);letter-spacing:.1em}

  @media print{
    body{background:#fff}
    .toolbar{display:none}
    .sheet{margin:0;box-shadow:none;width:auto;min-height:auto;padding:10mm}
    .sheet + .sheet{page-break-before:always}
    @page{size:A4;margin:0}
  }
</style>
</head>
<body>

<div class="toolbar">
  <b>ใบส่งของ <?= h($doc['doc_no']) ?></b>
  <a href="<?= h(url('doc_view', ['id' => $id])) ?>">← กลับหน้าเอกสาร</a>
  <a href="<?= h(url('delivery_note', ['id' => $id, 'copies' => 1])) ?>">1 ฉบับ</a>
  <a href="<?= h(url('delivery_note', ['id' => $id, 'copies' => 2])) ?>">2 ฉบับ</a>
  <a href="<?= h(url('delivery_note', ['id' => $id, 'xls' => 1])) ?>">ดาวน์โหลด Excel</a>
  <button class="go" onclick="window.print()">พิมพ์ / บันทึก PDF</button>
</div>

<?php for ($c = 0; $c < $copies; $c++): ?>
<div class="sheet">
  <?php if ($doc['status'] === 'void'): ?>
    <div class="void"><span>ยกเลิก</span></div>
  <?php endif; ?>

  <div class="cotitle">
    <h1>ใบส่งของ</h1>
    <div class="sub">DELIVERY NOTE</div>
  </div>
  <div class="co">
    <h2><?= h(COMPANY_NAME) ?></h2>
    <p><?= h(COMPANY_ADDRESS) ?></p>
    <p><?= h(COMPANY_TEL) ?> · <?= h(COMPANY_TAXID) ?></p>
  </div>
  <?php if ($copies > 1): ?>
    <div class="stamp"><?= h($labels[$c] ?? 'สำเนา') ?></div>
  <?php endif; ?>
  <hr class="rule">

  <div class="meta">
    <div class="box">
      <div class="lb">ส่งถึง / DELIVER TO</div>
      <b><?= h($doc['contact'] ?: '—') ?></b>
      <div><?= nl2br(h($doc['ship_to'] ?: '')) ?></div>
      <?php if ($doc['ship_tel']): ?><div>โทร. <?= h($doc['ship_tel']) ?></div><?php endif; ?>
    </div>
    <div class="box side">
      <div class="kv"><span>เลขที่</span><b><?= h($doc['doc_no']) ?></b></div>
      <div class="kv"><span>วันที่ส่ง</span><b><?= h(thai_date($doc['ship_date'] ?: $doc['doc_date'])) ?></b></div>
      <div class="kv"><span>อ้างอิง</span><b><?= h($doc['ref_no'] ?: '—') ?></b></div>
      <div class="kv"><span>คลังที่จ่าย</span><b><?= h($doc['wh_code']) ?></b></div>
    </div>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th style="width:34px">ลำดับ</th>
        <th style="width:78px">รหัส</th>
        <th>รายการ</th>
        <th style="width:60px">จำนวน</th>
        <th style="width:52px">หน่วย</th>
        <th style="width:70px">ราคา/หน่วย</th>
        <th style="width:82px">จำนวนเงิน</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach ($lines as $l): $i++; ?>
        <tr>
          <td class="c"><?= $i ?></td>
          <td><?= h($l['sku']) ?></td>
          <td><?= h($l['name']) ?><?= $l['note'] ? '<br><small style="color:#666">' . h($l['note']) . '</small>' : '' ?></td>
          <td class="n"><?= qty_fmt($l['qty']) ?></td>
          <td class="c"><?= h($l['unit_name'] ?? '') ?></td>
          <td class="n"><?= money($l['price']) ?></td>
          <td class="n"><?= money($l['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php for ($b = $i; $b < 8; $b++): ?>
        <tr class="blank"><td class="c"><?= $b + 1 ?></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
      <?php endfor; ?>
      <tr class="totrow">
        <td colspan="6" style="text-align:right">รวมเป็นเงินทั้งสิ้น</td>
        <td class="n"><?= money($total) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="baht">( <?= h(baht_text($total)) ?> )</div>

  <?php if ($doc['note']): ?>
    <div style="margin-top:8px;font-size:12px"><b>หมายเหตุ:</b> <?= h($doc['note']) ?></div>
  <?php endif; ?>

  <div class="signs">
    <div class="sign">
      <div class="line"></div>
      ผู้ส่งของ<div class="sub">วันที่ ........./........./.........</div>
    </div>
    <div class="sign">
      <div class="line"></div>
      ผู้ตรวจสอบ<div class="sub">วันที่ ........./........./.........</div>
    </div>
    <div class="sign">
      <div class="line"></div>
      ผู้รับสินค้า<div class="sub">วันที่ ........./........./.........</div>
    </div>
  </div>

  <div class="foot">
    <span>ผู้บันทึก: <?= h($doc['fullname'] ?? '—') ?></span>
    <span>ได้รับสินค้าตามรายการข้างต้นไว้ถูกต้องครบถ้วนแล้ว</span>
  </div>
</div>
<?php endfor; ?>

</body>
</html>
