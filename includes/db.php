<?php
/** การเชื่อมต่อฐานข้อมูล (PDO) */

/**
 * หน้าอธิบายเมื่อต่อฐานข้อมูลไม่ได้ — แยกสาเหตุให้ชัดว่าเป็น
 * "ไม่มี driver" (ปัญหาที่ตัว PHP) หรือ "ต่อเซิร์ฟเวอร์ไม่ได้" (ปัญหาที่ MySQL/config)
 */
function db_fail_page(string $error): void
{
    $noDriver = stripos($error, 'could not find driver') !== false;
    $drivers  = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    $e        = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    echo '<meta charset="utf-8"><div style="font-family:Sarabun,Segoe UI,Tahoma,sans-serif;max-width:720px;'
       . 'margin:60px auto;padding:26px;border:1px solid #f0c0c0;background:#fff6f6;border-radius:14px;line-height:1.65">';

    if ($noDriver) {
        echo '<h2 style="margin-top:0">PHP ยังไม่ได้เปิดส่วนขยาย <code>pdo_mysql</code></h2>'
           . '<p>MySQL อาจทำงานปกติ แต่ PHP ที่รันอยู่ต่อ MySQL ไม่ได้เพราะไม่มีไดรเวอร์</p>'
           . '<table style="border-collapse:collapse;font-size:.92em;margin:14px 0">'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">PHP ที่รันอยู่</td><td><b>' . $e(PHP_VERSION) . '</b> (' . $e(PHP_SAPI) . ')</td></tr>'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">php.ini ที่โหลด</td><td><code>' . $e(php_ini_loaded_file() ?: 'ไม่มี') . '</code></td></tr>'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">extension_dir</td><td><code>' . $e(ini_get('extension_dir')) . '</code></td></tr>'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">PDO drivers ที่มี</td><td><code>' . $e($drivers ? implode(', ', $drivers) : '(ไม่มีเลย)') . '</code></td></tr>'
           . '</table>'
           . '<p><b>สาเหตุที่พบบ่อยที่สุด:</b> เวอร์ชัน PHP กับ php.ini ไม่ตรงกัน '
           . 'เช่น Apache โหลด PHP 8.3.1 แต่ไปอ่าน <code>php.ini</code> ของ 8.2.14 '
           . 'ทำให้ <code>extension_dir</code> ชี้ไปโฟลเดอร์ ext คนละเวอร์ชัน — DLL จึงโหลดไม่ขึ้น</p>'
           . '<p><b>วิธีแก้:</b> เปิด <code>C:\MAMP\conf\apache\httpd.conf</code> แล้วทำให้สองบรรทัดนี้'
           . '<u>เป็นเวอร์ชันเดียวกัน</u> จากนั้น Stop → Start Apache ใน MAMP</p>'
           . '<pre style="background:#fff;border:1px solid #e6d9d9;border-radius:8px;padding:12px;overflow:auto">'
           . "PHPIniDir  \"C:\\MAMP\\conf\\php8.2.14\\php.ini\"\n"
           . "LoadModule php_module \"C:\\MAMP\\bin\\php\\php8.2.14\\php8apache2_4.dll\"</pre>";
    } else {
        echo '<h2 style="margin-top:0">เชื่อมต่อฐานข้อมูลไม่ได้</h2>'
           . '<p>ตรวจสอบว่า MySQL ทำงานอยู่ และค่าใน <code>config.php</code> ถูกต้อง</p>'
           . '<table style="border-collapse:collapse;font-size:.92em;margin:14px 0">'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">Host : Port</td><td><code>' . $e(DB_HOST) . ':' . $e(DB_PORT) . '</code></td></tr>'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">Database</td><td><code>' . $e(DB_NAME) . '</code></td></tr>'
           . '<tr><td style="padding:3px 12px 3px 0;color:#666">User</td><td><code>' . $e(DB_USER) . '</code></td></tr>'
           . '</table>'
           . '<p>ถ้าพอร์ตไม่ตรง ให้แก้ <code>DB_PORT</code> ใน <code>config.php</code> '
           . '(MAMP ดูพอร์ตได้ที่หน้า MAMP → Preferences → Ports)</p>';
    }

    if (APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap;color:#a00;margin-top:14px">' . $e($error) . '</pre>';
    }
    echo '<p style="margin-bottom:0"><a href="' . BASE_URL . '/install.php">→ ไปหน้าติดตั้ง / ตรวจสอบระบบ</a></p></div>';
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        db_fail_page($e->getMessage());
    }
    return $pdo;
}

/** query + bind แล้วคืน statement */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/** ดึงแถวเดียว */
function q1(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** ดึงทุกแถว */
function qa(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** ดึงค่าคอลัมน์แรกของแถวแรก */
function qv(string $sql, array $params = [], $default = null)
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? $default : $v;
}
