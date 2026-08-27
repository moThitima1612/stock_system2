<?php
require_once __DIR__ . '/../includes/layout.php';

$type = (string)inp('type');
$whId = inp_int('wh');
$from = (string)inp('from');
$to   = (string)inp('to');
$kw   = (string)inp('kw');
$page = max(1, inp_int('page', 1));
$per  = ROWS_PER_PAGE;

$where = [];
$args  = [];
if (isset(DOC_PREFIX[$type])) { $where[] = 'd.doc_type = ?'; $args[] = $type; }
if ($whId) { $where[] = '(d.warehouse_id = ? OR d.to_warehouse_id = ?)'; $args[] = $whId; $args[] = $whId; }
if ($from) { $where[] = 'd.doc_date >= ?'; $args[] = $from; }
if ($to)   { $where[] = 'd.doc_date <= ?'; $args[] = $to; }
if ($kw !== '') {
    $where[] = '(d.doc_no LIKE ? OR d.ref_no LIKE ? OR d.contact LIKE ? OR d.note LIKE ?)';
    array_push($args, "%$kw%", "%$kw%", "%$kw%", "%$kw%");
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)qv("SELECT COUNT(*) FROM stock_docs d $w", $args);
$rows  = qa(
    "SELECT d.*, w.name AS wh_name, w2.name AS wh2_name, s.name AS supp_name, u.fullname,
            (SELECT COUNT(*) FROM stock_doc_items i WHERE i.doc_id = d.id) AS n_items
       FROM stock_docs d
       JOIN warehouses w ON w.id = d.warehouse_id
       LEFT JOIN warehouses w2 ON w2.id = d.to_warehouse_id
       LEFT JOIN suppliers s ON s.id = d.supplier_id
       LEFT JOIN users u ON u.id = d.user_id
       $w
      ORDER BY d.doc_date DESC, d.id DESC
      LIMIT $per OFFSET " . (($page - 1) * $per),
    $args
);

render_header('เอกสารสต๊อก', 'docs');
?>
<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="docs">
    <label>ประเภท
      <select name="type">
        <option value="">ทุกประเภท</option>
        <?php foreach (DOC_PREFIX as $t => $_): ?>
          <option value="<?= h($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= h(doc_type_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>คลัง
      <select name="wh">
        <option value="">ทุกคลัง</option>
        <?php foreach (all_warehouses(false) as $wh): ?>
          <option value="<?= (int)$wh['id'] ?>" <?= $whId === (int)$wh['id'] ? 'selected' : '' ?>>
            <?= h($wh['code'] . ' · ' . $wh['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ตั้งแต่ <input type="date" name="from" value="<?= h($from) ?>"></label>
    <label>ถึง <input type="date" name="to" value="<?= h($to) ?>"></label>
    <label class="grow">ค้นหา
      <input type="search" name="kw" value="<?= h($kw) ?>" placeholder="เลขที่เอกสาร / อ้างอิง / ผู้เบิก">
    </label>
    <button class="btn primary"><?= icon('search') ?> ค้นหา</button>
    <a class="btn ghost" href="<?= h(url('docs')) ?>">ล้าง</a>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">ไม่พบเอกสารตามเงื่อนไข</div>
  <?php else: ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead>
        <tr><th>เลขที่</th><th>ประเภท</th><th>วันที่</th><th>คลัง</th><th>อ้างอิง</th>
            <th class="num">รายการ</th><th class="num">จำนวน</th><th class="num">มูลค่า</th>
            <th>ผู้บันทึก</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $d): $void = $d['status'] === 'void'; ?>
        <tr<?= $void ? ' style="opacity:.5"' : '' ?>>
          <td class="nowrap"><a href="<?= h(url('doc_view', ['id' => $d['id']])) ?>"><code><?= h($d['doc_no']) ?></code></a></td>
          <td><?= doc_type_badge($d['doc_type']) ?><?= $void ? ' <span class="badge danger">ยกเลิก</span>' : '' ?></td>
          <td class="nowrap small"><?= h(thai_date($d['doc_date'])) ?></td>
          <td class="small"><?= h($d['wh_name']) ?><?= $d['wh2_name'] ? ' → ' . h($d['wh2_name']) : '' ?></td>
          <td class="small"><?= h($d['ref_no'] ?: ($d['supp_name'] ?: ($d['contact'] ?: '—'))) ?></td>
          <td class="num"><?= (int)$d['n_items'] ?></td>
          <td class="num"><?= qty_fmt($d['total_qty']) ?></td>
          <td class="num"><?= money($d['total_amount']) ?></td>
          <td class="small muted"><?= h($d['fullname'] ?? '—') ?></td>
          <td class="right"><a class="btn sm ghost" href="<?= h(url('doc_view', ['id' => $d['id']])) ?>">ดู</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginate_links($total, $page, $per) ?>
  <div class="small muted" style="padding:0 16px 14px">พบทั้งหมด <?= number_format($total) ?> เอกสาร</div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
