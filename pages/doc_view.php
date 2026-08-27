<?php
require_once __DIR__ . '/../includes/layout.php';

$id  = inp_int('id', 0);
$doc = q1(
    'SELECT d.*, w.code AS wh_code, w.name AS wh_name, w2.name AS wh2_name, w2.code AS wh2_code,
            s.name AS supp_name, s.phone AS supp_phone, u.fullname, uv.fullname AS void_by_name
       FROM stock_docs d
       JOIN warehouses w ON w.id = d.warehouse_id
       LEFT JOIN warehouses w2 ON w2.id = d.to_warehouse_id
       LEFT JOIN suppliers s ON s.id = d.supplier_id
       LEFT JOIN users u ON u.id = d.user_id
       LEFT JOIN users uv ON uv.id = d.voided_by
      WHERE d.id = ?', [$id]);

if (!$doc) {
    flash('danger', 'ไม่พบเอกสารที่ต้องการ');
    redirect(url('docs'));
}

/* ---- ยกเลิกเอกสาร ---- */
if (is_post() && inp('action') === 'void') {
    csrf_check();
    if (!can('admin')) {
        flash('danger', 'เฉพาะผู้ดูแลระบบเท่านั้นที่ยกเลิกเอกสารได้');
    } else {
        try {
            void_document($id);
            flash('success', 'ยกเลิกเอกสาร ' . h($doc['doc_no']) . ' และคืนยอดสต๊อกเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            flash('danger', h($e->getMessage()));
        }
    }
    redirect(url('doc_view', ['id' => $id]));
}

$allItems = qa(
    'SELECT i.*, p.sku, p.name, p.product_type, un.name AS unit_name
       FROM stock_doc_items i
       JOIN products p ON p.id = i.product_id
       LEFT JOIN units un ON un.id = p.unit_id
      WHERE i.doc_id = ? ORDER BY i.line_kind DESC, i.id', [$id]);

$items   = array_values(array_filter($allItems, fn($r) => ($r['line_kind'] ?? 'MAIN') === 'MAIN'));
$consume = array_values(array_filter($allItems, fn($r) => ($r['line_kind'] ?? 'MAIN') === 'CONSUME'));

$consumeQty = $consumeAmt = 0.0;
foreach ($consume as $c) {
    $consumeQty += (float)$c['qty'];
    $consumeAmt += (float)$c['qty'] * (float)$c['unit_cost'];
}

$moves = qa(
    'SELECT m.*, p.sku, p.name, w.code AS wh_code
       FROM stock_movements m
       JOIN products p ON p.id = m.product_id
       JOIN warehouses w ON w.id = m.warehouse_id
      WHERE m.doc_id = ? ORDER BY m.id', [$id]);

$isVoid = $doc['status'] === 'void';
$isAdj  = $doc['doc_type'] === 'ADJUST';
$isProd = $doc['doc_type'] === 'PROD';

/* ---- ส่งออก Excel ---- */
if (inp('xls') !== '') {
    $rows = [];
    $n = 0;
    foreach ($items as $it) {
        $rows[] = [++$n, $it['sku'], $it['name'], qty_fmt($it['qty']), $it['unit_name'],
                   number_format((float)$it['unit_cost'], 2),
                   number_format((float)$it['qty'] * (float)$it['unit_cost'], 2), 'ผลผลิต/รายการหลัก'];
    }
    foreach ($consume as $c) {
        $rows[] = [++$n, $c['sku'], $c['name'], qty_fmt($c['qty']), $c['unit_name'],
                   number_format((float)$c['unit_cost'], 2),
                   number_format((float)$c['qty'] * (float)$c['unit_cost'], 2), 'วัตถุดิบที่ใช้'];
    }
    xls_download(
        $doc['doc_no'] . '.xls',
        doc_type_label($doc['doc_type']) . ' เลขที่ ' . $doc['doc_no'],
        ['ลำดับ', 'รหัสสินค้า', 'รายการ', 'จำนวน', 'หน่วย', 'ราคา/หน่วย', 'มูลค่า', 'ประเภทบรรทัด'],
        $rows,
        [
            'วันที่'   => thai_date($doc['doc_date']),
            'คลัง'    => $doc['wh_code'] . ' · ' . $doc['wh_name'],
            'อ้างอิง'  => $doc['ref_no'] ?: '-',
            'ผู้เกี่ยวข้อง' => $doc['contact'] ?: '-',
            'สถานะ'   => $isVoid ? 'ยกเลิกแล้ว' : 'บันทึกแล้ว',
        ]
    );
}

render_header('เอกสาร ' . $doc['doc_no'], 'docs');
?>
<?php if ($isVoid): ?>
  <div class="alert danger">เอกสารนี้ถูก<b>ยกเลิก</b>แล้วเมื่อ <?= h(thai_date($doc['voided_at'], true)) ?>
    โดย <?= h($doc['void_by_name'] ?? '-') ?> — ยอดสต๊อกถูกคืนกลับเรียบร้อย</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <h2><?= h(doc_type_label($doc['doc_type'])) ?> · <code><?= h($doc['doc_no']) ?></code></h2>
    <?= doc_type_badge($doc['doc_type']) ?>
    <?= $isVoid ? '<span class="badge danger">ยกเลิกแล้ว</span>' : '<span class="badge ok">บันทึกแล้ว</span>' ?>
    <span class="spacer"></span>
    <div class="btnrow noprint">
      <?php if ($doc['doc_type'] === 'OUT'): ?>
        <a class="btn sm primary" href="<?= h(url('delivery_note', ['id' => $id])) ?>" target="_blank">
          <?= icon('docs') ?> ใบส่งของ / PDF</a>
      <?php endif; ?>
      <a class="btn sm" href="<?= h(url('doc_view', ['id' => $id, 'xls' => 1])) ?>">Excel</a>
      <button class="btn sm" onclick="window.print()">พิมพ์</button>
      <?php if (!$isVoid && can('admin')): ?>
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="void">
          <button class="btn sm danger"
                  data-confirm="ยกเลิกเอกสาร <?= h($doc['doc_no']) ?> และคืนยอดสต๊อกทั้งหมด?">ยกเลิกเอกสาร</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">
    <div class="grid c4" style="gap:14px">
      <div><span class="small muted">วันที่เอกสาร</span><div><b><?= h(thai_date($doc['doc_date'])) ?></b></div></div>
      <div><span class="small muted">คลังสินค้า</span>
           <div><b><?= h($doc['wh_code'] . ' · ' . $doc['wh_name']) ?></b></div></div>
      <?php if ($doc['wh2_name']): ?>
        <div><span class="small muted">คลังปลายทาง</span>
             <div><b><?= h($doc['wh2_code'] . ' · ' . $doc['wh2_name']) ?></b></div></div>
      <?php endif; ?>
      <?php if ($doc['supp_name']): ?>
        <div><span class="small muted">ผู้ขาย</span><div><b><?= h($doc['supp_name']) ?></b></div></div>
      <?php endif; ?>
      <?php if ($doc['ref_no']): ?>
        <div><span class="small muted">เลขที่อ้างอิง</span><div><b><?= h($doc['ref_no']) ?></b></div></div>
      <?php endif; ?>
      <?php if ($doc['contact']): ?>
        <div><span class="small muted">ผู้เกี่ยวข้อง</span><div><b><?= h($doc['contact']) ?></b></div></div>
      <?php endif; ?>
      <div><span class="small muted">ผู้บันทึก</span><div><b><?= h($doc['fullname'] ?? '—') ?></b>
           <span class="small muted"><?= h(thai_date($doc['created_at'], true)) ?></span></div></div>
    </div>
    <?php if ($doc['note']): ?>
      <div style="margin-top:12px"><span class="small muted">หมายเหตุ</span><div><?= h($doc['note']) ?></div></div>
    <?php endif; ?>
  </div>

  <?php if ($isProd): ?>
    <div class="card-head" style="border-top:1px solid var(--line-2)">
      <h3 style="margin:0">① สินค้าที่ผลิตได้</h3>
      <span class="badge ok">เพิ่มเข้าสต๊อก</span>
    </div>
  <?php endif; ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead>
        <tr><th style="width:40px">#</th><th>สินค้า</th>
            <th class="num"><?= $isAdj ? 'ยอดนับได้' : ($isProd ? 'ผลิตได้' : 'จำนวน') ?></th>
            <th class="num">ราคา/หน่วย</th><th class="num">มูลค่า</th></tr>
      </thead>
      <tbody>
      <?php $i = 0; foreach ($items as $it): $i++; ?>
        <tr>
          <td class="muted"><?= $i ?></td>
          <td><a href="<?= h(url('stock_card', ['id' => $it['product_id']])) ?>"><?= h($it['name']) ?></a>
              <div class="small muted"><?= h($it['sku']) ?></div></td>
          <td class="num"><?= qty_fmt($it['qty']) ?> <span class="muted small"><?= h($it['unit_name'] ?? '') ?></span></td>
          <td class="num"><?= money($it['unit_cost']) ?></td>
          <td class="num"><?= money((float)$it['qty'] * (float)$it['unit_cost']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="2">รวม <?= count($items) ?> รายการ</td>
            <td class="num"><?= qty_fmt($doc['total_qty']) ?></td>
            <td></td><td class="num"><?= money($doc['total_amount']) ?></td></tr>
      </tfoot>
    </table>
  </div>

  <?php if ($isProd): ?>
    <div class="card-head" style="border-top:1px solid var(--line-2)">
      <h3 style="margin:0">② วัตถุดิบที่ใช้ไป</h3>
      <span class="badge warn">ตัดออกจากสต๊อก</span>
      <span class="spacer"></span>
      <span class="small muted">ต้นทุนวัตถุดิบรวม <b><?= money($consumeAmt) ?></b> บาท</span>
    </div>
    <?php if (!$consume): ?>
      <div class="empty">ไม่มีการบันทึกวัตถุดิบในเอกสารนี้</div>
    <?php else: ?>
    <div class="tablewrap">
      <table class="tbl">
        <thead><tr><th style="width:40px">#</th><th>วัตถุดิบ</th><th>ประเภท</th>
                   <th class="num">ใช้ไป</th><th class="num">ต้นทุน/หน่วย</th><th class="num">มูลค่า</th></tr></thead>
        <tbody>
        <?php $j = 0; foreach ($consume as $c): $j++; ?>
          <tr>
            <td class="muted"><?= $j ?></td>
            <td><a href="<?= h(url('stock_card', ['id' => $c['product_id']])) ?>"><?= h($c['name']) ?></a>
                <div class="small muted"><?= h($c['sku']) ?></div></td>
            <td><?= product_type_badge($c['product_type']) ?></td>
            <td class="num"><?= qty_fmt($c['qty']) ?> <span class="muted small"><?= h($c['unit_name'] ?? '') ?></span></td>
            <td class="num"><?= money($c['unit_cost']) ?></td>
            <td class="num"><?= money((float)$c['qty'] * (float)$c['unit_cost']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3">รวม <?= count($consume) ?> รายการ</td>
              <td class="num"><?= qty_fmt($consumeQty) ?></td>
              <td></td><td class="num"><?= money($consumeAmt) ?></td></tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>

    <div class="totalbar">
      <div><span>ต้นทุนวัตถุดิบ</span><b><?= money($consumeAmt) ?></b></div>
      <div><span>มูลค่าผลผลิต</span><b><?= money($doc['total_amount']) ?></b></div>
      <div><span>ส่วนต่าง</span><b style="color:<?= (float)$doc['total_amount'] - $consumeAmt < 0 ? 'var(--danger)' : 'var(--ok)' ?>">
        <?= money((float)$doc['total_amount'] - $consumeAmt) ?></b></div>
    </div>
  <?php endif; ?>
</div>

<div class="card noprint">
  <div class="card-head"><h2>ผลกระทบต่อสต๊อก</h2></div>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>สินค้า</th><th>คลัง</th><th class="num">เปลี่ยนแปลง</th>
                 <th class="num">คงเหลือหลังทำรายการ</th><th>บันทึก</th><th>เวลา</th></tr></thead>
      <tbody>
      <?php foreach ($moves as $m): $d = (float)$m['qty_change']; ?>
        <tr>
          <td><?= h($m['name']) ?><div class="small muted"><?= h($m['sku']) ?></div></td>
          <td class="small"><?= h($m['wh_code']) ?></td>
          <td class="num"><span class="badge <?= $d >= 0 ? 'ok' : 'warn' ?>">
              <?= $d >= 0 ? '+' : '−' ?><?= qty_fmt(abs($d)) ?></span></td>
          <td class="num"><?= qty_fmt($m['balance_after']) ?></td>
          <td class="small muted"><?= h($m['note'] ?? '') ?></td>
          <td class="small muted nowrap"><?= h(date('d/m H:i', strtotime($m['moved_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="btnrow noprint">
  <a class="btn" href="<?= h(url('docs', ['type' => $doc['doc_type']])) ?>">← กลับรายการเอกสาร</a>
</div>
<?php render_footer(); ?>
