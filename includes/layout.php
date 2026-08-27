<?php
/** โครงหน้าเว็บ: header + เมนูข้าง + footer */

function icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'in'        => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/>',
        'out'       => '<path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M4 3h16"/>',
        'adjust'    => '<path d="M4 6h16M4 12h10M4 18h7"/><circle cx="18" cy="12" r="2"/><circle cx="14" cy="18" r="2"/>',
        'docs'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'box'       => '<path d="m3 8 9-5 9 5v8l-9 5-9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/>',
        'chart'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'warn'      => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.9-1.1L14.2 3H9.8l-.4 2.8a7 7 0 0 0-1.9 1.1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.9 1.1l.4 2.8h4.4l.4-2.8a7 7 0 0 0 1.9-1.1l2.4 1 2-3.4-2-1.6c.07-.36.1-.73.1-1.1z"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 5.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5a6 6 0 0 1 3.5 5.5"/>',
        'card'      => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 14h6"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'prod'      => '<path d="M3 21V10l5 3.5V10l5 3.5V10l5 3.5V21z"/><path d="M18 10V4h3v6"/><path d="M3 21h18"/>',
        'bom'       => '<path d="M9 4h11M9 12h11M9 20h11"/><path d="M4 4v16"/><path d="M4 4h2M4 12h2M4 20h2"/>',
        'book'      => '<path d="M4 5a2 2 0 0 1 2-2h13v18H6a2 2 0 0 1-2-2z"/><path d="M4 17h15"/><path d="M9 7h6"/>',
    ];
    $p = $paths[$name] ?? $paths['box'];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function nav_menu(): array
{
    return [
        ['group' => 'ภาพรวม', 'items' => [
            ['dashboard', 'แดชบอร์ด', 'dashboard', 'viewer'],
        ]],
        ['group' => 'งานประจำวัน', 'items' => [
            ['receive',  'รับสินค้าเข้า', 'in',       'staff'],
            ['issue',    'เบิก/จ่ายออก',  'out',      'staff'],
            ['adjust',   'ปรับปรุงยอด',   'adjust',   'staff'],
        ]],
        ['group' => 'การผลิต', 'items' => [
            ['production', 'ใบผลิต',            'prod', 'staff'],
            ['bom',        'สูตรการผลิต (BOM)', 'bom',  'staff'],
        ]],
        ['group' => 'ข้อมูล', 'items' => [
            ['products',  'สินค้า',            'box',  'viewer'],
            ['docs',      'เอกสารทั้งหมด',      'docs', 'viewer'],
            ['movements', 'ความเคลื่อนไหว',     'card', 'viewer'],
        ]],
        ['group' => 'รายงาน', 'items' => [
            ['report_balance',  'ยอดคงเหลือ',   'chart', 'viewer'],
            ['report_lowstock', 'สินค้าใกล้หมด', 'warn',  'viewer'],
            ['stock_card',      'การ์ดสินค้า',   'card',  'viewer'],
        ]],
        ['group' => 'ตั้งค่า', 'items' => [
            ['warehouses', 'คลังสินค้า', 'settings', 'staff'],
            ['categories', 'หมวดหมู่',   'settings', 'staff'],
            ['units',      'หน่วยนับ',   'settings', 'staff'],
            ['suppliers',  'ผู้ขาย',     'settings', 'staff'],
            ['users',      'ผู้ใช้งาน',  'users',    'admin'],
        ]],
        ['group' => 'ช่วยเหลือ', 'items' => [
            ['manual_pdf', 'คู่มือระบบ (PDF)', 'book', 'viewer'],
        ]],
    ];
}

/**
 * กระดิ่งแจ้งเตือนใบสั่งซื้อ (PO) จากอีเมล
 * ตัวรายการโหลดผ่าน api.php ตอนกดเปิด เพื่อไม่ให้ทุกหน้าต้องคุยกับฐานข้อมูลเพิ่ม
 */
function render_po_bell(): void
{
    $n = po_unread_count();
    ?>
    <div class="bell" id="poBell"
         data-api="<?= h(BASE_URL) ?>/api.php"
         data-csrf="<?= h(csrf_token()) ?>"
         data-receive="<?= h(url('receive')) ?>">
      <button type="button" class="bellbtn" aria-expanded="false" aria-label="แจ้งเตือนใบสั่งซื้อจากอีเมล">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/>
          <path d="M10.3 20a2 2 0 0 0 3.4 0"/>
        </svg>
        <span class="dot"<?= $n ? '' : ' hidden' ?>><?= $n > 99 ? '99+' : (int)$n ?></span>
      </button>

      <div class="bellpanel" hidden>
        <div class="bellhead">
          <b>ใบสั่งซื้อจากอีเมล</b>
          <span class="spacer"></span>
          <button type="button" class="btn sm ghost" data-po="fetch">ตรวจอีเมล</button>
          <button type="button" class="btn sm ghost" data-po="readall">อ่านทั้งหมด</button>
        </div>
        <div class="belllist" data-po-list>
          <div class="bellempty">กำลังโหลด…</div>
        </div>
      </div>
    </div>
    <?php
}

function render_header(string $title, string $active = ''): void
{
    $u = current_user();
    ?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> · <?= h(APP_NAME) ?></title>
<!-- ไอคอนบนแท็บเบราว์เซอร์ — SVG มาก่อน เบราว์เซอร์รุ่นเก่าจะถอยไปใช้ PNG เอง -->
<link rel="icon" type="image/svg+xml" href="<?= h(BASE_URL) ?>/assets/favicon.svg?v=<?= h(APP_VERSION) ?>">
<link rel="icon" type="image/jpeg" sizes="512x512" href="<?= h(BASE_URL) ?>/imgs/558314573146663596_n.jpg?v=<?= h(APP_VERSION) ?>">
<link rel="apple-touch-icon" href="<?= h(BASE_URL) ?>/assets/favicon.png?v=<?= h(APP_VERSION) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= h(BASE_URL) ?>/assets/style.css?v=<?= h(APP_VERSION) ?>">
<!-- ต้องโหลดก่อน <script> ของแต่ละหน้า เพราะหน้าฟอร์มเรียก PP.initItems() ตอนท้าย body -->
<script src="<?= h(BASE_URL) ?>/assets/app.js?v=<?= h(APP_VERSION) ?>"></script>
</head>
<body>
<input type="checkbox" id="navtoggle" hidden>
<div class="shell">
  <aside class="side">
    <div class="brand">
      <span class="logo"><?= icon('box') ?></span>
      <span>
        <b><?= h(APP_NAME) ?></b>
        <small>v<?= h(APP_VERSION) ?></small>
      </span>
    </div>
    <nav>
      <?php foreach (nav_menu() as $g): ?>
        <?php
        $visible = array_filter($g['items'], fn($it) => can($it[3]));
        if (!$visible) { continue; }
        ?>
        <div class="navgroup"><?= h($g['group']) ?></div>
        <?php foreach ($visible as $it): ?>
          <a class="navlink<?= $active === $it[0] ? ' on' : '' ?>" href="<?= h(url($it[0])) ?>">
            <?= icon($it[2]) ?><span><?= h($it[1]) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <div class="me">
        <span class="avatar"><?= h(mb_substr($u['fullname'] ?? '?', 0, 1, 'UTF-8')) ?></span>
        <span>
          <b><?= h($u['fullname'] ?? '') ?></b>
          <small><?= h(role_label($u['role'] ?? '')) ?></small>
        </span>
      </div>
      <a class="btn ghost sm" href="<?= h(BASE_URL) ?>/logout.php"><?= icon('logout') ?> ออกจากระบบ</a>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <label for="navtoggle" class="burger" title="เมนู"><span></span><span></span><span></span></label>
      <h1><?= h($title) ?></h1>
      <div class="spacer"></div>
      <?php render_po_bell(); ?>
      <span class="today"><?= h(thai_date(date('Y-m-d'))) ?></span>
    </header>

    <div class="content">
      <?php foreach (flash_pull() as $f): ?>
        <?php /* JS หยิบไปแสดงเป็นกล่องแจ้งเตือนมุมขวาบน — ถ้าปิด JS จะเห็นเป็นแถบข้อความปกติ */ ?>
        <div class="flashseed" data-type="<?= h($f['type']) ?>"
             data-title="<?= h($f['title'] ?? flash_title($f['type'])) ?>"><?= $f['msg'] ?></div>
        <noscript><div class="alert <?= h($f['type']) ?>"><?= $f['msg'] ?></div></noscript>
      <?php endforeach; ?>
<?php
}

function render_footer(): void
{
    ?>
    </div>
    <footer class="foot"><?= h(APP_NAME) ?> · สร้างด้วย PHP <?= h(PHP_VERSION) ?> + MySQL</footer>
  </main>
</div>
<label for="navtoggle" class="scrim"></label>
</body>
</html>
<?php
}

/* role_label() ย้ายไปอยู่ใน helpers.php แล้ว เพราะหน้า login.php ก็ต้องใช้ */
