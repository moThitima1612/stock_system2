-- MySQL dump 10.13  Distrib 5.7.24, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: pp_stock
-- ------------------------------------------------------
-- Server version	5.7.24

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bom_items`
--

DROP TABLE IF EXISTS `bom_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fg_product_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `qty` decimal(14,3) NOT NULL DEFAULT '1.000',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bom` (`fg_product_id`,`material_id`),
  KEY `idx_bom_mat` (`material_id`),
  CONSTRAINT `fk_bom_fg` FOREIGN KEY (`fg_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bom_mat` FOREIGN KEY (`material_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_items`
--

LOCK TABLES `bom_items` WRITE;
/*!40000 ALTER TABLE `bom_items` DISABLE KEYS */;
INSERT INTO `bom_items` VALUES (1,13,2,1.000,NULL,'2026-08-26 18:18:22'),(2,13,5,2.000,NULL,'2026-08-26 18:18:22'),(3,13,4,1.000,NULL,'2026-08-26 18:18:22'),(4,13,10,1.000,NULL,'2026-08-26 18:18:22'),(5,13,11,0.100,NULL,'2026-08-26 18:18:22'),(6,14,3,1.000,NULL,'2026-08-26 18:18:22'),(7,14,5,1.000,NULL,'2026-08-26 18:18:22'),(8,14,10,1.000,NULL,'2026-08-26 18:18:22'),(9,14,12,0.050,NULL,'2026-08-26 18:18:22');
/*!40000 ALTER TABLE `bom_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'อุปกรณ์สำนักงาน','เครื่องเขียนและของใช้ในออฟฟิศ','2026-08-26 18:18:22'),(2,'อุปกรณ์ไอที','คอมพิวเตอร์และอุปกรณ์ต่อพ่วง','2026-08-26 18:18:22'),(3,'วัสดุบรรจุภัณฑ์','กล่อง เทป วัสดุกันกระแทก','2026-08-26 18:18:22'),(4,'ของใช้ทั่วไป',NULL,'2026-08-26 18:18:22');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_counters`
--

DROP TABLE IF EXISTS `doc_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_counters` (
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_no` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`prefix`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_counters`
--

LOCK TABLES `doc_counters` WRITE;
/*!40000 ALTER TABLE `doc_counters` DISABLE KEYS */;
INSERT INTO `doc_counters` VALUES ('AJ','202608',1),('IS','202608',2),('PD','202608',2),('RC','202608',4);
/*!40000 ALTER TABLE `doc_counters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type` enum('MAT','WIP','FG','PACK','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FG',
  `category_id` int(10) unsigned DEFAULT NULL,
  `unit_id` int(10) unsigned DEFAULT NULL,
  `cost_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sell_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `min_stock` decimal(14,3) NOT NULL DEFAULT '0.000',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_barcode` (`barcode`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_type` (`product_type`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_unit` (`unit_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'A4-80',NULL,'กระดาษถ่ายเอกสาร A4 80 แกรม','MAT',1,6,520.00,650.00,10.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(2,'PEN-BL',NULL,'ปากกาลูกลื่น น้ำเงิน 0.5','MAT',1,2,85.00,120.00,20.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(3,'PEN-RD',NULL,'ปากกาลูกลื่น แดง 0.5','MAT',1,2,85.00,120.00,20.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(4,'STPL-10',NULL,'ลวดเย็บกระดาษ เบอร์ 10','MAT',1,2,18.00,30.00,30.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(5,'FILE-A4',NULL,'แฟ้มสันกว้าง A4 สีน้ำเงิน','MAT',1,1,65.00,95.00,25.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(6,'MOUSE-W',NULL,'เมาส์ไร้สาย 2.4GHz','FG',2,1,290.00,450.00,10.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(7,'KB-USB',NULL,'คีย์บอร์ด USB มาตรฐาน','FG',2,1,390.00,590.00,8.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(8,'HDMI-2M',NULL,'สาย HDMI 2 เมตร','FG',2,7,150.00,250.00,15.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(9,'USB-32G',NULL,'แฟลชไดรฟ์ 32GB','FG',2,1,185.00,290.00,20.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(10,'BOX-S',NULL,'กล่องลูกฟูก ขนาดเล็ก','PACK',3,8,12.00,20.00,100.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(11,'TAPE-OPP',NULL,'เทปใส OPP 2 นิ้ว','PACK',3,4,22.00,35.00,50.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(12,'BUBBLE',NULL,'พลาสติกกันกระแทก 65 ซม. x 100 ม.','PACK',3,4,480.00,700.00,5.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(13,'SET-OFFICE',NULL,'ชุดเครื่องเขียนพนักงานใหม่','FG',4,9,247.20,390.00,5.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22'),(14,'SET-GIFT',NULL,'ชุดของขวัญลูกค้า','FG',4,9,186.00,320.00,5.000,NULL,1,'2026-08-26 18:18:22','2026-08-26 18:18:22');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_balances`
--

DROP TABLE IF EXISTS `stock_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_balances` (
  `product_id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `qty` decimal(14,3) NOT NULL DEFAULT '0.000',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`,`warehouse_id`),
  KEY `idx_balance_wh` (`warehouse_id`),
  CONSTRAINT `fk_bal_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bal_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_balances`
--

LOCK TABLES `stock_balances` WRITE;
/*!40000 ALTER TABLE `stock_balances` DISABLE KEYS */;
INSERT INTO `stock_balances` VALUES (1,1,30.000,'2026-08-26 18:18:22'),(1,2,8.000,'2026-08-26 18:18:22'),(2,1,33.000,'2026-08-26 18:18:49'),(2,2,12.000,'2026-08-26 18:18:22'),(3,1,7.000,'2026-08-26 18:18:50'),(3,2,4.000,'2026-08-26 18:18:22'),(4,1,10.000,'2026-08-26 18:18:49'),(5,1,28.000,'2026-08-26 18:18:50'),(5,2,20.000,'2026-08-26 18:18:22'),(6,1,18.000,'2026-08-26 18:18:22'),(6,2,6.000,'2026-08-26 18:18:22'),(7,1,9.000,'2026-08-26 18:18:22'),(7,2,2.000,'2026-08-26 18:18:22'),(8,1,6.000,'2026-08-26 18:18:22'),(8,2,3.000,'2026-08-26 18:18:22'),(9,1,40.000,'2026-08-26 18:36:48'),(9,2,10.000,'2026-08-26 18:18:22'),(10,1,190.000,'2026-08-26 18:18:50'),(10,2,80.000,'2026-08-26 18:18:22'),(11,1,22.800,'2026-08-26 18:18:49'),(11,2,15.000,'2026-08-26 18:18:22'),(12,1,2.600,'2026-08-26 18:18:51'),(12,2,1.000,'2026-08-26 18:18:22'),(13,1,12.000,'2026-08-26 18:18:49'),(14,1,4452.000,'2026-08-26 18:47:08');
/*!40000 ALTER TABLE `stock_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_doc_items`
--

DROP TABLE IF EXISTS `stock_doc_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_doc_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) unsigned NOT NULL,
  `line_kind` enum('MAIN','CONSUME') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MAIN',
  `product_id` int(10) unsigned NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_doc` (`doc_id`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `fk_items_doc` FOREIGN KEY (`doc_id`) REFERENCES `stock_docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_doc_items`
--

LOCK TABLES `stock_doc_items` WRITE;
/*!40000 ALTER TABLE `stock_doc_items` DISABLE KEYS */;
INSERT INTO `stock_doc_items` VALUES (1,1,'MAIN',1,35.000,520.00,NULL),(2,1,'MAIN',2,48.000,85.00,NULL),(3,1,'MAIN',3,15.000,85.00,NULL),(4,1,'MAIN',4,22.000,18.00,NULL),(5,1,'MAIN',5,60.000,65.00,NULL),(6,1,'MAIN',6,18.000,290.00,NULL),(7,1,'MAIN',7,9.000,390.00,NULL),(8,1,'MAIN',8,6.000,150.00,NULL),(9,1,'MAIN',9,40.000,185.00,NULL),(10,1,'MAIN',10,250.000,12.00,NULL),(11,1,'MAIN',11,30.000,22.00,NULL),(12,1,'MAIN',12,3.000,480.00,NULL),(13,2,'MAIN',1,8.000,520.00,NULL),(14,2,'MAIN',2,12.000,85.00,NULL),(15,2,'MAIN',3,4.000,85.00,NULL),(16,2,'MAIN',5,20.000,65.00,NULL),(17,2,'MAIN',6,6.000,290.00,NULL),(18,2,'MAIN',7,2.000,390.00,NULL),(19,2,'MAIN',8,3.000,150.00,NULL),(20,2,'MAIN',9,10.000,185.00,NULL),(21,2,'MAIN',10,80.000,12.00,NULL),(22,2,'MAIN',11,15.000,22.00,NULL),(23,2,'MAIN',12,1.000,480.00,NULL),(24,3,'MAIN',1,5.000,520.00,NULL),(25,3,'MAIN',2,3.000,85.00,NULL),(26,4,'MAIN',10,40.000,12.00,NULL),(27,4,'MAIN',11,6.000,22.00,NULL),(28,5,'CONSUME',2,12.000,85.00,NULL),(29,5,'CONSUME',5,24.000,65.00,NULL),(30,5,'CONSUME',4,12.000,18.00,NULL),(31,5,'CONSUME',10,12.000,12.00,NULL),(32,5,'CONSUME',11,1.200,22.00,NULL),(33,5,'MAIN',13,12.000,247.20,NULL),(34,6,'CONSUME',3,8.000,85.00,NULL),(35,6,'CONSUME',5,8.000,65.00,NULL),(36,6,'CONSUME',10,8.000,12.00,NULL),(37,6,'CONSUME',12,0.400,480.00,NULL),(38,6,'MAIN',14,8.000,186.00,NULL),(39,7,'MAIN',9,5.000,185.00,NULL),(40,9,'MAIN',14,4444.000,186.00,NULL);
/*!40000 ALTER TABLE `stock_doc_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_docs`
--

DROP TABLE IF EXISTS `stock_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_docs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_type` enum('IN','OUT','TRANSFER','ADJUST','PROD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_date` date NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `to_warehouse_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned DEFAULT NULL,
  `ref_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('posted','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'posted',
  `total_qty` decimal(14,3) NOT NULL DEFAULT '0.000',
  `total_amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `voided_at` datetime DEFAULT NULL,
  `voided_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_docs_no` (`doc_no`),
  KEY `idx_docs_type_date` (`doc_type`,`doc_date`),
  KEY `idx_docs_wh` (`warehouse_id`),
  KEY `fk_docs_wh2` (`to_warehouse_id`),
  KEY `fk_docs_supp` (`supplier_id`),
  KEY `fk_docs_user` (`user_id`),
  CONSTRAINT `fk_docs_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_docs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_docs_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `fk_docs_wh2` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_docs`
--

LOCK TABLES `stock_docs` WRITE;
/*!40000 ALTER TABLE `stock_docs` DISABLE KEYS */;
INSERT INTO `stock_docs` VALUES (1,'RC-202608-0001','IN','2026-08-12',1,NULL,1,'OPENING-WH01','ระบบ','ยอดยกมาเริ่มต้นระบบ','posted',536.000,49981.00,1,'2026-08-26 18:18:22',NULL,NULL),(2,'RC-202608-0002','IN','2026-08-12',2,NULL,1,'OPENING-WH02','ระบบ','ยอดยกมาเริ่มต้นระบบ','posted',161.000,13410.00,1,'2026-08-26 18:18:22',NULL,NULL),(3,'IS-202608-0001','OUT','2026-08-19',1,NULL,NULL,NULL,'ฝ่ายบัญชี','เบิกใช้ภายใน','posted',8.000,2855.00,1,'2026-08-26 18:18:22',NULL,NULL),(4,'IS-202608-0002','OUT','2026-08-24',1,NULL,NULL,NULL,'ฝ่ายการตลาด','เบิกใช้ภายใน','posted',46.000,612.00,1,'2026-08-26 18:18:22',NULL,NULL),(5,'PD-202608-0001','PROD','2026-08-24',1,NULL,NULL,'WO-2026-001',NULL,NULL,'posted',12.000,2966.40,1,'2026-08-26 18:18:49',NULL,NULL),(6,'PD-202608-0002','PROD','2026-08-25',1,NULL,NULL,'WO-2026-002',NULL,NULL,'posted',8.000,1488.00,1,'2026-08-26 18:18:50',NULL,NULL),(7,'RC-202608-0003','IN','2026-08-26',1,NULL,NULL,'APACHE-TEST',NULL,NULL,'void',5.000,925.00,1,'2026-08-26 18:36:25','2026-08-26 18:36:48',1),(8,'AJ-202608-0001','ADJUST','2026-08-26',1,NULL,NULL,NULL,NULL,NULL,'posted',0.000,0.00,1,'2026-08-26 18:41:39',NULL,NULL),(9,'RC-202608-0004','IN','2026-08-26',1,NULL,NULL,NULL,NULL,NULL,'posted',4444.000,826584.00,1,'2026-08-26 18:47:08',NULL,NULL);
/*!40000 ALTER TABLE `stock_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) unsigned DEFAULT NULL,
  `doc_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_type` enum('IN','OUT','TRANSFER','ADJUST','VOID','PROD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `qty_change` decimal(14,3) NOT NULL,
  `balance_after` decimal(14,3) NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `moved_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mv_product_time` (`product_id`,`moved_at`),
  KEY `idx_mv_wh` (`warehouse_id`),
  KEY `idx_mv_doc` (`doc_id`),
  CONSTRAINT `fk_mv_doc` FOREIGN KEY (`doc_id`) REFERENCES `stock_docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_mv_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,'RC-202608-0001','IN',1,1,35.000,35.000,520.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(2,1,'RC-202608-0001','IN',2,1,48.000,48.000,85.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(3,1,'RC-202608-0001','IN',3,1,15.000,15.000,85.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(4,1,'RC-202608-0001','IN',4,1,22.000,22.000,18.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(5,1,'RC-202608-0001','IN',5,1,60.000,60.000,65.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(6,1,'RC-202608-0001','IN',6,1,18.000,18.000,290.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(7,1,'RC-202608-0001','IN',7,1,9.000,9.000,390.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(8,1,'RC-202608-0001','IN',8,1,6.000,6.000,150.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(9,1,'RC-202608-0001','IN',9,1,40.000,40.000,185.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(10,1,'RC-202608-0001','IN',10,1,250.000,250.000,12.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(11,1,'RC-202608-0001','IN',11,1,30.000,30.000,22.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(12,1,'RC-202608-0001','IN',12,1,3.000,3.000,480.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(13,2,'RC-202608-0002','IN',1,2,8.000,8.000,520.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(14,2,'RC-202608-0002','IN',2,2,12.000,12.000,85.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(15,2,'RC-202608-0002','IN',3,2,4.000,4.000,85.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(16,2,'RC-202608-0002','IN',5,2,20.000,20.000,65.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(17,2,'RC-202608-0002','IN',6,2,6.000,6.000,290.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(18,2,'RC-202608-0002','IN',7,2,2.000,2.000,390.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(19,2,'RC-202608-0002','IN',8,2,3.000,3.000,150.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(20,2,'RC-202608-0002','IN',9,2,10.000,10.000,185.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(21,2,'RC-202608-0002','IN',10,2,80.000,80.000,12.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(22,2,'RC-202608-0002','IN',11,2,15.000,15.000,22.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(23,2,'RC-202608-0002','IN',12,2,1.000,1.000,480.00,'ยอดยกมา',1,'2026-08-12 09:00:00'),(24,3,'IS-202608-0001','OUT',1,1,-5.000,30.000,520.00,'ฝ่ายบัญชี',1,'2026-08-19 14:30:00'),(25,3,'IS-202608-0001','OUT',2,1,-3.000,45.000,85.00,'ฝ่ายบัญชี',1,'2026-08-19 14:30:00'),(26,4,'IS-202608-0002','OUT',10,1,-40.000,210.000,12.00,'ฝ่ายการตลาด',1,'2026-08-24 14:30:00'),(27,4,'IS-202608-0002','OUT',11,1,-6.000,24.000,22.00,'ฝ่ายการตลาด',1,'2026-08-24 14:30:00'),(28,5,'PD-202608-0001','PROD',2,1,-12.000,33.000,85.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:49'),(29,5,'PD-202608-0001','PROD',5,1,-24.000,36.000,65.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:49'),(30,5,'PD-202608-0001','PROD',4,1,-12.000,10.000,18.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:49'),(31,5,'PD-202608-0001','PROD',10,1,-12.000,198.000,12.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:49'),(32,5,'PD-202608-0001','PROD',11,1,-1.200,22.800,22.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:49'),(33,5,'PD-202608-0001','PROD',13,1,12.000,12.000,247.20,'ผลิตเข้าคลัง',1,'2026-08-26 18:18:49'),(34,6,'PD-202608-0002','PROD',3,1,-8.000,7.000,85.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:50'),(35,6,'PD-202608-0002','PROD',5,1,-8.000,28.000,65.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:50'),(36,6,'PD-202608-0002','PROD',10,1,-8.000,190.000,12.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:50'),(37,6,'PD-202608-0002','PROD',12,1,-0.400,2.600,480.00,'ใช้ในการผลิต',1,'2026-08-26 18:18:51'),(38,6,'PD-202608-0002','PROD',14,1,8.000,8.000,186.00,'ผลิตเข้าคลัง',1,'2026-08-26 18:18:51'),(39,7,'RC-202608-0003','IN',9,1,5.000,45.000,185.00,NULL,1,'2026-08-26 18:36:25'),(40,7,'RC-202608-0003','VOID',9,1,-5.000,40.000,185.00,'ยกเลิกเอกสาร RC-202608-0003',1,'2026-08-26 18:36:48'),(41,9,'RC-202608-0004','IN',14,1,4444.000,4452.000,186.00,NULL,1,'2026-08-26 18:47:08');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP01','บริษัท ออฟฟิศเมท ซัพพลาย จำกัด','02-111-2233','sales@officemate.example',NULL,'2026-08-26 18:18:22'),(2,'SUP02','ร้านไอทีโซลูชั่น','081-234-5678','contact@itsol.example',NULL,'2026-08-26 18:18:22'),(3,'SUP03','โรงงานกล่องกระดาษไทย','034-555-000',NULL,NULL,'2026-08-26 18:18:22'),(4,'1212','1111','111111','contact@itsol.example','กก','2026-08-26 18:54:47');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_units_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'ชิ้น','2026-08-26 18:18:22'),(2,'กล่อง','2026-08-26 18:18:22'),(3,'แพ็ค','2026-08-26 18:18:22'),(4,'ม้วน','2026-08-26 18:18:22'),(5,'กิโลกรัม','2026-08-26 18:18:22'),(6,'ลัง','2026-08-26 18:18:22'),(7,'เส้น','2026-08-26 18:18:22'),(8,'ใบ','2026-08-26 18:18:22'),(9,'ชุด','2026-08-26 18:18:22');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','staff','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$FM1btDHtGOIOh3wwv8sm3O2BfCfd2ZyaW08SpO2Lz4b06dgp44P7m','ผู้ดูแลระบบ','admin',1,'2026-08-26 18:58:17','2026-08-26 18:18:22');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouses_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'WH01','คลังกลาง สำนักงานใหญ่','อาคาร A ชั้น 1',1,'2026-08-26 18:18:22'),(2,'WH02','คลังสาขาย่อย','อาคาร B ชั้น 2',1,'2026-08-26 18:18:22');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'pp_stock'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-26 18:58:32
