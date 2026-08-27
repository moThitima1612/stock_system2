<?php
require_once __DIR__ . '/../includes/layout.php';

$whId = inp_int('wh');
$type = (string)inp('type');
$from = (string)inp('from');
$to   = (string)inp('to');
$kw   = (string)inp('kw');
$page = max(1, inp_int('page', 1));
$per  = ROWS_PER_PAGE * 2;

$where = [];
$args  = [];
if ($whId) { $where[] = 'm.warehouse_id = ?'; $args[] = $whId; }
if ($type !== '' && in_array($type, ['IN','OUT','ADJUST','PROD','VOID'], true)) {
    $where[] = 'm.doc_type = ?'; $args[] = $type;
}
if ($from) { $where[] = 'DATE(m.moved_at) >= ?'; $args[] = $from; }
if ($to)   { $where[] = 'DATE(m.moved_at) <= ?'; $args[] = $to; }
if ($kw !== '') {
    $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR m.doc_no LIKE ?)';
    array_push($args, "%$kw%", "%$kw%", "%$kw%");
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$base = "FROM stock_movements m
          JOIN products p ON p.id = m.product_id
          JOIN warehouses wh ON wh.id = m.warehouse_id
          LEFT JOIN users u ON u.id = m.user_id
          $w";

$total = (int)qv("SELECT COUNT(*) $base", $args);
$sumIn  = (float)qv("SELECT COALESCE(SUM(CASE WHEN m.qty_change > 0 THEN m.qty_change ELSE 0 END),0) $base", $args);
$sumOut = (float)qv("SELECT COALESCE(SUM(CASE WHEN m.qty_change < 0 THEN -m.qty_change ELSE 0 END),0) $base", $args);

$rows = qa(
    "SELECT m.*, p.sku, p.name, wh.code AS wh_code, u.fullname,
            (SELECT id FROM stock_docs WHERE id = m.doc_id) AS doc_ref
     $base
     ORDER BY m.id DESC
     LIMIT $per OFFSET " . (($page - 1) * $per),
    $args
);

if (inp('export') !== '') {
    $all = qa("SELECT m.moved_at, m.doc_no, m.doc_type, p.sku, p.name, wh.code,
                      m.qty_change, m.balance_after, m.unit_cost, m.note, u.fullname
               $base ORDER BY m.id DESC LIMIT 10000", $args);
    export_if_requested('movements', 'รายงานความเคลื่อนไหวสต๊อก',
        ['เวลา','เลขที่เอกสาร','ประเภท','รหัสสินค้า','ชื่อสินค้า','คลัง','เปลี่ยนแปลง','คงเหลือ','ราคาทุน','หมายเหตุ','ผู้บันทึก'],
        array_map(fn($r) => [thai_date($r['moved_at'], true), $r['doc_no'], doc_type_label($r['doc_type']),
                             $r['sku'], $r['name'], $r['code'], qty_fmt($r['qty_change']),
                             qty_fmt($r['balance_after']), number_format((float)$r['unit_cost'], 2),
                             $r['note'], $r['fullname']], $all),
        ['รับเข้ารวม' => qty_fmt($sumIn), 'จ่ายออกรวม' => qty_fmt($sumOut),
         'จำนวนรายการ' => number_format($total)]);
}

render_header('ความเคลื่อนไหวสต๊อก', 'movements');
?>
<div class="kpis">
  <div class="kpi ok"><span class="kico"><?= icon('in') ?></span>
    <span><span class="kval"><?= qty_fmt($sumIn) ?></span><span class="klab">รับเข้ารวม (ตามตัวกรอง)</span></span></div>
  <div class="kpi warn"><span class="kico"><?= icon('out') ?></span>
    <span><span class="kval"><?= qty_fmt($sumOut) ?></span><span class="klab">จ่ายออกรวม (ตามตัวกรอง)</span></span></div>
  <div class="kpi"><span class="kico"><?= icon('card') ?></span>
    <span><span class="kval"><?= number_format($total) ?></span><span class="klab">จำนวนรายการเคลื่อนไหว</span></span></div>
</div>

<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="movements">
    <label>คลัง
      <select name="wh">
        <option value="">ทุกคลัง</option>
        <?php foreach (all_warehouses(false) as $x): ?>
          <option value="<?= (int)$x['id'] ?>" <?= $whId === (int)$x['id'] ? 'selected' : '' ?>>
            <?= h($x['code'] . ' · ' . $x['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ประเภท
      <select name="type">
        <option value="">ทั้งหมด</option>
        <?php foreach (['IN','OUT','ADJUST','PROD','VOID'] as $t): ?>
          <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= h(doc_type_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ตั้งแต่ <input type="date" name="from" value="<?= h($from) ?>"></label>
    <label>ถึง <input type="date" name="to" value="<?= h($to) ?>"></label>
    <label class="grow">ค้นหา
      <input type="search" name="kw" value="<?= h($kw) ?>" placeholder="สินค้า / เลขที่เอกสาร"></label>
    <button class="btn primary"><?= icon('search') ?> ค้นหา</button>
    <?= export_buttons() ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">ยังไม่มีความเคลื่อนไหวตามเงื่อนไขนี้</div>
  <?php else: ?>
  <div class="tablewrap">
    <table class="tbl">
      <thead><tr><th>เวลา</th><th>เอกสาร</th><th>ประเภท</th><th>สินค้า</th><th>คลัง</th>
                 <th class="num">เข้า</th><th class="num">ออก</th><th class="num">คงเหลือ</th><th>ผู้บันทึก</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $m): $d = (float)$m['qty_change']; ?>
        <tr>
          <td class="small muted nowrap"><?= h(thai_date($m['moved_at'], true)) ?></td>
          <td class="nowrap"><?php if ($m['doc_ref']): ?>
              <a href="<?= h(url('doc_view', ['id' => $m['doc_ref']])) ?>"><code><?= h($m['doc_no']) ?></code></a>
              <?php else: ?><code><?= h($m['doc_no']) ?></code><?php endif; ?></td>
          <td><?= doc_type_badge($m['doc_type']) ?></td>
          <td><?= h($m['name']) ?><div class="small muted"><?= h($m['sku']) ?></div></td>
          <td class="small"><?= h($m['wh_code']) ?></td>
          <td class="num"><?= $d > 0 ? '<b style="color:var(--ok)">+' . qty_fmt($d) . '</b>' : '' ?></td>
          <td class="num"><?= $d < 0 ? '<b style="color:var(--danger)">−' . qty_fmt(abs($d)) . '</b>' : '' ?></td>
          <td class="num"><?= qty_fmt($m['balance_after']) ?></td>
          <td class="small muted"><?= h($m['fullname'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginate_links($total, $page, $per) ?>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
