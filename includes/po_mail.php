<?php
/**
 * กระดิ่งแจ้งเตือนใบสั่งซื้อ (PO) จากอีเมล
 *
 * ดึงอีเมลจากกล่องที่ตั้งไว้ใน config.php แล้วเก็บเฉพาะฉบับที่ "หัวเรื่องมีคำว่า PO"
 * ลงตาราง po_notifications เพื่อให้กระดิ่งบนแถบบนหยิบไปแสดง
 *
 * ออกแบบให้พังแบบไม่ทำให้ระบบล่ม — ถ้ายังไม่ได้ตั้งค่าหรือไม่มีส่วนขยาย imap
 * ทุกฟังก์ชันจะคืนค่าที่ปลอดภัยพร้อมข้อความอธิบายแทนการโยน error
 */

/** ตรวจว่าพร้อมดึงเมลหรือยัง — คืน [พร้อมหรือไม่, เหตุผล] */
function po_mail_ready(): array
{
    if (!function_exists('imap_open')) {
        return [false, 'PHP ยังไม่ได้เปิดส่วนขยาย imap — เปิดบรรทัด extension=imap ใน php.ini แล้วรีสตาร์ท Apache'];
    }
    if (!MAIL_PO_ENABLED) {
        return [false, 'ยังไม่ได้เปิดใช้งาน — ตั้ง MAIL_PO_ENABLED เป็น true ใน config.php'];
    }
    if (MAIL_PO_USER === '') {
        return [false, 'ยังไม่ได้กรอกอีเมลของกล่องรับ PO — ตั้ง MAIL_PO_USER ใน config.php'];
    }
    if (MAIL_PO_PASS === '') {
        return [false, 'ยังไม่ได้กรอกรหัสผ่านของกล่อง ' . MAIL_PO_USER
                     . ' — ตั้ง MAIL_PO_PASS ใน config.php (Gmail ต้องใช้ App Password 16 หลัก)'];
    }
    return [true, ''];
}

/**
 * หัวเรื่องนี้ถือเป็นใบ PO หรือไม่
 * จับ "PO" ที่ยืนเป็นคำของตัวเอง หรือเกาะกับตัวเลข/ขีด เช่น PO-2026-001, PO#4821, [po 118]
 * แต่ไม่จับคำที่บังเอิญมีตัวอักษรติดกัน เช่น Position, Report, Spot
 */
function po_subject_matches(string $subject, ?string $keyword = null): bool
{
    $kw = trim((string)($keyword ?? MAIL_PO_KEYWORD));
    if ($kw === '') {
        return true;
    }
    $k = preg_quote($kw, '/');
    return (bool)preg_match('/(?<![A-Za-z])' . $k . '(?![A-Za-z])/iu', $subject);
}

/** แกะเลขที่ PO จากหัวเรื่อง เช่น "ใบสั่งซื้อ PO-2026-0148 ด่วน" -> PO-2026-0148 */
function po_extract_no(string $subject, ?string $keyword = null): ?string
{
    $kw = trim((string)($keyword ?? MAIL_PO_KEYWORD));
    $k  = preg_quote($kw === '' ? 'PO' : $kw, '/');
    if (preg_match('/(?<![A-Za-z])(' . $k . '\s*[-#\/_ ]?\s*[A-Za-z0-9][A-Za-z0-9\-\/]*)/iu', $subject, $m)) {
        $no = preg_replace('/\s+/u', '', $m[1]);
        return mb_substr($no, 0, 60, 'UTF-8');
    }
    return null;
}

/** แปลงหัวเรื่อง MIME (=?UTF-8?B?...?=) ให้เป็นข้อความอ่านได้ */
function po_decode_header(?string $raw): string
{
    if ($raw === null || $raw === '') {
        return '';
    }
    $out = '';
    foreach ((array)imap_mime_header_decode($raw) as $part) {
        $cs   = strtoupper($part->charset ?? 'default');
        $text = $part->text ?? '';
        if ($cs !== 'DEFAULT' && $cs !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $conv = @mb_convert_encoding($text, 'UTF-8', $cs);
            $text = $conv !== false ? $conv : $text;
        }
        $out .= $text;
    }
    return trim($out);
}

/** นับไฟล์แนบของเมลหนึ่งฉบับ */
function po_count_attachments($mbox, int $msgNo): int
{
    $st = @imap_fetchstructure($mbox, $msgNo);
    if (!$st || empty($st->parts)) {
        return 0;
    }
    $n = 0;
    foreach ($st->parts as $p) {
        $disp = strtoupper($p->disposition ?? '');
        if ($disp === 'ATTACHMENT' || !empty($p->ifdparameters) || !empty($p->ifparameters)) {
            if ($disp === 'ATTACHMENT') {
                $n++;
            }
        }
    }
    return $n;
}

/**
 * ดึงเมลใหม่เข้ากล่องแจ้งเตือน
 * @return array ['ok'=>bool, 'new'=>int, 'scanned'=>int, 'error'=>string]
 */
function po_fetch(): array
{
    $res = ['ok' => false, 'new' => 0, 'scanned' => 0, 'error' => ''];

    [$ready, $why] = po_mail_ready();
    if (!$ready) {
        $res['error'] = $why;
        return $res;
    }

    $mbox = @imap_open(MAIL_PO_HOST, MAIL_PO_USER, MAIL_PO_PASS, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
    if (!$mbox) {
        $res['error'] = 'เชื่อมต่อกล่องอีเมลไม่ได้: ' . (imap_last_error() ?: 'ไม่ทราบสาเหตุ');
        imap_errors();
        return $res;
    }

    $since = date('d-M-Y', strtotime('-' . max(1, (int)MAIL_PO_DAYS) . ' days'));
    $ids   = @imap_search($mbox, 'SINCE "' . $since . '"', SE_UID);
    if (!$ids) {
        imap_errors();
        imap_close($mbox);
        $res['ok'] = true;
        return $res;
    }

    rsort($ids);
    $ids = array_slice($ids, 0, max(1, (int)MAIL_PO_LIMIT));
    $box = MAIL_PO_HOST;

    foreach ($ids as $uid) {
        $res['scanned']++;
        $msgNo = imap_msgno($mbox, $uid);
        $hdr   = @imap_headerinfo($mbox, $msgNo);
        if (!$hdr) {
            continue;
        }

        $subject = po_decode_header($hdr->subject ?? '');
        if (!po_subject_matches($subject)) {
            continue;
        }
        if (qv('SELECT id FROM po_notifications WHERE mailbox = ? AND msg_uid = ?', [$box, (string)$uid])) {
            continue;   // เคยเก็บแล้ว
        }

        $from      = $hdr->from[0] ?? null;
        $fromName  = $from ? po_decode_header($from->personal ?? '') : '';
        $fromMail  = $from ? strtolower(($from->mailbox ?? '') . '@' . ($from->host ?? '')) : '';
        $body      = (string)@imap_fetchbody($mbox, $msgNo, '1', FT_PEEK);
        $body      = trim(preg_replace('/\s+/u', ' ', strip_tags(quoted_printable_decode($body))));

        q('INSERT INTO po_notifications
             (mailbox, msg_uid, subject, from_name, from_email, po_no, excerpt, n_attach, mail_date)
           VALUES (?,?,?,?,?,?,?,?,?)',
            [
                $box,
                (string)$uid,
                mb_substr($subject, 0, 255, 'UTF-8'),
                mb_substr($fromName, 0, 160, 'UTF-8') ?: null,
                mb_substr($fromMail, 0, 160, 'UTF-8') ?: null,
                po_extract_no($subject),
                mb_substr($body, 0, 600, 'UTF-8') ?: null,
                po_count_attachments($mbox, $msgNo),
                isset($hdr->udate) ? date('Y-m-d H:i:s', (int)$hdr->udate) : null,
            ]);
        $res['new']++;
    }

    imap_errors();
    imap_close($mbox);
    $res['ok'] = true;
    return $res;
}

/** จำนวนใบ PO ที่ยังไม่ได้อ่าน */
function po_unread_count(): int
{
    try {
        return (int)qv('SELECT COUNT(*) FROM po_notifications WHERE is_read = 0', [], 0);
    } catch (PDOException $e) {
        return 0;   // ยังไม่ได้รันตัวอัปเกรดฐานข้อมูล
    }
}

/** รายการแจ้งเตือนล่าสุด */
function po_list(int $limit = 12): array
{
    try {
        return qa('SELECT * FROM po_notifications ORDER BY is_read, COALESCE(mail_date, created_at) DESC, id DESC
                    LIMIT ' . max(1, $limit));
    } catch (PDOException $e) {
        return [];
    }
}

/** ทำเครื่องหมายว่าอ่านแล้ว — ส่ง 0 เพื่ออ่านทั้งหมด */
function po_mark_read(int $id = 0): int
{
    $uid = current_user()['id'] ?? null;
    if ($id > 0) {
        return q('UPDATE po_notifications SET is_read = 1, read_by = ?, read_at = NOW()
                   WHERE id = ? AND is_read = 0', [$uid, $id])->rowCount();
    }
    return q('UPDATE po_notifications SET is_read = 1, read_by = ?, read_at = NOW()
               WHERE is_read = 0', [$uid])->rowCount();
}
