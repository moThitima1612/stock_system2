<?php
/** ตัวจัดเส้นทางหลัก — ทุกหน้าเรียกผ่าน index.php?p=xxx */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

// ยังไม่ได้ติดตั้ง -> ส่งไปหน้า install
try {
    db()->query('SELECT 1 FROM users LIMIT 1');
} catch (PDOException $e) {
    redirect(BASE_URL . '/install.php');
}

require_login();

/** หน้า => สิทธิ์ขั้นต่ำ */
const ROUTES = [
    'dashboard'       => 'viewer',
    'products'        => 'viewer',
    'product_form'    => 'staff',
    'receive'         => 'staff',
    'issue'           => 'staff',
    'adjust'          => 'staff',
    'production'      => 'staff',
    'bom'             => 'staff',
    'docs'            => 'viewer',
    'doc_view'        => 'viewer',
    'delivery_note'   => 'viewer',
    'movements'       => 'viewer',
    'stock_card'      => 'viewer',
    'report_balance'  => 'viewer',
    'report_lowstock' => 'viewer',
    'categories'      => 'staff',
    'units'           => 'staff',
    'warehouses'      => 'staff',
    'suppliers'       => 'staff',
    'users'           => 'admin',
    'manual_pdf'      => 'viewer',
];

$page = (string)inp('p', 'dashboard');
if (!isset(ROUTES[$page])) {
    $page = 'dashboard';
}

require_role(ROUTES[$page]);

$file = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($file)) {
    http_response_code(404);
    render_header('ไม่พบหน้า');
    echo '<div class="card"><p>ไม่พบหน้าที่ต้องการ</p></div>';
    render_footer();
    exit;
}

require $file;
