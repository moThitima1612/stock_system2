<?php
/**
 * ค่าตั้งต้นของระบบสต๊อกคลังสินค้า (WMS Lite)
 * แก้ค่าในไฟล์นี้ให้ตรงกับเครื่องของคุณก่อนรัน install.php
 */

// ---------- ฐานข้อมูล ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');            // เครื่องนี้ MAMP รัน MySQL 5.7 ที่ 3306
                                      // (ค่าอื่นที่พบบ่อย: MAMP(Win)=3307, MAMP(Mac)=8889)
define('DB_NAME', 'pp_stock');
define('DB_USER', 'root');
define('DB_PASS', 'root');            // MAMP=root, XAMPP=ว่าง
define('DB_CHARSET', 'utf8mb4');

// ---------- ทั่วไป ----------
define('APP_NAME', 'ระบบสต๊อกคลังสินค้า');
define('APP_VERSION', '1.2.1');   // ใช้ทำ cache-busting ให้ css/js ด้วย — บวมเลขทุกครั้งที่แก้ asset
define('BASE_URL', '/pp');            // path ที่วางโปรเจกต์ (วางที่ root ใช้ '')
define('ROWS_PER_PAGE', 20);
define('LOW_STOCK_LIMIT', 10);        // จำนวนรายการใกล้หมดที่โชว์บนแดชบอร์ด
define('ALLOW_NEGATIVE_STOCK', false); // true = ยอมให้ยอดคงเหลือติดลบได้

// ---------- ข้อมูลบริษัท (ใช้บนหัวใบส่งของ / เอกสารพิมพ์) ----------
define('COMPANY_NAME',    'ห้างหุ้นส่วนจำกัด ตัวอย่างการพิมพ์');
define('COMPANY_ADDRESS', '123/45 ถนนพระราม 2 แขวงบางมด เขตจอมทอง กรุงเทพฯ 10150');
define('COMPANY_TEL',     'โทร. 02-123-4567  แฟกซ์ 02-123-4568');
define('COMPANY_TAXID',   'เลขประจำตัวผู้เสียภาษี 0-1234-56789-01-2');

// ---------- กล่องอีเมลรับใบสั่งซื้อ (PO) ----------
// กระดิ่งบนแถบบนจะดึงอีเมลที่ "หัวเรื่องมีคำว่า PO" มาแสดงเป็นแจ้งเตือน
// ต้องเปิดส่วนขยาย imap ใน php.ini ก่อน (extension=imap)
define('MAIL_PO_ENABLED', true);                           // เปิดใช้งานเมื่อกรอกข้อมูลด้านล่างครบแล้ว
define('MAIL_PO_HOST',    '{imap.gmail.com:993/imap/ssl}INBOX');
define('MAIL_PO_USER',    'thitimaaof1612@gmail.com');     // อีเมลที่ใช้รับใบ PO
define('MAIL_PO_PASS',    'abcdefghijklmnop');                             // Gmail ให้ใช้ App Password ไม่ใช่รหัสผ่านปกติ
define('MAIL_PO_KEYWORD', 'PO');                           // คำที่ต้องมีในหัวเรื่อง
define('MAIL_PO_DAYS',    14);                             // ย้อนหลังกี่วัน
define('MAIL_PO_LIMIT',   40);                             // ดึงสูงสุดกี่ฉบับต่อครั้ง

date_default_timezone_set('Asia/Bangkok');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// ---------- โหมดพัฒนา ----------
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
