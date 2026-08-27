-- ============================================================
--  ระบบสต๊อกคลังสินค้า (pp_stock)  --  MySQL 5.7+ / MariaDB 10.2+
--  รันไฟล์นี้ผ่าน phpMyAdmin ก็ได้ หรือใช้ install.php ให้สร้างอัตโนมัติ
-- ============================================================

SET NAMES utf8mb4;

-- สร้างและเลือกฐานข้อมูลให้เลย (เผื่อรันตรงใน phpMyAdmin / MySQL client)
-- ถ้าเปลี่ยนชื่อ DB ใน config.php ให้แก้ 2 บรรทัดนี้ตามด้วย
-- install.php จะข้าม 2 บรรทัดนี้และใช้ค่า DB_NAME จาก config.php แทน
CREATE DATABASE IF NOT EXISTS pp_stock
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pp_stock;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------- ผู้ใช้งาน ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  fullname      VARCHAR(120) NOT NULL,
  role          ENUM('admin','staff','viewer') NOT NULL DEFAULT 'staff',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ข้อมูลหลัก ----------
CREATE TABLE IF NOT EXISTS categories (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  note       VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS units (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_units_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warehouses (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code       VARCHAR(20)  NOT NULL,
  name       VARCHAR(120) NOT NULL,
  address    VARCHAR(255) NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_warehouses_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code       VARCHAR(20)  NULL,
  name       VARCHAR(160) NOT NULL,
  phone      VARCHAR(50)  NULL,
  email      VARCHAR(120) NULL,
  address    VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_suppliers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sku         VARCHAR(50)  NOT NULL,
  barcode     VARCHAR(50)  NULL,
  name        VARCHAR(200) NOT NULL,
  -- MAT=วัตถุดิบ, WIP=กึ่งสำเร็จรูป, FG=สินค้าสำเร็จรูป, PACK=บรรจุภัณฑ์
  product_type ENUM('MAT','WIP','FG','PACK','OTHER') NOT NULL DEFAULT 'FG',
  category_id INT UNSIGNED NULL,
  unit_id     INT UNSIGNED NULL,
  cost_price  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  sell_price  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  min_stock   DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  note        VARCHAR(255) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_barcode (barcode),
  KEY idx_products_name (name),
  KEY idx_products_type (product_type),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_unit     FOREIGN KEY (unit_id)     REFERENCES units(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ยอดคงเหลือ (สรุปต่อ สินค้า x คลัง) ----------
CREATE TABLE IF NOT EXISTS stock_balances (
  product_id   INT UNSIGNED NOT NULL,
  warehouse_id INT UNSIGNED NOT NULL,
  qty          DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, warehouse_id),
  KEY idx_balance_wh (warehouse_id),
  CONSTRAINT fk_bal_product   FOREIGN KEY (product_id)   REFERENCES products(id)   ON DELETE CASCADE,
  CONSTRAINT fk_bal_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- เอกสาร (หัวเอกสาร) ----------
CREATE TABLE IF NOT EXISTS stock_docs (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_no          VARCHAR(30) NOT NULL,
  doc_type        ENUM('IN','OUT','TRANSFER','ADJUST','PROD') NOT NULL,
  doc_date        DATE NOT NULL,
  warehouse_id    INT UNSIGNED NOT NULL,
  to_warehouse_id INT UNSIGNED NULL,
  supplier_id     INT UNSIGNED NULL,
  ref_no          VARCHAR(60) NULL,
  contact         VARCHAR(160) NULL,   -- ชื่อลูกค้า / ผู้เบิก
  ship_to         VARCHAR(255) NULL,   -- ที่อยู่จัดส่ง (ใบส่งของ)
  ship_tel        VARCHAR(50)  NULL,
  ship_date       DATE         NULL,
  note            VARCHAR(255) NULL,
  status          ENUM('posted','void') NOT NULL DEFAULT 'posted',
  total_qty       DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  total_amount    DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  user_id         INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  voided_at       DATETIME NULL,
  voided_by       INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_docs_no (doc_no),
  KEY idx_docs_type_date (doc_type, doc_date),
  KEY idx_docs_wh (warehouse_id),
  CONSTRAINT fk_docs_wh   FOREIGN KEY (warehouse_id)    REFERENCES warehouses(id),
  CONSTRAINT fk_docs_wh2  FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_docs_supp FOREIGN KEY (supplier_id)     REFERENCES suppliers(id) ON DELETE SET NULL,
  CONSTRAINT fk_docs_user FOREIGN KEY (user_id)         REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- รายการในเอกสาร ----------
CREATE TABLE IF NOT EXISTS stock_doc_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_id     INT UNSIGNED NOT NULL,
  -- MAIN = รายการหลักของเอกสาร (ใบผลิต = ของที่ผลิตได้), CONSUME = วัตถุดิบที่ใช้ไป
  line_kind  ENUM('MAIN','CONSUME') NOT NULL DEFAULT 'MAIN',
  product_id INT UNSIGNED NOT NULL,
  qty        DECIMAL(14,3) NOT NULL,
  unit_cost  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  note       VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_items_doc (doc_id),
  KEY idx_items_product (product_id),
  CONSTRAINT fk_items_doc     FOREIGN KEY (doc_id)     REFERENCES stock_docs(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- บัญชีการเคลื่อนไหว (ledger, insert-only) ----------
CREATE TABLE IF NOT EXISTS stock_movements (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_id        INT UNSIGNED NULL,
  doc_no        VARCHAR(30) NOT NULL,
  doc_type      ENUM('IN','OUT','TRANSFER','ADJUST','VOID','PROD') NOT NULL,
  product_id    INT UNSIGNED NOT NULL,
  warehouse_id  INT UNSIGNED NOT NULL,
  qty_change    DECIMAL(14,3) NOT NULL,
  balance_after DECIMAL(14,3) NOT NULL,
  unit_cost     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  note          VARCHAR(255) NULL,
  user_id       INT UNSIGNED NULL,
  moved_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mv_product_time (product_id, moved_at),
  KEY idx_mv_wh (warehouse_id),
  KEY idx_mv_doc (doc_id),
  CONSTRAINT fk_mv_doc     FOREIGN KEY (doc_id)       REFERENCES stock_docs(id) ON DELETE SET NULL,
  CONSTRAINT fk_mv_product FOREIGN KEY (product_id)   REFERENCES products(id),
  CONSTRAINT fk_mv_wh      FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- สูตรการผลิต (BOM: FG 1 หน่วย ใช้วัตถุดิบอะไรบ้าง) ----------
CREATE TABLE IF NOT EXISTS bom_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ตัวนับเลขที่เอกสาร ----------
CREATE TABLE IF NOT EXISTS doc_counters (
  prefix  VARCHAR(20) NOT NULL,
  period  VARCHAR(8)  NOT NULL,
  last_no INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (prefix, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- แจ้งเตือนใบสั่งซื้อ (PO) ที่ดึงมาจากอีเมล ----------
CREATE TABLE IF NOT EXISTS po_notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mailbox    VARCHAR(120) NOT NULL DEFAULT '',
  msg_uid    VARCHAR(80)  NOT NULL,          -- UID ของเมลในกล่องนั้น กันดึงซ้ำ
  subject    VARCHAR(255) NOT NULL,
  from_name  VARCHAR(160) NULL,
  from_email VARCHAR(160) NULL,
  po_no      VARCHAR(60)  NULL,              -- เลขที่ PO ที่แกะได้จากหัวเรื่อง
  excerpt    TEXT         NULL,
  n_attach   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  mail_date  DATETIME     NULL,
  is_read    TINYINT(1)   NOT NULL DEFAULT 0,
  read_by    INT UNSIGNED NULL,
  read_at    DATETIME     NULL,
  doc_id     INT UNSIGNED NULL,              -- ใบรับเข้าที่สร้างจาก PO ใบนี้
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_po_msg (mailbox, msg_uid),
  KEY idx_po_unread (is_read, id),
  CONSTRAINT fk_po_doc  FOREIGN KEY (doc_id)  REFERENCES stock_docs(id) ON DELETE SET NULL,
  CONSTRAINT fk_po_user FOREIGN KEY (read_by) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
