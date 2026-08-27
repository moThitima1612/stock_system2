<?php
/**
 * อัปเกรดโครงสร้างฐานข้อมูลแบบปลอดภัย (idempotent)
 * เรียกซ้ำกี่ครั้งก็ได้ ข้อมูลเดิมไม่หาย — ใช้ทั้งตอนติดตั้งใหม่และอัปเกรดของเดิม
 */

function mig_table_exists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables
                          WHERE table_schema = DATABASE() AND table_name = ?');
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}

function mig_col_exists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
                          WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}

function mig_col_type(PDO $pdo, string $table, string $col): string
{
    $st = $pdo->prepare('SELECT COLUMN_TYPE FROM information_schema.columns
                          WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $col]);
    return (string)$st->fetchColumn();
}

/** @return string[] รายการขั้นตอนที่เพิ่งถูกนำไปใช้ */
function apply_migrations(PDO $pdo): array
{
    $done = [];

    /* ---- 002: ประเภทสินค้า MAT / WIP / FG / PACK ---- */
    if (mig_table_exists($pdo, 'products') && !mig_col_exists($pdo, 'products', 'product_type')) {
        $pdo->exec("ALTER TABLE products
                      ADD COLUMN product_type ENUM('MAT','WIP','FG','PACK','OTHER')
                          NOT NULL DEFAULT 'FG' AFTER name,
                      ADD INDEX idx_products_type (product_type)");
        $done[] = 'products.product_type';
    }

    /* ---- 002: สูตรการผลิต (BOM) ---- */
    if (!mig_table_exists($pdo, 'bom_items')) {
        $pdo->exec("CREATE TABLE bom_items (
              id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
              fg_product_id INT UNSIGNED NOT NULL,
              material_id   INT UNSIGNED NOT NULL,
              qty           DECIMAL(14,3) NOT NULL DEFAULT 1.000,
              note          VARCHAR(255) NULL,
              created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_bom (fg_product_id, material_id),
              KEY idx_bom_mat (material_id),
              CONSTRAINT fk_bom_fg  FOREIGN KEY (fg_product_id) REFERENCES products(id) ON DELETE CASCADE,
              CONSTRAINT fk_bom_mat FOREIGN KEY (material_id)   REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done[] = 'bom_items';
    }

    /* ---- 002: เอกสารชนิดใบผลิต (PROD) ---- */
    if (mig_table_exists($pdo, 'stock_docs')
        && strpos(mig_col_type($pdo, 'stock_docs', 'doc_type'), 'PROD') === false) {
        $pdo->exec("ALTER TABLE stock_docs
                      MODIFY doc_type ENUM('IN','OUT','TRANSFER','ADJUST','PROD') NOT NULL");
        $done[] = 'stock_docs.doc_type+PROD';
    }
    if (mig_table_exists($pdo, 'stock_movements')
        && strpos(mig_col_type($pdo, 'stock_movements', 'doc_type'), 'PROD') === false) {
        $pdo->exec("ALTER TABLE stock_movements
                      MODIFY doc_type ENUM('IN','OUT','TRANSFER','ADJUST','VOID','PROD') NOT NULL");
        $done[] = 'stock_movements.doc_type+PROD';
    }

    /* ---- 002: แยกบรรทัด "ผลได้" กับ "วัตถุดิบที่ใช้" ในใบผลิต ---- */
    if (mig_table_exists($pdo, 'stock_doc_items') && !mig_col_exists($pdo, 'stock_doc_items', 'line_kind')) {
        $pdo->exec("ALTER TABLE stock_doc_items
                      ADD COLUMN line_kind ENUM('MAIN','CONSUME') NOT NULL DEFAULT 'MAIN' AFTER doc_id");
        $done[] = 'stock_doc_items.line_kind';
    }

    /* ---- 003: ข้อมูลสำหรับออกใบส่งของ ---- */
    if (mig_table_exists($pdo, 'stock_docs') && !mig_col_exists($pdo, 'stock_docs', 'ship_to')) {
        $pdo->exec("ALTER TABLE stock_docs
                      ADD COLUMN ship_to   VARCHAR(255) NULL AFTER contact,
                      ADD COLUMN ship_tel  VARCHAR(50)  NULL AFTER ship_to,
                      ADD COLUMN ship_date DATE         NULL AFTER ship_tel");
        $done[] = 'stock_docs.ship_to/ship_tel/ship_date';
    }

    /* ---- 004: กล่องแจ้งเตือนใบ PO ที่ดึงมาจากอีเมล ---- */
    if (!mig_table_exists($pdo, 'po_notifications')) {
        $pdo->exec("CREATE TABLE po_notifications (
              id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
              mailbox      VARCHAR(120)  NOT NULL DEFAULT '',
              msg_uid      VARCHAR(80)   NOT NULL,
              subject      VARCHAR(255)  NOT NULL,
              from_name    VARCHAR(160)  NULL,
              from_email   VARCHAR(160)  NULL,
              po_no        VARCHAR(60)   NULL,
              excerpt      TEXT          NULL,
              n_attach     TINYINT UNSIGNED NOT NULL DEFAULT 0,
              mail_date    DATETIME      NULL,
              is_read      TINYINT(1)    NOT NULL DEFAULT 0,
              read_by      INT UNSIGNED  NULL,
              read_at      DATETIME      NULL,
              doc_id       INT UNSIGNED  NULL,
              created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_po_msg (mailbox, msg_uid),
              KEY idx_po_unread (is_read, id),
              CONSTRAINT fk_po_doc  FOREIGN KEY (doc_id)  REFERENCES stock_docs(id) ON DELETE SET NULL,
              CONSTRAINT fk_po_user FOREIGN KEY (read_by) REFERENCES users(id)      ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done[] = 'po_notifications';
    }

    return $done;
}
