<?php
/**
 * ปลายทาง JSON เล็ก ๆ สำหรับกระดิ่งแจ้งเตือนใบ PO
 *
 *   GET  api.php?a=list    -> รายการแจ้งเตือน + จำนวนที่ยังไม่อ่าน
 *   GET  api.php?a=count   -> จำนวนที่ยังไม่อ่าน (ใช้ตอนเช็คเป็นระยะ)
 *   POST api.php?a=fetch   -> ดึงเมลใหม่จากกล่อง PO
 *   POST api.php?a=read    -> ทำเครื่องหมายอ่านแล้ว (id=0 คือทั้งหมด)
 *
 * ทุกคำสั่งต้องเข้าสู่ระบบก่อน และคำสั่งที่เปลี่ยนข้อมูลต้องมี CSRF token
 */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/** ตอบกลับแล้วจบการทำงาน */
function say(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_logged_in()) {
    say(['ok' => false, 'error' => 'ยังไม่ได้เข้าสู่ระบบ'], 401);
}

$action = (string)inp('a');

if (is_post()) {
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        say(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่'], 419);
    }
}

switch ($action) {

    case 'count':
        say(['ok' => true, 'unread' => po_unread_count()]);
        // no break

    case 'list':
        [$ready, $why] = po_mail_ready();
        $rows = [];
        foreach (po_list(15) as $r) {
            $when = $r['mail_date'] ?: $r['created_at'];
            $rows[] = [
                'id'       => (int)$r['id'],
                'subject'  => $r['subject'],
                'po_no'    => $r['po_no'],
                'from'     => $r['from_name'] ?: $r['from_email'],
                'email'    => $r['from_email'],
                'excerpt'  => mb_substr((string)$r['excerpt'], 0, 160, 'UTF-8'),
                'attach'   => (int)$r['n_attach'],
                'when'     => thai_date($when, true),
                'is_read'  => (int)$r['is_read'],
                'doc_id'   => $r['doc_id'] ? (int)$r['doc_id'] : null,
            ];
        }
        say([
            'ok'       => true,
            'unread'   => po_unread_count(),
            'items'    => $rows,
            'ready'    => $ready,
            'setup'    => $ready ? '' : $why,
            'keyword'  => MAIL_PO_KEYWORD,
        ]);
        // no break

    case 'fetch':
        if (!is_post()) {
            say(['ok' => false, 'error' => 'ต้องเรียกด้วยวิธี POST'], 405);
        }
        $r = po_fetch();
        say([
            'ok'      => $r['ok'],
            'new'     => $r['new'],
            'scanned' => $r['scanned'],
            'unread'  => po_unread_count(),
            'error'   => $r['error'],
        ]);
        // no break

    case 'read':
        if (!is_post()) {
            say(['ok' => false, 'error' => 'ต้องเรียกด้วยวิธี POST'], 405);
        }
        $n = po_mark_read(inp_int('id', 0) ?? 0);
        say(['ok' => true, 'marked' => $n, 'unread' => po_unread_count()]);
        // no break
}

say(['ok' => false, 'error' => 'ไม่รู้จักคำสั่งนี้'], 400);
