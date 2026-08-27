<?php
/**
 * รันสคริปต์ SQL หลายคำสั่งผ่าน PDO
 * (ตัดคอมเมนต์บรรทัด -- และ # ออกก่อน แล้วแยกคำสั่งด้วย ;)
 * ใช้ได้กับ schema.sql ของโปรเจกต์นี้ที่ไม่มี stored procedure / DELIMITER
 */
function run_sql_script(PDO $pdo, string $sql, bool $skipDbStatements = false): int
{
    $keep = [];
    foreach (preg_split('/\R/', $sql) as $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '#')) {
            continue;
        }
        $keep[] = $line;
    }

    $count = 0;
    foreach (explode(';', implode("\n", $keep)) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        // ผู้เรียกเลือกฐานข้อมูลเองแล้ว (จาก config.php) จึงข้าม CREATE DATABASE / USE ในไฟล์
        if ($skipDbStatements && preg_match('/^(CREATE\s+DATABASE|USE)\b/i', $stmt)) {
            continue;
        }
        $pdo->exec($stmt);
        $count++;
    }
    return $count;
}
