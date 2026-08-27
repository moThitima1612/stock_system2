<?php
/**
 * คู่มือระบบ — เลย์เอาต์ A4 สำหรับพิมพ์ / บันทึกเป็น PDF
 * ใช้เนื้อหาชุดเดียวกับหน้าคู่มือในระบบ (manual_body.php) จึงไม่มีทางหลุดกัน
 */
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/manual_toc.php';
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>คู่มือระบบ · <?= h(APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= h(BASE_URL) ?>/assets/favicon.svg?v=<?= h(APP_VERSION) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= h(BASE_URL) ?>/assets/style.css?v=<?= h(APP_VERSION) ?>">
<style>
  body{background:#e9e8f0;margin:0}

  /* กระดาษเป็นสีขาวเสมอ จึงตรึงชุดสีโทนสว่างไว้ ไม่ให้ธีมมืดของเครื่องผู้ใช้
     ทำให้ตัวหนังสือกลายเป็นสีอ่อนบนพื้นขาว */
  body.pdfdoc{
    --bg:#ffffff;      --surface:#ffffff;  --surface-2:#f6f6fa;
    --line:#d7d6e4;    --line-2:#e8e8f1;   --hair:rgba(185,138,18,.35);
    --text:#111111;    --muted:#555566;
    --brand:#8a6410;   --brand-2:#b98a12;  --brand-3:#e8bd3f;
    --brand-ink:#231a05; --brand-soft:#fdf3d6;
    --ok:#0c7049;      --ok-soft:#e2f6ee;
    --warn:#9c620a;    --warn-soft:#fbf0d8;
    --danger:#bd1e3a;  --danger-soft:#fde4e9;
    --info:#125e9c;    --info-soft:#e3eefb;
    --gold:#8a6410;    --gold-soft:#fbeec4;
    --shadow:none;
  }

  /* ---------- แถบเครื่องมือ (ไม่ติดไปกับงานพิมพ์) ---------- */
  .toolbar{position:sticky;top:0;z-index:10;background:#1d1740;color:#ddd8f2;padding:12px 20px;
    display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:13px}
  .toolbar b{color:#fff;margin-right:auto}
  .toolbar a,.toolbar button{font:inherit;font-size:13px;font-weight:600;cursor:pointer;
    text-decoration:none;padding:7px 14px;border-radius:7px;border:1px solid rgba(255,255,255,.25);
    background:transparent;color:#ddd8f2}
  .toolbar a:hover,.toolbar button:hover{background:rgba(255,255,255,.1)}
  .toolbar .go{background:linear-gradient(165deg,#ffdf7d,#e8bd3f 45%,#b98a12);color:#231a05;border-color:#b98a12}

  /* ---------- กระดาษ ---------- */
  .paper{width:210mm;margin:18px auto;background:#fff;color:#111;padding:15mm 14mm 12mm;
    box-shadow:0 10px 30px -14px rgba(0,0,0,.4)}

  /* ---------- ปกหน้าแรก ---------- */
  .cover{page-break-after:always;padding:26mm 0 0}
  .cover .co{font-size:13px;color:#555;letter-spacing:.02em}
  .cover .rule{border:0;border-top:2px solid #1d1740;margin:14px 0 26px}
  .cover h1{font-family:var(--serif);font-size:44px;line-height:1.1;margin:0 0 10px;color:#1d1740;
    font-weight:600;letter-spacing:.01em}
  .cover .sub{font-size:15px;color:#444;max-width:52ch;line-height:1.75}
  .cover .facts{margin-top:34px;border-top:1px solid #ccc;padding-top:16px;
    display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 26px;max-width:150mm}
  .cover .facts div{font-size:12.5px}
  .cover .facts span{display:block;color:#777;font-size:10.5px;letter-spacing:.14em;
    text-transform:uppercase;margin-bottom:2px}
  .cover .facts b{font-weight:600;color:#111}
  .cover .seal{margin-top:40px;display:inline-block;border:2px solid #b98a12;color:#8a6410;
    border-radius:5px;padding:5px 16px;font-size:11.5px;font-weight:700;letter-spacing:.08em}

  /* ---------- ปรับเนื้อหาให้เป็นเอกสารเรียงหน้า ---------- */
  .pdfdoc .man{display:block}
  .pdfdoc .man>div{display:block}
  .pdfdoc aside.man-toc.noprint{display:block !important;position:static;page-break-after:always;
    border:none;box-shadow:none;background:none;margin:0 0 10px}
  .pdfdoc .man-toc .card-head{border:none;padding:0 0 10px}
  .pdfdoc .man-toc .card-head h2{font-family:var(--serif);font-size:26px;color:#1d1740}
  .pdfdoc .man-toc .card-body{padding:0;columns:2;column-gap:26px}
  .pdfdoc .man-toc .g{break-inside:avoid}
  .pdfdoc .man-toc a{break-inside:avoid;color:#111}

  .pdfdoc .man-sec{page-break-before:always;border:none;box-shadow:none;background:none;margin:0}
  .pdfdoc .man-sec .card-head{background:none;border:none;border-bottom:2px solid #1d1740;
    padding:0 0 8px;margin-bottom:14px}
  .pdfdoc .man-sec .card-head h2{font-size:20px;color:#1d1740}
  .pdfdoc .man-sec .card-head h2 i{font-size:26px}
  .pdfdoc .man-sec .card-body{padding:0}
  .pdfdoc .man-sec .btn,.pdfdoc .man-sec .card-head .btn{display:none}
  .pdfdoc .tablewrap{border:none;box-shadow:none;overflow:visible;background:none}
  .pdfdoc table.tbl{break-inside:auto}
  .pdfdoc table.tbl tr{break-inside:avoid}
  .pdfdoc table.tbl thead{display:table-header-group}
  /* บล็อกโค้ดพื้นเข้มกินหมึกมากเวลาพิมพ์ — สลับเป็นพื้นอ่อนขอบบาง */
  .pdfdoc .man pre{background:#f5f5f9;color:#1a1a1a;border:1px solid #dcdce6}
  .pdfdoc .man pre b{color:#8a6410}
  .pdfdoc .man pre span{color:#666677}

  .pdfdoc .alert,.pdfdoc pre,.pdfdoc ol.steps>li{break-inside:avoid}
  .pdfdoc h3{break-after:avoid}

  @media print{
    body{background:#fff}
    .toolbar{display:none}
    .paper{width:auto;margin:0;padding:0;box-shadow:none}
    .cover{padding:18mm 0 0}
    @page{size:A4;margin:15mm 14mm 16mm}
  }
</style>
</head>
<body class="pdfdoc">

<div class="toolbar">
  <b>คู่มือระบบ — เลย์เอาต์ A4</b>
  <a href="<?= h(url('dashboard')) ?>">← กลับแดชบอร์ด</a>
  <button class="go" onclick="window.print()">พิมพ์ / บันทึก PDF</button>
</div>

<div class="paper">

  <!-- ================= ปก ================= -->
  <div class="cover">
    <div class="co"><?= h(COMPANY_NAME) ?></div>
    <hr class="rule">
    <h1>คู่มือระบบ<br>สต๊อกคลังสินค้า</h1>
    <p class="sub">คู่มือการใช้งานและการดูแลระบบฉบับละเอียด ครอบคลุมทุกหน้าจอ ตั้งแต่การติดตั้ง
       การบันทึกงานประจำวัน การผลิตตามสูตร เอกสารและรายงาน ไปจนถึงการแก้ปัญหาที่พบบ่อย</p>

    <div class="facts">
      <div><span>เวอร์ชันระบบ</span><b><?= h(APP_VERSION) ?></b></div>
      <div><span>จำนวนบท</span><b><?php
        $chapters = 0;
        foreach (MANUAL_TOC as $items) { $chapters += count($items); }
        echo (int)$chapters;
      ?> บท</b></div>
      <div><span>ฐานข้อมูล</span><b><?= h(DB_NAME) ?></b></div>
      <div><span>จัดทำเมื่อ</span><b><?= h(thai_date(date('Y-m-d'))) ?></b></div>
      <div><span>จัดทำโดย</span><b><?= h(current_user()['fullname'] ?? '-') ?></b></div>
      <div><span>ระบบที่ใช้</span><b>PHP <?= h(PHP_VERSION) ?> + MySQL</b></div>
    </div>

    <div class="seal">เอกสารภายในองค์กร</div>
  </div>

  <!-- ================= สารบัญ + เนื้อหาทั้งหมด ================= -->
  <?php require __DIR__ . '/manual_body.php'; ?>

</div>

</body>
</html>
