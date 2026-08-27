<?php
/**
 * ตัวติดตั้งระบบ — สร้างฐานข้อมูล ตาราง และบัญชีผู้ดูแลระบบ
 * เมื่อติดตั้งเสร็จแล้วควรลบไฟล์นี้ทิ้ง
 */
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('PPSTOCK');
    session_start();
}
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/schema_runner.php';
require_once __DIR__ . '/includes/migrate.php';

/* ---------- ตรวจการเชื่อมต่อ (ยังไม่ระบุฐานข้อมูล) ---------- */
function server_pdo(?string $port = null): PDO
{
    $port = $port ?? DB_PORT;
    return new PDO(
        sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, $port, DB_CHARSET),
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

$connErr = '';
$srv     = null;
try {
    $srv = server_pdo();
} catch (PDOException $e) {
    $connErr = $e->getMessage();
}

/* ถ้าพอร์ตที่ตั้งไว้ต่อไม่ได้ ลองพอร์ตยอดนิยมเพื่อแนะนำผู้ใช้ */
$suggest = [];
if ($connErr) {
    foreach (['3306', '3307', '8889', '33060'] as $p) {
        if ($p === DB_PORT) { continue; }
        try {
            $t = server_pdo($p);
            $suggest[$p] = (string)$t->query('SELECT VERSION()')->fetchColumn();
        } catch (PDOException $e) { /* ข้าม */ }
    }
}

$installed = false;
if ($srv) {
    try {
        $srv->query('USE `' . DB_NAME . '`');
        $n = $srv->query("SELECT COUNT(*) FROM information_schema.tables
                           WHERE table_schema = " . $srv->quote(DB_NAME) . " AND table_name = 'users'")->fetchColumn();
        if ($n) {
            $c = $srv->query('SELECT COUNT(*) FROM `' . DB_NAME . '`.users')->fetchColumn();
            $installed = (int)$c > 0;
        }
    } catch (PDOException $e) { /* ยังไม่มีฐานข้อมูล */ }
}

$done = '';
$err  = '';

if (is_post() && $srv && !$err) {
    csrf_check();

    $adminUser = (string)inp('admin_user', 'admin');
    $adminName = (string)inp('admin_name', 'ผู้ดูแลระบบ');
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $seed      = inp('seed') !== '';
    $force     = inp('force') !== '';

    if (strlen($adminPass) < 6) {
        $err = 'รหัสผ่านผู้ดูแลระบบต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($installed && !$force) {
        $err = 'ระบบถูกติดตั้งไว้แล้ว หากต้องการติดตั้งซ้ำให้ติ๊กยืนยันด้านล่าง';
    }

    if (!$err) {
        try {
            $srv->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`
                        DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $srv->exec('USE `' . DB_NAME . '`');

            $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
            if ($sql === false) {
                throw new RuntimeException('อ่านไฟล์ sql/schema.sql ไม่ได้');
            }
            run_sql_script($srv, $sql, true);   // ข้าม CREATE DATABASE/USE ในไฟล์ ใช้ DB_NAME จาก config
            apply_migrations($srv);   // อัปเกรดโครงสร้างเดิมให้ทันสมัย (ปลอดภัยกับข้อมูลเดิม)

            // บัญชีผู้ดูแลระบบ
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $st   = $srv->prepare('SELECT id FROM users WHERE username = ?');
            $st->execute([$adminUser]);
            if ($id = $st->fetchColumn()) {
                $srv->prepare('UPDATE users SET password_hash=?, fullname=?, role=\'admin\', is_active=1 WHERE id=?')
                    ->execute([$hash, $adminName, $id]);
            } else {
                $srv->prepare('INSERT INTO users (username, password_hash, fullname, role) VALUES (?,?,?,\'admin\')')
                    ->execute([$adminUser, $hash, $adminName]);
            }

            if ($seed) {
                require __DIR__ . '/sql/seed.php';
                seed_demo_data($srv);
            }

            $done = $adminUser;
        } catch (Throwable $e) {
            $err = 'ติดตั้งไม่สำเร็จ: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดตั้งระบบ · <?= h(APP_NAME) ?></title>
<!-- ไอคอนบนแท็บเบราว์เซอร์ — SVG มาก่อน เบราว์เซอร์รุ่นเก่าจะถอยไปใช้ PNG เอง -->
<link rel="icon" type="image/svg+xml" href="<?= h(BASE_URL) ?>/assets/favicon.svg?v=<?= h(APP_VERSION) ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?= h(BASE_URL) ?>/assets/favicon.png?v=<?= h(APP_VERSION) ?>">
<link rel="apple-touch-icon" href="<?= h(BASE_URL) ?>/assets/favicon.png?v=<?= h(APP_VERSION) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= h(BASE_URL) ?>/assets/style.css?v=<?= h(APP_VERSION) ?>">
<style>
  body{display:block;padding:40px 16px}
  .wrap{max-width:640px;margin:0 auto}
  .steps{display:flex;gap:8px;margin-bottom:20px;font-size:.82rem;color:var(--muted)}
  .steps b{color:var(--text)}
  kbd{background:var(--surface-2);border:1px solid var(--line);border-radius:5px;padding:1px 6px;font-size:.85em}
</style>
</head>
<body>
<div class="wrap">
  <div class="loginhead" style="margin-bottom:22px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:44px;height:44px;color:var(--brand)">
      <path d="m3 8 9-5 9 5v8l-9 5-9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/>
    </svg>
    <h1><?= h(APP_NAME) ?></h1>
    <p class="muted">ตัวช่วยติดตั้งระบบครั้งแรก</p>
  </div>

  <?php if ($done): ?>
    <div class="card"><div class="card-body">
      <div class="alert success"><b>ติดตั้งสำเร็จ</b> — ระบบพร้อมใช้งานแล้ว</div>
      <p>เข้าสู่ระบบด้วยชื่อผู้ใช้ <kbd><?= h($done) ?></kbd> และรหัสผ่านที่คุณเพิ่งตั้ง</p>
      <div class="alert warn">เพื่อความปลอดภัย ควรลบไฟล์ <kbd>install.php</kbd> ออกหลังติดตั้งเสร็จ</div>
      <a class="btn primary block" href="<?= h(BASE_URL) ?>/login.php">ไปหน้าเข้าสู่ระบบ</a>
    </div></div>

  <?php elseif ($connErr): ?>
    <div class="card"><div class="card-body">
      <div class="alert danger"><b>เชื่อมต่อ MySQL ไม่ได้</b><br><?= h($connErr) ?></div>
      <p>ค่าที่ใช้อยู่ใน <kbd>config.php</kbd>:</p>
      <table class="tbl">
        <tr><th>Host</th><td><?= h(DB_HOST) ?></td></tr>
        <tr><th>Port</th><td><?= h(DB_PORT) ?></td></tr>
        <tr><th>User</th><td><?= h(DB_USER) ?></td></tr>
        <tr><th>Database</th><td><?= h(DB_NAME) ?></td></tr>
      </table>
      <?php if ($suggest): ?>
        <div class="alert info" style="margin-top:16px">
          <b>พบ MySQL ที่พอร์ตอื่น</b> ลองแก้ <kbd>DB_PORT</kbd> ใน <kbd>config.php</kbd> เป็น:
          <ul style="margin:8px 0 0">
            <?php foreach ($suggest as $p => $ver): ?>
              <li><kbd><?= h($p) ?></kbd> — MySQL <?= h($ver) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <div class="alert warn" style="margin-top:16px">
          ตรวจสอบว่าเปิด MySQL ใน MAMP/XAMPP แล้ว จากนั้นกดโหลดหน้านี้ใหม่
        </div>
      <?php endif; ?>
      <a class="btn block" href="">ลองเชื่อมต่ออีกครั้ง</a>
    </div></div>

  <?php else: ?>
    <?php if ($err): ?><div class="alert danger"><?= h($err) ?></div><?php endif; ?>
    <?php if ($installed): ?>
      <div class="alert warn">ตรวจพบว่าระบบเคยติดตั้งไว้แล้วในฐานข้อมูล <kbd><?= h(DB_NAME) ?></kbd>
        — <a href="<?= h(BASE_URL) ?>/login.php">เข้าสู่ระบบ</a></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
      <div class="alert success" style="margin-bottom:18px">
        เชื่อมต่อ MySQL สำเร็จ (<?= h((string)$srv->query('SELECT VERSION()')->fetchColumn()) ?>)
        ที่ <?= h(DB_HOST) ?>:<?= h(DB_PORT) ?> — จะสร้างฐานข้อมูล <kbd><?= h(DB_NAME) ?></kbd> ให้อัตโนมัติ
      </div>

      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <h2>บัญชีผู้ดูแลระบบ</h2>
        <label>ชื่อผู้ใช้ <input type="text" name="admin_user" value="<?= h(inp('admin_user', 'admin')) ?>" required></label>
        <label>ชื่อ - นามสกุล <input type="text" name="admin_name" value="<?= h(inp('admin_name', 'ผู้ดูแลระบบ')) ?>" required></label>
        <label>รหัสผ่าน (อย่างน้อย 6 ตัวอักษร) <input type="password" name="admin_pass" minlength="6" required></label>

        <label style="display:flex;gap:8px;align-items:center;margin-top:14px">
          <input type="checkbox" name="seed" value="1" checked style="width:auto;margin:0">
          ใส่ข้อมูลตัวอย่าง (คลัง 2 แห่ง, หมวดหมู่, สินค้า 12 รายการ พร้อมยอดเริ่มต้น)
        </label>

        <?php if ($installed): ?>
          <label style="display:flex;gap:8px;align-items:center;color:var(--danger)">
            <input type="checkbox" name="force" value="1" style="width:auto;margin:0">
            ยืนยันติดตั้งซ้ำ (ตารางเดิมจะถูกเก็บไว้ ข้อมูลเดิมไม่ถูกลบ)
          </label>
        <?php endif; ?>

        <button class="btn primary block" style="margin-top:16px" type="submit">เริ่มติดตั้ง</button>
      </form>
    </div></div>
  <?php endif; ?>

  <p class="hint">
    <?= h(APP_NAME) ?> v<?= h(APP_VERSION) ?> · PHP <?= h(PHP_VERSION) ?>
  </p>
</div>
</body>
</html>
