<?php
/** ฟังก์ชันช่วยทั่วไป */

/** escape สำหรับพิมพ์ลง HTML */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** อ่านค่าจาก $_GET/$_POST แบบปลอดภัย */
function inp(string $key, $default = '', ?array $src = null)
{
    $src = $src ?? $_REQUEST;
    if (!isset($src[$key])) {
        return $default;
    }
    $v = $src[$key];
    return is_string($v) ? trim($v) : $v;
}

function inp_int(string $key, ?int $default = null, ?array $src = null): ?int
{
    $v = inp($key, null, $src);
    if ($v === null || $v === '') {
        return $default;
    }
    return (int)$v;
}

function inp_dec(string $key, float $default = 0.0, ?array $src = null): float
{
    $v = inp($key, null, $src);
    if ($v === null || $v === '') {
        return $default;
    }
    return (float)str_replace(',', '', (string)$v);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

/** ---------- flash message ---------- */
function flash(string $type, string $msg, ?string $title = null): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg, 'title' => $title];
}

/**
 * ข้อความผิดพลาดของฟอร์ม — แสดงเป็นกล่องแจ้งเตือนมุมขวาบน
 * และมีแถบข้อความสำรองไว้ให้เมื่อผู้ใช้ปิด JavaScript
 */
function error_alert(string $msg, string $title = 'บันทึกไม่สำเร็จ'): string
{
    if ($msg === '') {
        return '';
    }
    return '<div class="flashseed" data-type="danger" data-title="' . h($title) . '">' . h($msg) . '</div>'
         . '<noscript><div class="alert danger">' . h($msg) . '</div></noscript>';
}

/** ชื่อไทยของระดับสิทธิ์ */
function role_label(string $r): string
{
    return ['admin' => 'ผู้ดูแลระบบ', 'staff' => 'เจ้าหน้าที่', 'viewer' => 'ดูอย่างเดียว'][$r] ?? $r;
}

/** หัวข้อเริ่มต้นของกล่องแจ้งเตือน แยกตามชนิดข้อความ */
function flash_title(string $type): string
{
    return [
        'success' => 'ทำรายการสำเร็จ',
        'danger'  => 'ทำรายการไม่สำเร็จ',
        'warn'    => 'โปรดตรวจสอบ',
        'info'    => 'แจ้งให้ทราบ',
    ][$type] ?? 'แจ้งให้ทราบ';
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** ---------- URL / redirect ---------- */
function url(string $page = '', array $params = []): string
{
    $base = BASE_URL . '/index.php';
    if ($page !== '') {
        $params = array_merge(['p' => $page], $params);
    }
    return $params ? $base . '?' . http_build_query($params) : $base;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** คง query string เดิมแล้วแทนที่บางตัว */
function url_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        }
    }
    return BASE_URL . '/index.php' . ($params ? '?' . http_build_query($params) : '');
}

/** ---------- format ---------- */
function num($v, int $dec = 2): string
{
    return number_format((float)$v, $dec);
}

/** จำนวนสินค้า: ตัด .000 ท้ายออกให้อ่านง่าย */
function qty_fmt($v): string
{
    $f = (float)$v;
    $s = number_format($f, 3);
    $s = rtrim(rtrim($s, '0'), '.');
    return $s === '' || $s === '-' ? '0' : $s;
}

function money($v): string
{
    return number_format((float)$v, 2);
}

/** 2026-08-26 -> 26/08/2569 */
function thai_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    $y = (int)date('Y', $ts) + 543;
    $s = date('d/m/', $ts) . $y;
    if ($withTime) {
        $s .= ' ' . date('H:i', $ts);
    }
    return $s;
}

/** แปลงจำนวนเงินเป็นตัวอักษรไทย เช่น 1250.50 -> "หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์" */
function baht_text(float $amount): string
{
    $digit = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $place = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

    $readInt = function (string $n) use (&$readInt, $digit, $place): string {
        $n = ltrim($n, '0');
        $len = strlen($n);
        if ($len === 0) {
            return '';
        }
        if ($len > 7) {            // เกินหลักล้าน -> ตัดเป็นก้อนล้าน
            return $readInt(substr($n, 0, $len - 6)) . 'ล้าน' . $readInt(substr($n, -6));
        }
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $d = (int)$n[$i];
            $p = $len - $i - 1;
            if ($d === 0) {
                continue;
            }
            if ($p === 1 && $d === 1) {
                $out .= 'สิบ';
            } elseif ($p === 1 && $d === 2) {
                $out .= 'ยี่สิบ';
            } elseif ($p === 0 && $d === 1 && $len > 1) {
                $out .= 'เอ็ด';
            } else {
                $out .= $digit[$d] . $place[$p];
            }
        }
        return $out;
    };

    $amount  = round($amount, 2);
    $neg     = $amount < 0;
    $amount  = abs($amount);
    $baht    = (string)(int)floor($amount);
    $satang  = (int)round(($amount - floor($amount)) * 100);

    $text = ($baht === '0' && $satang === 0) ? 'ศูนย์บาทถ้วน'
          : ($readInt($baht) ?: 'ศูนย์') . 'บาท' . ($satang > 0 ? $readInt(str_pad((string)$satang, 2, '0', STR_PAD_LEFT)) . 'สตางค์' : 'ถ้วน');

    return ($neg ? 'ลบ' : '') . $text;
}

/** ---------- pagination ---------- */
function paginate_links(int $total, int $page, int $perPage): string
{
    $pages = (int)ceil($total / max(1, $perPage));
    if ($pages <= 1) {
        return '';
    }
    $out   = '<nav class="pager">';
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);

    if ($page > 1) {
        $out .= '<a href="' . h(url_with(['page' => $page - 1])) . '">&laquo; ก่อนหน้า</a>';
    }
    if ($start > 1) {
        $out .= '<a href="' . h(url_with(['page' => 1])) . '">1</a>';
        if ($start > 2) {
            $out .= '<span class="gap">…</span>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $cls  = $i === $page ? ' class="on"' : '';
        $out .= '<a' . $cls . ' href="' . h(url_with(['page' => $i])) . '">' . $i . '</a>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) {
            $out .= '<span class="gap">…</span>';
        }
        $out .= '<a href="' . h(url_with(['page' => $pages])) . '">' . $pages . '</a>';
    }
    if ($page < $pages) {
        $out .= '<a href="' . h(url_with(['page' => $page + 1])) . '">ถัดไป &raquo;</a>';
    }
    return $out . '</nav>';
}

/** ---------- ป้ายชนิดเอกสาร ---------- */
function doc_type_label(string $t): string
{
    return [
        'IN'       => 'รับเข้า',
        'OUT'      => 'เบิกออก',
        'TRANSFER' => 'โอนย้าย',
        'ADJUST'   => 'ปรับปรุง',
        'PROD'     => 'ผลิต',
        'VOID'     => 'ยกเลิก',
    ][$t] ?? $t;
}

function doc_type_badge(string $t): string
{
    $cls = [
        'IN'       => 'ok',
        'OUT'      => 'warn',
        'TRANSFER' => 'info',
        'ADJUST'   => 'muted',
        'PROD'     => 'gold',
        'VOID'     => 'danger',
    ][$t] ?? 'muted';
    return '<span class="badge ' . $cls . '">' . h(doc_type_label($t)) . '</span>';
}

/** ---------- ประเภทสินค้า (MAT / WIP / FG / PACK) ---------- */
const PRODUCT_TYPES = [
    'MAT'   => 'วัตถุดิบ',
    'WIP'   => 'กึ่งสำเร็จรูป',
    'FG'    => 'สินค้าสำเร็จรูป',
    'PACK'  => 'บรรจุภัณฑ์',
    'OTHER' => 'อื่น ๆ',
];

function product_type_label(string $t): string
{
    return PRODUCT_TYPES[$t] ?? $t;
}

function product_type_badge(?string $t): string
{
    $t   = $t ?: 'OTHER';
    $cls = [
        'MAT'   => 'info',
        'WIP'   => 'warn',
        'FG'    => 'gold',
        'PACK'  => 'muted',
        'OTHER' => 'muted',
    ][$t] ?? 'muted';
    return '<span class="badge ' . $cls . '" title="' . h($t) . '">'
         . h(product_type_label($t)) . '</span>';
}

/**
 * ส่งออกตามรูปแบบที่ผู้ใช้เลือก (?export=csv|xls) — ไม่ตรงรูปแบบก็ไม่ทำอะไร
 * $base = ชื่อไฟล์ฐาน (ไม่ต้องมีนามสกุล)
 */
function export_if_requested(string $base, string $title, array $header, array $rows, array $meta = []): void
{
    $fmt = strtolower((string)inp('export'));
    $stamp = date('Ymd');
    if ($fmt === 'xls') {
        xls_download("{$base}_{$stamp}.xls", $title, $header, $rows, $meta);
    } elseif ($fmt === 'csv') {
        csv_download("{$base}_{$stamp}.csv", $header, array_map('array_values', $rows));
    }
}

/** ปุ่มส่งออก CSV + Excel คู่กัน (ใช้ในแถบตัวกรอง) */
function export_buttons(): string
{
    return '<a class="btn ghost" href="' . h(url_with(['export' => 'csv'])) . '">CSV</a>'
         . '<a class="btn ghost" href="' . h(url_with(['export' => 'xls'])) . '">Excel</a>';
}

/**
 * ส่งออก Excel (.xls) — ใช้ตาราง HTML ที่ Excel เปิดได้ตรง ๆ
 * ข้อดีคือได้หัวตาราง/ตัวเลขจัดรูปแบบ และรองรับภาษาไทยเต็มรูปแบบ โดยไม่ต้องพึ่งไลบรารีภายนอก
 *
 * @param array $rows  แต่ละแถวเป็น array ของค่า; ค่าที่เป็นตัวเลขจะจัดชิดขวาให้อัตโนมัติ
 * @param array $meta  บรรทัดหัวเรื่องเหนือตาราง เช่น ['รายงาน' => 'ยอดคงเหลือ', 'ณ วันที่' => '...']
 */
function xls_download(string $filename, string $title, array $header, array $rows, array $meta = []): void
{
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $cols = max(1, count($header));
    echo "\xEF\xBB\xBF";
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8">'
       . '<style>'
       . 'table{border-collapse:collapse;font-family:Tahoma,sans-serif;font-size:11pt}'
       . 'th{background:#1d1740;color:#fff;border:1px solid #999;padding:6px 8px;text-align:left}'
       . 'td{border:1px solid #ccc;padding:5px 8px;vertical-align:top}'
       . '.t{font-size:15pt;font-weight:bold}.m{color:#555}.n{mso-number-format:"#,##0.00\\ ";text-align:right}'
       . '</style></head><body>';

    echo '<table><tr><td class="t" colspan="' . $cols . '">' . h($title) . '</td></tr>';
    foreach ($meta as $k => $v) {
        echo '<tr><td class="m" colspan="' . $cols . '">' . h($k) . ': ' . h($v) . '</td></tr>';
    }
    echo '<tr><td colspan="' . $cols . '"></td></tr></table>';

    echo '<table><thead><tr>';
    foreach ($header as $th) {
        echo '<th>' . h($th) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach (array_values($r) as $v) {
            $isNum = is_int($v) || is_float($v) || (is_string($v) && $v !== '' && is_numeric(str_replace(',', '', $v)));
            echo '<td' . ($isNum ? ' class="n"' : '') . '>' . h($v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

/** ส่งออก CSV (BOM สำหรับ Excel ภาษาไทย) */
function csv_download(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}
