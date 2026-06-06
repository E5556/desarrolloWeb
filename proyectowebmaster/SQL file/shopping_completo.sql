-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: shopping
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `abandoned_carts`
--

DROP TABLE IF EXISTS `abandoned_carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abandoned_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `cart_data` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abandoned_carts`
--

LOCK TABLES `abandoned_carts` WRITE;
/*!40000 ALTER TABLE `abandoned_carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `abandoned_carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `creationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) NOT NULL,
  `role` varchar(30) DEFAULT 'super',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','$2y$10$lSkvaoZn5Eg/RD.Y0foxwOp0VtUCCdB6jGdUvwjfnXAsRC5hKMj56','2017-01-24 16:21:18','01-04-2026 08:02:29 PM','super'),(2,'asesor1','da113d70eb6bba2b1f007869b773907d','2026-05-27 04:35:25','2026-05-26 23:35:25','asesor');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_log`
--

DROP TABLE IF EXISTS `admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_log`
--

LOCK TABLES `admin_log` WRITE;
/*!40000 ALTER TABLE `admin_log` DISABLE KEYS */;
INSERT INTO `admin_log` VALUES (1,'admin','login','Sesión iniciada desde ::1','::1','2026-05-13 22:18:25'),(2,'admin','login','Sesión iniciada desde ::1','::1','2026-05-13 23:14:37'),(3,'admin','login','Sesión iniciada desde ::1','::1','2026-05-18 01:00:08'),(4,'admin','login','Sesión iniciada desde ::1','::1','2026-05-20 23:47:24'),(5,'admin','update_order','Orden #4 → in Process','::1','2026-05-20 23:48:15'),(6,'admin','update_order','Orden #4 → Delivered','::1','2026-05-20 23:51:30'),(7,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 00:05:08'),(8,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 00:22:27'),(9,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 00:34:55'),(10,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 01:18:26'),(11,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 01:23:47'),(12,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 01:27:07'),(13,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 02:39:04'),(14,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 02:55:13'),(15,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 02:55:44'),(16,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 02:56:44'),(17,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 02:58:11'),(18,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 03:01:08'),(19,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 03:03:41'),(20,'admin','login','Sesión iniciada desde ::1','::1','2026-05-21 03:05:33'),(21,'admin','login','Sesión iniciada desde ::1','::1','2026-05-25 22:46:18'),(22,'admin','login','Sesión iniciada desde ::1','::1','2026-05-25 23:36:28'),(23,'admin','login','Sesión iniciada desde ::1','::1','2026-05-27 22:59:26'),(24,'admin','login','Sesión iniciada desde ::1','::1','2026-06-05 23:15:43');
/*!40000 ALTER TABLE `admin_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role_permissions`
--

DROP TABLE IF EXISTS `admin_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_perm` (`admin_id`,`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `keyword` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Nike','brandsimage/nike.jpg','Nike',1,1),(2,'Adidas','brandsimage/adidas.svg','Adidas',2,1),(3,'Philipp Plein','brandsimage/philipp plein.png','Philipp',3,1),(4,'Puma','brandsimage/Puma.jpg','Puma',4,1),(5,'Reebok','brandsimage/Reebok.jpg','Reebok',5,1),(6,'New Balance','brandsimage/new-balance.jpg','New Balance',6,1),(7,'Vans','brandsimage/vans.png','Vans',7,1),(8,'Umbro','brandsimage/umbro.svg','Umbro',8,1),(9,'gucci','brandsimage/brand_1775186484.png','gucci',9,1);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bundle_items`
--

DROP TABLE IF EXISTS `bundle_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bundle_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bundle_items`
--

LOCK TABLES `bundle_items` WRITE;
/*!40000 ALTER TABLE `bundle_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bundle_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bundles`
--

DROP TABLE IF EXISTS `bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bundles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `bundle_price` decimal(10,2) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bundles`
--

LOCK TABLES `bundles` WRITE;
/*!40000 ALTER TABLE `bundles` DISABLE KEYS */;
/*!40000 ALTER TABLE `bundles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_events`
--

DROP TABLE IF EXISTS `cart_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_added` (`added_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_events`
--

LOCK TABLES `cart_events` WRITE;
/*!40000 ALTER TABLE `cart_events` DISABLE KEYS */;
INSERT INTO `cart_events` VALUES (1,7,'hj4or8pon61vg5h9kgsnp12q07',NULL,'2026-05-26 04:02:51'),(2,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 04:15:08'),(3,18,'984k8huum84lfuhkf7u81j5lnf',11,'2026-05-26 04:29:54'),(4,4,'gouvkip988d8s28lboldbtu9jf',1,'2026-05-26 04:44:35'),(5,20,'anllin85t0eogeju0p8guu352e',1,'2026-05-28 04:05:34');
/*!40000 ALTER TABLE `cart_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(255) DEFAULT NULL,
  `categoryDescription` longtext DEFAULT NULL,
  `creationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL,
  `categoryImage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Mujer','Moda femenina — vestidos, blusas, pantalones y más','2026-04-18 03:18:01',NULL,'cat_1_banner.jpg'),(2,'Hombre','Moda masculina — camisas, pantalones, chaquetas y más','2026-04-18 03:18:01',NULL,'cat_2_banner.jpg'),(3,'Accesorios','Bolsos, joyería, relojes y gafas de sol','2026-04-18 03:18:01',NULL,'cat_3_banner.jpg'),(4,'Calzado','Tacones, deportivos, botines y sandalias','2026-04-18 03:18:01',NULL,'cat_4_banner.jpg');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_discounts`
--

DROP TABLE IF EXISTS `category_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `min_qty` int(11) DEFAULT 1,
  `discount_pct` decimal(5,2) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `label` varchar(80) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_discounts`
--

LOCK TABLES `category_discounts` WRITE;
/*!40000 ALTER TABLE `category_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `challenge_progress`
--

DROP TABLE IF EXISTS `challenge_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `challenge_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `period_key` varchar(20) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `rewarded` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cp` (`challenge_id`,`user_id`,`period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `challenge_progress`
--

LOCK TABLES `challenge_progress` WRITE;
/*!40000 ALTER TABLE `challenge_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `challenge_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `challenges`
--

DROP TABLE IF EXISTS `challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `points_reward` int(11) DEFAULT 0,
  `goal_type` varchar(50) DEFAULT NULL,
  `goal_value` int(11) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `period` varchar(20) DEFAULT 'monthly',
  `target_value` int(11) DEFAULT 1,
  `reward_points` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `challenges`
--

LOCK TABLES `challenges` WRITE;
/*!40000 ALTER TABLE `challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `subject` varchar(250) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'fgdfg','e5556@hotmail.com','fgdfgdfg','gdfgdfgdfgdf',1,'2026-05-21 06:13:13');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) NOT NULL DEFAULT 0,
  `uses_count` int(11) NOT NULL DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'BIENVENIDO10','percent',10.00,0.00,100,0,NULL,1,'2026-04-07 03:31:03'),(2,'DESCUENTO20','percent',20.00,50000.00,50,0,'2026-12-31',1,'2026-04-07 03:31:03'),(3,'ENVIOGRATIS','fixed',10000.00,0.00,0,0,NULL,1,'2026-04-07 03:31:03');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_rules`
--

DROP TABLE IF EXISTS `discount_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discount_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `discount_pct` decimal(5,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_rules`
--

LOCK TABLES `discount_rules` WRITE;
/*!40000 ALTER TABLE `discount_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) DEFAULT 'General',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq`
--

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
INSERT INTO `faq` VALUES (1,'General','prueba 1','respuesta prueba 1',1,1);
/*!40000 ALTER TABLE `faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flash_sales`
--

DROP TABLE IF EXISTS `flash_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flash_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `label` varchar(100) DEFAULT 'Flash Sale',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flash_sales`
--

LOCK TABLES `flash_sales` WRITE;
/*!40000 ALTER TABLE `flash_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `flash_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_registries`
--

DROP TABLE IF EXISTS `gift_registries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gift_registries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `occasion` varchar(100) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `token` varchar(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_registries`
--

LOCK TABLES `gift_registries` WRITE;
/*!40000 ALTER TABLE `gift_registries` DISABLE KEYS */;
/*!40000 ALTER TABLE `gift_registries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_registry_items`
--

DROP TABLE IF EXISTS `gift_registry_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gift_registry_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registry_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `purchased_qty` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_registry_items`
--

LOCK TABLES `gift_registry_items` WRITE;
/*!40000 ALTER TABLE `gift_registry_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `gift_registry_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_carousels`
--

DROP TABLE IF EXISTS `home_carousels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_carousels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `search_type` enum('category','subcategory','description') NOT NULL DEFAULT 'category',
  `search_value` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_carousels`
--

LOCK TABLES `home_carousels` WRITE;
/*!40000 ALTER TABLE `home_carousels` DISABLE KEYS */;
INSERT INTO `home_carousels` VALUES (1,'Mujer','category','1',1,1,'2026-04-19 17:25:32'),(2,'Hombre','category','2',2,1,'2026-04-19 17:25:32'),(3,'novedades','description','pantalon',3,1,'2026-04-20 03:47:34');
/*!40000 ALTER TABLE `home_carousels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_chat_messages`
--

DROP TABLE IF EXISTS `live_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `live_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sender` enum('user','admin') DEFAULT 'user',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_chat_messages`
--

LOCK TABLES `live_chat_messages` WRITE;
/*!40000 ALTER TABLE `live_chat_messages` DISABLE KEYS */;
INSERT INTO `live_chat_messages` VALUES (1,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','hola','2026-05-21 07:10:45',0),(2,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','vomo vas?','2026-05-21 07:11:54',0),(3,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','hola tu','2026-05-21 07:12:40',0),(4,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','dima lo todo','2026-05-21 07:12:50',0),(5,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','hola','2026-05-21 07:14:36',0),(6,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','hhhh','2026-05-21 07:14:42',0),(7,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','uyukyuk','2026-05-21 07:14:48',0),(8,'',NULL,'admin','hola','2026-05-21 07:19:49',0),(9,'',NULL,'admin','hhhhh','2026-05-21 07:20:08',0),(10,'',NULL,'admin','111111111111','2026-05-21 07:20:33',0),(11,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','trtrtrtrtrt','2026-05-21 07:20:46',0),(12,'',NULL,'admin','22222222222','2026-05-21 07:20:57',0),(13,'',NULL,'admin','3333333333333','2026-05-21 07:21:18',0),(14,'qsvanaqarestqogsphheu5sh87',NULL,'user','hola test','2026-05-21 07:37:04',1),(15,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'user','iiiiiiiiiiiiiiiiiiii','2026-05-21 07:38:56',1),(16,'qsvanaqarestqogsphheu5sh87',NULL,'admin','iii','2026-05-21 07:39:31',0),(17,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','rocky','2026-05-21 07:39:41',0),(18,'3tfvmrvab1pndek7h6c6sf8p1i',NULL,'admin','tttt','2026-05-21 07:40:52',0),(19,'a65q3691uedfps0n5lhtbamnci',NULL,'user','hola 21','2026-05-21 07:41:53',0),(20,'a65q3691uedfps0n5lhtbamnci',NULL,'user','hola 22','2026-05-21 07:41:59',0),(21,'a65q3691uedfps0n5lhtbamnci',NULL,'user','55555555','2026-05-21 07:42:18',0),(22,'a65q3691uedfps0n5lhtbamnci',NULL,'user','8888888888888','2026-05-21 07:47:32',0),(23,'m40m9b7hivhr0lk8nevc83f830',11,'user','000000000000000','2026-05-21 07:48:04',0),(24,'m40m9b7hivhr0lk8nevc83f830',11,'user','dfgdsfgsdfgsdfgsdfg','2026-05-21 07:48:22',0),(25,'m40m9b7hivhr0lk8nevc83f830',11,'user','trtrtrtrtrtrt','2026-05-21 07:48:32',0),(26,'m40m9b7hivhr0lk8nevc83f830',11,'user','333333333333333333333','2026-05-21 07:48:40',0),(27,'t4r5kncrlpm6aom2lnvu8sa0kn',NULL,'user','tttttttttttttuuuuuuuuuuuuu','2026-05-21 07:49:18',0),(28,'99818vl8rdsqqe72aa7uig2000',11,'user','uiuiuiuiuiuiuiuiui','2026-05-21 07:50:25',0),(29,'3sfpsnk2bi08kep0a94bmq925e',NULL,'user','gfgfgfgfgfgfgfgg','2026-05-21 07:50:50',0),(30,'rstu1e4chco29131qthvrr5kp7',NULL,'user','deslogueado','2026-05-21 07:55:00',0),(31,'6l361aaqg4k70frhktkm2e6pjs',1,'user','555555555555555','2026-05-21 08:03:25',0),(32,'6l361aaqg4k70frhktkm2e6pjs',1,'user','666666666666','2026-05-21 08:03:27',0),(33,'tpunvfmqrtrvbpi2nvg6tdr96f',11,'user','2121212121212','2026-05-21 08:12:47',1),(34,'tpunvfmqrtrvbpi2nvg6tdr96f',NULL,'admin','332323232322','2026-05-21 08:13:02',0),(35,'tpunvfmqrtrvbpi2nvg6tdr96f',11,'user','uiuiuiuiuiui','2026-05-21 08:13:15',1),(36,'tpunvfmqrtrvbpi2nvg6tdr96f',NULL,'admin','uiyuiyuiyuiyuiyuiyu','2026-05-21 08:13:32',0),(37,'tpunvfmqrtrvbpi2nvg6tdr96f',NULL,'admin','8989898989','2026-05-21 08:14:17',0),(38,'bj5acsb6mhnkv2a1qscf1u605h',11,'user','ytytytytytytyty','2026-05-21 08:14:24',0);
/*!40000 ALTER TABLE `live_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_plans`
--

DROP TABLE IF EXISTS `membership_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_days` int(11) DEFAULT 30,
  `benefits` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_plans`
--

LOCK TABLES `membership_plans` WRITE;
/*!40000 ALTER TABLE `membership_plans` DISABLE KEYS */;
INSERT INTO `membership_plans` VALUES (1,'Premium',29900.00,30,'Env├¡o gratis, descuentos exclusivos, acceso anticipado a ofertas',1);
/*!40000 ALTER TABLE `membership_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter`
--

DROP TABLE IF EXISTS `newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `active` tinyint(4) DEFAULT 1,
  `subscribed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter`
--

LOCK TABLES `newsletter` WRITE;
/*!40000 ALTER TABLE `newsletter` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_campaigns`
--

DROP TABLE IF EXISTS `newsletter_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(250) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_sent` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_campaigns`
--

LOCK TABLES `newsletter_campaigns` WRITE;
/*!40000 ALTER TABLE `newsletter_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `supplier_id` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,28,1,2,1,189000.00,'2026-05-27 05:44:35'),(2,29,2,1,2,145000.00,'2026-05-27 05:44:35');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `productId` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `orderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `paymentMethod` varchar(50) DEFAULT NULL,
  `orderStatus` varchar(55) DEFAULT NULL,
  `track_token` varchar(32) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `group_ref` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'5',1,'2026-04-03 10:18:02','COD','Delivered','7b4b6a939cb12da8a0435b27d8a5eb92',NULL,NULL,NULL),(2,1,'12',2,'2026-03-19 10:18:02','Debit / Credit card','Delivered','ec7f893263e651e53308ae6b74d0a2ba',NULL,NULL,NULL),(3,2,'3',1,'2026-03-29 10:18:02','COD','Delivered','b2e9ca67bb0b8e7e50ae1234efc183f5',NULL,NULL,NULL),(4,2,'18',1,'2026-04-16 10:18:02','COD','Delivered','c8f6b00d682feccf17e7945da78fea99','HTTPS://www.perri-perdiste.com',NULL,NULL),(5,3,'7',2,'2026-03-04 10:18:02','Debit / Credit card','Delivered','6c6deaf302fed9f8096bf6240fb48d32',NULL,NULL,NULL),(6,3,'25',1,'2026-04-08 10:18:02','COD','Delivered','1fe1e9fda0ee57fb45317dbf45619591',NULL,NULL,NULL),(7,4,'1',3,'2026-02-17 10:18:02','Debit / Credit card','Delivered','93cc4da6f96380407a1e16a24e025007',NULL,NULL,NULL),(8,4,'30',1,'2026-04-17 10:18:02','COD','in Process','dedf3835e80e4141b4d6bd6d4cc881a3',NULL,NULL,NULL),(9,5,'10',1,'2026-03-24 10:18:02','COD','Delivered','e487b115337232cac283ee1d2153f07d',NULL,NULL,NULL),(10,5,'15',2,'2026-03-14 10:18:02','Debit / Credit card','Delivered','ab010949d35501c1ca769b0757757c34',NULL,NULL,NULL),(11,6,'22',1,'2026-02-27 10:18:02','COD','Delivered','a31d3067e802575b012b18af3ef174d7',NULL,NULL,NULL),(12,6,'8',1,'2026-04-15 10:18:02','COD','in Process','e2fbeaa2f443cb93a76a8f65d70c7b73',NULL,NULL,NULL),(13,7,'4',2,'2026-03-31 10:18:02','Debit / Credit card','Delivered','a4286c8a121a1b902ffb59bb3a3a27cf',NULL,NULL,NULL),(14,7,'33',1,'2026-03-09 10:18:02','COD','Delivered','19129c8c976e36c50e387e9e052298a2',NULL,NULL,NULL),(15,8,'19',1,'2026-02-22 10:18:02','COD','Delivered','91a9484ccba7889478b97e6744d5035e',NULL,NULL,NULL),(16,8,'2',3,'2026-04-06 10:18:02','Debit / Credit card','Delivered','36557e9297f3b78f8ea5a68201983cd0',NULL,NULL,NULL),(17,9,'11',1,'2026-04-14 10:18:02','COD','in Process','10191645fda275b2e71e30ba72c353fd',NULL,NULL,NULL),(18,9,'27',2,'2026-03-27 10:18:02','COD','Delivered','d2acfddd4cc27d2935c0e9c4eb01367c',NULL,NULL,NULL),(19,10,'6',1,'2026-03-11 10:18:02','Debit / Credit card','Delivered','1d2cba2a7f34392ae9d3ac7eeda1be71',NULL,NULL,NULL),(20,10,'14',1,'2026-03-01 10:18:02','COD','Delivered','29319ae6aeb7e218d565b00d2c7914d1',NULL,NULL,NULL),(21,1,'20',2,'2026-02-12 10:18:02','COD','Delivered','210e006b3e089a6e67e1997fefda5f39',NULL,NULL,NULL),(22,2,'9',1,'2026-02-07 10:18:02','Debit / Credit card','Delivered','32dbfe098b32537c232fd33261af218d',NULL,NULL,NULL),(23,3,'31',1,'2026-03-21 10:18:02','COD','Delivered','98f758aa14c0e1dffbb89c4953cc7c2d',NULL,NULL,NULL),(24,4,'16',2,'2026-03-16 10:18:02','COD','Delivered','2a3641954ed8d8f85221e225ac834037',NULL,NULL,NULL),(25,5,'38',1,'2026-03-07 10:18:02','Debit / Credit card','Delivered','e58ab9f15410b50df68d0bc07b8114cf',NULL,NULL,NULL),(26,11,'22',1,'2026-05-26 04:23:14','BankTransfer',NULL,'1bddace8e22903ae8d6764cc5dd95b14',NULL,NULL,NULL),(27,11,'22',1,'2026-05-26 04:26:56','BankTransfer',NULL,'94fcd9e2fcc72753ae27e71fbae85473',NULL,NULL,NULL),(28,1,'1',2,'2026-05-27 05:44:35','Efectivo','Pending',NULL,NULL,2,'test_asesor_001'),(29,1,'2',1,'2026-05-27 05:44:35','Efectivo','Pending',NULL,NULL,2,'test_asesor_001');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordertrackhistory`
--

DROP TABLE IF EXISTS `ordertrackhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordertrackhistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `remark` mediumtext DEFAULT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordertrackhistory`
--

LOCK TABLES `ordertrackhistory` WRITE;
/*!40000 ALTER TABLE `ordertrackhistory` DISABLE KEYS */;
INSERT INTO `ordertrackhistory` VALUES (1,3,'in Process','Order has been Shipped.','2017-03-10 19:36:45'),(2,1,'Delivered','Order Has been delivered','2017-03-10 19:37:31'),(3,3,'Delivered','Product delivered successfully','2017-03-10 19:43:04'),(4,4,'in Process','Product ready for Shipping','2017-03-10 19:50:36'),(5,1,'Delivered','23-568/6516516','2026-03-31 05:55:19'),(6,4,'in Process','11111111111','2026-03-31 05:56:32'),(7,4,'Delivered','2222222222222','2026-03-31 05:56:44'),(8,5,'in Process','ttt','2026-04-03 02:39:58'),(9,5,'Delivered','y','2026-04-07 05:07:58'),(10,6,'Delivered','2222','2026-04-07 05:08:21'),(11,7,'Delivered','2','2026-04-07 05:08:35'),(12,8,'Delivered','5353453','2026-04-07 05:08:51'),(13,4,'in Process','ya merito','2026-05-21 04:48:15'),(14,4,'Delivered','listo por fin entregado','2026-05-21 04:51:30');
/*!40000 ALTER TABLE `ordertrackhistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_otp`
--

DROP TABLE IF EXISTS `password_otp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_otp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_otp`
--

LOCK TABLES `password_otp` WRITE;
/*!40000 ALTER TABLE `password_otp` DISABLE KEYS */;
INSERT INTO `password_otp` VALUES (1,4,'$2y$10$dSU8bUe2QMBgwBCfW8wbRuipJ2ynm4852oerbogVtwgD.pdf8K3fm','2026-04-03 05:55:32',1,NULL,1,'2026-04-02 22:45:32'),(2,4,'$2y$10$/a2XufCCyC2V6ZHBAWu9aekgH3Y5vLtPOhcUu0XX00PoBKNtT3S62','2026-04-03 05:56:36',0,NULL,1,'2026-04-02 22:46:36'),(3,4,'$2y$10$MJKzsbK4nQat1d8/B.ZO/ezdvP1FjY6ZR6nZiaEvlM7yuVSHtTmCq','2026-04-04 01:33:06',0,NULL,1,'2026-04-03 18:23:06'),(4,4,'$2y$10$ydxIAfShoJ3Vt6CNDWyVke/LJXjtdRVfS59Nsg5c16Hvvwndq4WKe','2026-04-04 02:18:04',0,NULL,1,'2026-04-03 19:08:04'),(5,4,'$2y$10$bioJcSgVMoNmEkdG2j4oiOo86sMyUdW5hclMtmYnrJoUvexNw3Fdi','2026-04-04 04:28:42',0,NULL,1,'2026-04-03 21:18:42'),(6,4,'$2y$10$oB/ngifNQRKKKZsr68rqQOAaFH.pgdZPu2uuFcH0uRbiETbiNZSwe','2026-04-04 05:28:20',0,NULL,1,'2026-04-03 22:18:20'),(7,4,'$2y$10$YjXdSypCb4lKZVzk94/y4eg3thdKKDL9.iM1ZkhYgFpo/BZERbTUa','2026-04-04 05:36:06',0,NULL,1,'2026-04-03 22:26:06'),(8,5,'$2y$10$ILqcONtsyjnQ9xvXb7Xi3.QrspEZS.3PoTEyfuev7hZJ8Sj7ju9/2','2026-04-07 04:27:40',0,NULL,1,'2026-04-06 21:17:40');
/*!40000 ALTER TABLE `password_otp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `persistent_cart`
--

DROP TABLE IF EXISTS `persistent_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persistent_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT NULL,
  `customization` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `persistent_cart`
--

LOCK TABLES `persistent_cart` WRITE;
/*!40000 ALTER TABLE `persistent_cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `persistent_cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_customizer`
--

DROP TABLE IF EXISTS `product_customizer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_customizer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `field_label` varchar(120) NOT NULL,
  `field_type` enum('text','textarea','color','select','checkbox') DEFAULT 'text',
  `field_options` text DEFAULT '' COMMENT 'Para select: opciones separadas por |',
  `price_extra` decimal(10,2) DEFAULT 0.00,
  `required` tinyint(1) DEFAULT 0,
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_customizer`
--

LOCK TABLES `product_customizer` WRITE;
/*!40000 ALTER TABLE `product_customizer` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_customizer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_customizer_fields`
--

DROP TABLE IF EXISTS `product_customizer_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_customizer_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `field_type` enum('text','textarea','color','select','checkbox') DEFAULT 'text',
  `label` varchar(200) DEFAULT NULL,
  `options` text DEFAULT NULL,
  `price_extra` decimal(10,2) DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0,
  `required` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_customizer_fields`
--

LOCK TABLES `product_customizer_fields` WRITE;
/*!40000 ALTER TABLE `product_customizer_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_customizer_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) NOT NULL,
  `imageName` varchar(255) NOT NULL,
  `sortOrder` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`productId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_price_history`
--

DROP TABLE IF EXISTS `product_price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `recorded_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pid_date` (`product_id`,`recorded_at`),
  KEY `idx_pid` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_price_history`
--

LOCK TABLES `product_price_history` WRITE;
/*!40000 ALTER TABLE `product_price_history` DISABLE KEYS */;
INSERT INTO `product_price_history` VALUES (1,1,189000.00,'2026-05-18'),(3,3,210000.00,'2026-05-18'),(4,12,195000.00,'2026-05-18'),(5,11,285000.00,'2026-05-18'),(17,7,110000.00,'2026-05-18'),(28,13,245000.00,'2026-05-18'),(29,13,245000.00,'2026-05-21'),(32,2,145000.00,'2026-05-21'),(33,12,195000.00,'2026-05-21'),(37,2,145000.00,'2026-05-26'),(38,3,210000.00,'2026-05-26'),(42,11,285000.00,'2026-05-26'),(43,7,110000.00,'2026-05-26'),(44,10,120000.00,'2026-05-26'),(47,21,265000.00,'2026-05-26'),(50,12,195000.00,'2026-05-26'),(51,15,155000.00,'2026-05-26'),(65,5,135000.00,'2026-05-26'),(68,22,320000.00,'2026-05-26'),(69,18,235000.00,'2026-05-26'),(70,4,98000.00,'2026-05-26'),(72,20,385000.00,'2026-05-28');
/*!40000 ALTER TABLE `product_price_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_questions`
--

DROP TABLE IF EXISTS `product_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `answered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_questions`
--

LOCK TABLES `product_questions` WRITE;
/*!40000 ALTER TABLE `product_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) DEFAULT NULL,
  `variant_value` varchar(100) DEFAULT NULL,
  `price_extra` decimal(10,2) DEFAULT 0.00,
  `stock_qty` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_views`
--

DROP TABLE IF EXISTS `product_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `view_date` date GENERATED ALWAYS AS (cast(`viewed_at` as date)) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_viewed` (`viewed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_views`
--

LOCK TABLES `product_views` WRITE;
/*!40000 ALTER TABLE `product_views` DISABLE KEYS */;
INSERT INTO `product_views` VALUES (1,5,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:18:49','2026-04-18'),(2,35,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:19:13','2026-04-18'),(3,25,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:19:27','2026-04-18'),(4,1,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:19:40','2026-04-18'),(5,36,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:19:47','2026-04-18'),(6,34,'26p2vk7ecm37u2vpp44q19ikos',0,'2026-04-18 03:19:52','2026-04-18'),(7,2,'0kc7t8bfpdb8q0mhn503rnnhuh',1,'2026-04-18 04:00:21','2026-04-18'),(8,2,'s40c6hkrpilja8kof94gm9674e',1,'2026-04-18 16:23:50','2026-04-18'),(9,1,'s40c6hkrpilja8kof94gm9674e',1,'2026-04-18 16:23:58','2026-04-18'),(10,1,'f3k4mee5q77rmq82l9ub0tafqr',0,'2026-04-18 17:58:05','2026-04-18'),(11,1,'hiagb0jgtbh5j9hb8plq3u7md5',0,'2026-04-18 23:07:52','2026-04-18'),(12,1,'8a5k7ns08pa0bhaeli6r8oss4n',0,'2026-04-19 07:23:04','2026-04-19'),(13,15,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 16:48:53','2026-04-19'),(14,2,'ehpv8jql5pkfhfgovt1im4o3ht',0,'2026-04-19 16:49:24','2026-04-19'),(15,2,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 17:04:59','2026-04-19'),(16,1,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 17:05:05','2026-04-19'),(17,7,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 17:32:15','2026-04-19'),(18,1,'gngd60001inf8j822hg3golb38',1,'2026-04-19 17:32:34','2026-04-19'),(19,18,'gngd60001inf8j822hg3golb38',1,'2026-04-19 17:33:50','2026-04-19'),(20,14,'gngd60001inf8j822hg3golb38',1,'2026-04-19 17:36:11','2026-04-19'),(21,39,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 18:12:52','2026-04-19'),(22,39,'gngd60001inf8j822hg3golb38',1,'2026-04-19 18:17:58','2026-04-19'),(23,13,'jbhamkm65c3mun47g236e0boo3',0,'2026-04-19 18:20:55','2026-04-19'),(24,22,'3rpdc99ahb12de1qmec8p86ttv',0,'2026-04-20 03:49:08','2026-04-20'),(25,10,'3rpdc99ahb12de1qmec8p86ttv',0,'2026-04-20 03:52:28','2026-04-20'),(26,1,'',NULL,'2026-05-18 06:40:16','2026-05-18'),(27,1,'',NULL,'2026-05-18 06:40:21','2026-05-18'),(28,3,'',NULL,'2026-05-18 06:40:27','2026-05-18'),(29,12,'',NULL,'2026-05-18 06:40:35','2026-05-18'),(30,11,'',1,'2026-05-18 06:40:48','2026-05-18'),(31,12,'',1,'2026-05-18 06:49:01','2026-05-18'),(32,12,'',1,'2026-05-18 06:49:03','2026-05-18'),(33,11,'',1,'2026-05-18 06:49:07','2026-05-18'),(34,12,'',1,'2026-05-18 06:49:21','2026-05-18'),(35,12,'',1,'2026-05-18 06:49:24','2026-05-18'),(36,11,'',1,'2026-05-18 06:49:30','2026-05-18'),(37,11,'',1,'2026-05-18 06:49:35','2026-05-18'),(38,1,'',1,'2026-05-18 06:49:54','2026-05-18'),(39,11,'',NULL,'2026-05-18 06:49:57','2026-05-18'),(40,3,'',1,'2026-05-18 06:50:14','2026-05-18'),(41,3,'',1,'2026-05-18 06:50:17','2026-05-18'),(42,7,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:51:17','2026-05-18'),(43,7,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:54:00','2026-05-18'),(44,7,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:54:00','2026-05-18'),(45,3,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:55:48','2026-05-18'),(46,3,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:55:48','2026-05-18'),(47,7,'tf6qq84167l50p7etvbjg324qm',NULL,'2026-05-18 06:56:35','2026-05-18'),(48,7,'tf6qq84167l50p7etvbjg324qm',0,'2026-05-18 06:56:35','2026-05-18'),(49,7,'nu02ioqrd2jr4iidcf1545u0rd',NULL,'2026-05-18 06:56:55','2026-05-18'),(50,7,'nu02ioqrd2jr4iidcf1545u0rd',0,'2026-05-18 06:56:55','2026-05-18'),(51,7,'q7k4onc9t2acb3il9qtcpgdj43',NULL,'2026-05-18 06:56:58','2026-05-18'),(52,7,'q7k4onc9t2acb3il9qtcpgdj43',0,'2026-05-18 06:56:58','2026-05-18'),(53,7,'3tn0gk95m8l6e2olkajlfqoksb',NULL,'2026-05-18 06:57:00','2026-05-18'),(54,7,'3tn0gk95m8l6e2olkajlfqoksb',0,'2026-05-18 06:57:00','2026-05-18'),(55,7,'mt9mtth8k77nbrndmedp2s1jt4',NULL,'2026-05-18 06:57:12','2026-05-18'),(56,7,'mt9mtth8k77nbrndmedp2s1jt4',0,'2026-05-18 06:57:12','2026-05-18'),(57,7,'gjina2mfcm7jcmhn01lvdfgtk2',NULL,'2026-05-18 06:57:15','2026-05-18'),(58,7,'gjina2mfcm7jcmhn01lvdfgtk2',0,'2026-05-18 06:57:15','2026-05-18'),(59,7,'3npeapnifcbuhsav2io9dem4ka',NULL,'2026-05-18 06:57:34','2026-05-18'),(60,7,'3npeapnifcbuhsav2io9dem4ka',0,'2026-05-18 06:57:34','2026-05-18'),(61,3,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:57:41','2026-05-18'),(62,13,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:58:35','2026-05-18'),(63,13,'gba46nanf0f0dqr0m4boh61rbv',1,'2026-05-18 06:58:35','2026-05-18'),(64,13,'au0jr76rmvu9jqfsau9lkup4gq',NULL,'2026-05-21 04:28:38','2026-05-21'),(65,13,'au0jr76rmvu9jqfsau9lkup4gq',0,'2026-05-21 04:28:38','2026-05-21'),(66,13,'au0jr76rmvu9jqfsau9lkup4gq',NULL,'2026-05-21 04:28:45','2026-05-21'),(67,13,'au0jr76rmvu9jqfsau9lkup4gq',NULL,'2026-05-21 04:38:19','2026-05-21'),(68,2,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:39:02','2026-05-21'),(69,2,'0qo7lv7himcrddpoeltgm2k4h7',0,'2026-05-21 04:39:02','2026-05-21'),(70,0,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:40:49','2026-05-21'),(71,0,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:40:53','2026-05-21'),(72,0,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:40:54','2026-05-21'),(73,12,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:41:06','2026-05-21'),(74,12,'0qo7lv7himcrddpoeltgm2k4h7',0,'2026-05-21 04:41:06','2026-05-21'),(75,12,'0qo7lv7himcrddpoeltgm2k4h7',NULL,'2026-05-21 04:41:16','2026-05-21'),(76,12,'d0aua2ilsi75ii21bk8tot7r2n',1,'2026-05-21 05:28:49','2026-05-21'),(77,12,'d0aua2ilsi75ii21bk8tot7r2n',1,'2026-05-21 05:28:49','2026-05-21'),(78,12,'3tfvmrvab1pndek7h6c6sf8p1i',11,'2026-05-21 07:28:24','2026-05-21'),(79,12,'3tfvmrvab1pndek7h6c6sf8p1i',11,'2026-05-21 07:28:24','2026-05-21'),(80,2,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:41:50','2026-05-26'),(81,2,'qisng3pd906tf1qld3hn9c0pvq',0,'2026-05-26 03:41:50','2026-05-26'),(82,3,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:42:24','2026-05-26'),(83,3,'qisng3pd906tf1qld3hn9c0pvq',0,'2026-05-26 03:42:24','2026-05-26'),(84,3,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:42:52','2026-05-26'),(85,3,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:42:55','2026-05-26'),(86,3,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:43:00','2026-05-26'),(87,11,'qisng3pd906tf1qld3hn9c0pvq',NULL,'2026-05-26 03:43:07','2026-05-26'),(88,11,'qisng3pd906tf1qld3hn9c0pvq',0,'2026-05-26 03:43:07','2026-05-26'),(89,7,'e8lb2mbbqced0bd2q5ebhtkkpt',NULL,'2026-05-26 03:48:20','2026-05-26'),(90,7,'e8lb2mbbqced0bd2q5ebhtkkpt',0,'2026-05-26 03:48:20','2026-05-26'),(91,10,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:49:03','2026-05-26'),(92,10,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:49:03','2026-05-26'),(93,10,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:49:11','2026-05-26'),(94,10,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:51:10','2026-05-26'),(95,21,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:52:28','2026-05-26'),(96,21,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:52:28','2026-05-26'),(97,21,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:56:05','2026-05-26'),(98,21,'tmt8smve3mr40og006mde0n7v5',11,'2026-05-26 03:56:21','2026-05-26'),(99,12,'tmt8smve3mr40og006mde0n7v5',NULL,'2026-05-26 03:56:27','2026-05-26'),(100,12,'tmt8smve3mr40og006mde0n7v5',0,'2026-05-26 03:56:27','2026-05-26'),(101,15,'tmt8smve3mr40og006mde0n7v5',NULL,'2026-05-26 03:56:50','2026-05-26'),(102,15,'tmt8smve3mr40og006mde0n7v5',0,'2026-05-26 03:56:50','2026-05-26'),(103,7,'gu238t4tp3p3cisg55srqu7nf2',11,'2026-05-26 03:57:06','2026-05-26'),(104,7,'gu238t4tp3p3cisg55srqu7nf2',11,'2026-05-26 03:57:06','2026-05-26'),(105,7,'gu238t4tp3p3cisg55srqu7nf2',11,'2026-05-26 03:59:27','2026-05-26'),(106,7,'gu238t4tp3p3cisg55srqu7nf2',11,'2026-05-26 03:59:28','2026-05-26'),(107,7,'gu238t4tp3p3cisg55srqu7nf2',11,'2026-05-26 03:59:30','2026-05-26'),(108,7,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 03:59:42','2026-05-26'),(109,7,'f4djuk5cr1kcdoo7v98m7b1lmq',0,'2026-05-26 03:59:42','2026-05-26'),(110,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 03:59:53','2026-05-26'),(111,2,'f4djuk5cr1kcdoo7v98m7b1lmq',0,'2026-05-26 03:59:53','2026-05-26'),(112,7,'bt5isi0qip72sv7aa04mthb2si',NULL,'2026-05-26 04:05:10','2026-05-26'),(113,7,'bt5isi0qip72sv7aa04mthb2si',0,'2026-05-26 04:05:10','2026-05-26'),(114,7,'druabu68k0smefm6hskao3046h',NULL,'2026-05-26 04:05:14','2026-05-26'),(115,7,'druabu68k0smefm6hskao3046h',0,'2026-05-26 04:05:14','2026-05-26'),(116,7,'p69n8re6pq0mcnqan5723f57mp',NULL,'2026-05-26 04:07:05','2026-05-26'),(117,7,'p69n8re6pq0mcnqan5723f57mp',0,'2026-05-26 04:07:05','2026-05-26'),(118,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 04:15:05','2026-05-26'),(119,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 04:15:22','2026-05-26'),(120,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 04:15:42','2026-05-26'),(121,2,'f4djuk5cr1kcdoo7v98m7b1lmq',NULL,'2026-05-26 04:16:01','2026-05-26'),(122,5,'70gek6plot331msn02ujjjjtfa',11,'2026-05-26 04:16:35','2026-05-26'),(123,5,'70gek6plot331msn02ujjjjtfa',11,'2026-05-26 04:16:35','2026-05-26'),(124,5,'70gek6plot331msn02ujjjjtfa',11,'2026-05-26 04:18:05','2026-05-26'),(125,5,'70gek6plot331msn02ujjjjtfa',11,'2026-05-26 04:21:04','2026-05-26'),(126,22,'984k8huum84lfuhkf7u81j5lnf',11,'2026-05-26 04:21:17','2026-05-26'),(127,22,'984k8huum84lfuhkf7u81j5lnf',11,'2026-05-26 04:21:17','2026-05-26'),(128,18,'984k8huum84lfuhkf7u81j5lnf',11,'2026-05-26 04:29:52','2026-05-26'),(129,18,'984k8huum84lfuhkf7u81j5lnf',11,'2026-05-26 04:29:52','2026-05-26'),(130,4,'gouvkip988d8s28lboldbtu9jf',1,'2026-05-26 04:42:07','2026-05-26'),(131,4,'gouvkip988d8s28lboldbtu9jf',1,'2026-05-26 04:42:07','2026-05-26'),(132,22,'gouvkip988d8s28lboldbtu9jf',1,'2026-05-26 06:30:16','2026-05-26'),(133,20,'anllin85t0eogeju0p8guu352e',1,'2026-05-28 04:05:32','2026-05-28'),(134,20,'anllin85t0eogeju0p8guu352e',1,'2026-05-28 04:05:32','2026-05-28');
/*!40000 ALTER TABLE `product_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productreviews`
--

DROP TABLE IF EXISTS `productreviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productreviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `quality` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `review` longtext DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `reviewDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productreviews`
--

LOCK TABLES `productreviews` WRITE;
/*!40000 ALTER TABLE `productreviews` DISABLE KEYS */;
INSERT INTO `productreviews` VALUES (1,5,1,5,5,5,'Laura Martínez',NULL,'¡Me encantó!','Excelente calidad, llegó rápido y el empaque era perfecto. Totalmente recomendado.','rejected',1,'2026-04-18 03:18:02',NULL),(2,12,2,4,5,4,'Carlos Rodríguez',NULL,'Muy buena compra','La prenda es exactamente como en las fotos. La tela es de buena calidad. Volvería a comprar.','approved',1,'2026-04-18 03:18:02',NULL),(3,3,3,5,4,5,'María Gómez',NULL,'Hermoso vestido','Me quedó perfecto, el estampado es precioso en persona. La entrega fue muy rápida.','approved',1,'2026-04-18 03:18:02',NULL),(4,7,4,4,4,4,'Andrés Torres',NULL,'Buena chaqueta','La chaqueta es bonita y de buena calidad. El talle es un poco grande, pedir una talla menos.','approved',1,'2026-04-18 03:18:02',NULL),(5,25,5,5,5,5,'Valentina López',NULL,'Bolso increíble','El cuero es genuino y muy resistente. Los acabados son perfectos. Vale cada peso.','approved',1,'2026-04-18 03:18:02',NULL),(6,30,6,5,5,5,'Diego Hernández',NULL,'Reloj espectacular','Se ve muy lujoso, la correa de cuero es suave y la esfera muy elegante. Súper recomendado.','approved',1,'2026-04-18 03:18:02',NULL),(7,1,7,4,4,4,'Camila Sánchez',NULL,'Vestido muy bonito','Me encantó el vestido, la tela es suave y fresca. Lo usé en una boda y recibí muchos elogios.','approved',1,'2026-04-18 03:18:02',NULL),(8,10,8,5,4,5,'Sebastián Vargas',NULL,'Excelentes zapatillas','Muy cómodas desde el primer uso, sin periodo de adaptación. El diseño es muy actual.','approved',0,'2026-04-18 03:18:02',NULL),(9,22,9,4,5,4,'Isabella Moreno',NULL,'Camisa de calidad','La camisa de lino es perfecta para el calor. Se ve elegante y es muy fresca.','approved',1,'2026-04-18 03:18:02',NULL),(10,15,10,5,5,5,'Felipe Castro',NULL,'Sandalias perfectas','Son exactamente como en la foto, muy cómodas y de buena calidad. Las uso todos los días.','approved',1,'2026-04-18 03:18:02',NULL),(11,38,2,4,4,4,'Laura Martínez',NULL,'Buenos deportivos','Muy cómodos para correr, el material es transpirable. El único detalle es que el talle corre grande.','approved',0,'2026-04-18 03:18:02',NULL),(12,9,3,5,5,5,'Carlos Rodríguez',NULL,'Jeans perfectos','Los mejores jeans que he comprado. Se amoldan perfecto al cuerpo y no pierden la forma al lavar.','approved',1,'2026-04-18 03:18:02',NULL),(13,33,4,4,4,5,'María Gómez',NULL,'Gafas lindísimas','Las gafas son preciosas y de buena calidad. La protección UV es real. Llegaron con estuche y paño.','approved',1,'2026-04-18 03:18:02',NULL),(14,19,5,5,4,5,'Andrés Torres',NULL,'Botín muy trendy','Se ven increíbles, el bordado es detallado y bonito. Cómodos para caminar todo el día.','approved',1,'2026-04-18 03:18:02',NULL),(15,27,6,4,5,4,'Valentina López',NULL,'Buen bolso','La mochila es práctica y de buena calidad. El cierre es resistente. Ideal para el trabajo.','approved',0,'2026-04-18 03:18:02',NULL);
/*!40000 ALTER TABLE `productreviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` int(11) NOT NULL,
  `subCategory` int(11) DEFAULT NULL,
  `productName` varchar(255) DEFAULT NULL,
  `productCompany` varchar(255) DEFAULT NULL,
  `productPrice` int(11) DEFAULT NULL,
  `productPriceBeforeDiscount` int(11) DEFAULT NULL,
  `productDescription` longtext DEFAULT NULL,
  `productImage1` varchar(255) DEFAULT NULL,
  `productImage2` varchar(255) DEFAULT NULL,
  `productImage3` varchar(255) DEFAULT NULL,
  `shippingCharge` int(11) DEFAULT NULL,
  `productAvailability` varchar(255) DEFAULT NULL,
  `postingDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL,
  `productPurchasePrice` decimal(10,2) DEFAULT 0.00,
  `hasDiscount` tinyint(1) DEFAULT 0,
  `stock_qty` int(11) DEFAULT 0,
  `supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,'Vestido Floral Primavera','Zara',189000,250000,'Vestido midi de manga corta con estampado floral multicolor. Tela ligera de viscosa, perfecta para ocasiones casuales y formales. Incluye cinturón a juego.','prod1_1.jpg','prod1_2.jpg','prod1_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,500.00,1,0,NULL),(2,1,1,'Vestido Midi Elegante Negro','H&M',145000,180000,'Vestido midi de cuello en V, ideal para cenas y eventos. Tejido con elastano para mayor comodidad. Disponible en talla XS a XL.','prod2_1.jpg','prod2_2.jpg','prod2_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(3,1,1,'Vestido Boho Largo','Mango',210000,280000,'Vestido largo estilo bohemio con bordados artesanales en el cuello. Perfecto para la playa o eventos al aire libre. Tela 100% algodón.<div><ul><li>mujer</li></ul></div>','prod3_1.jpg','prod3_2.jpg','prod3_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(4,1,1,'Vestido Mini Casual','Pull&Bear',98000,130000,'Vestido mini de punto acanalado. Diseño sencillo y versátil. Se puede combinar con sneakers o sandalias.','prod4_1.jpg','prod4_2.jpg','prod4_3.jpg',9000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(5,1,2,'Blusa Seda Marfil','Massimo Dutti',135000,175000,'Blusa de seda con escote en V y mangas tipo globo. Elegante y sofisticada, ideal para el trabajo o una salida especial.','prod5_1.jpg','prod5_2.jpg','prod5_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(6,1,2,'Blusa Estampada Tropical','Bershka',75000,99000,'Blusa de gasa con estampado tropical. Escote cuadrado y mangas cortas con volante. Muy fresca para el verano.','prod6_1.jpg','prod6_2.jpg','prod6_3.jpg',8000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(7,1,2,'Blusa Lino Natural','Mango',110000,145000,'Blusa de lino 100% natural en color neutro. Corte relajado y cómodo, perfecta para el uso diario.','prod7_1.jpg','prod7_2.jpg','prod7_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(8,1,3,'Pantalón Wide Leg Blanco','Zara',165000,210000,'Pantalón de pierna ancha en tejido fluido color blanco roto. Cintura alta con cierre lateral. Ideal para outfits casuales y formales.','prod8_1.jpg','prod8_2.jpg','prod8_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(9,1,3,'Jean Skinny Azul Oscuro','Levi\'s',220000,280000,'Jean skinny de denim premium con lavado oscuro. Cinco bolsillos, cierre con botón y cremallera. Alto porcentaje de elastano para mayor comodidad.','prod9_1.jpg','prod9_2.jpg','prod9_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(10,1,3,'Pantalón Culotte Cuadros','H&M',120000,155000,'Pantalón culotte con estampado de cuadros en tonos neutros. Cierre con elástico en la cintura. Muy cómodo y de tendencia.','prod10_1.jpg','prod10_2.jpg','prod10_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(11,1,4,'Set Deportivo Rosa Neon','Adidas',285000,350000,'Conjunto deportivo de top y leggins en rosa neón. Tela técnica de secado rápido y control de humedad. Ideal para gym y yoga.','prod11_1.jpg','prod11_2.jpg','prod11_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(12,1,4,'Leggins Compresión Negro','Nike',195000,240000,'Leggins de compresión con bolsillo lateral y cintura alta. Tela Dri-FIT de alta tecnología. Soporte máximo para actividades de alta intensidad.','prod12_1.jpg','prod12_2.jpg','prod12_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(13,2,5,'Camisa Oxford Azul Marino','Tommy Hilfiger',245000,310000,'Camisa Oxford de algodón en azul marino. Corte slim fit con botones de nácar. Versátil para ocasiones formales y casuales.','prod13_1.jpg','prod13_2.jpg','prod13_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(14,2,5,'Camisa Lino Beige','Massimo Dutti',215000,270000,'Camisa de lino 100% en tono beige arena. Corte regular y cuello italiano. Perfecta para climas cálidos.','prod14_1.jpg','prod14_2.jpg','prod14_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(15,2,5,'Camisa Franela Cuadros','Levis',155000,195000,'Camisa de franela con estampado de cuadros en rojo y negro. Ideal para un look casual masculino. Bolsillo frontal doble.','prod15_1.jpg','prod15_2.jpg','prod15_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(16,2,5,'Camisa Polo Blanca','Ralph Lauren',185000,230000,'Polo clásico de piqué de algodón. Logo bordado en el pecho. Ideal para un look smart casual en cualquier ocasión.','prod16_1.jpg','prod16_2.jpg','prod16_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(17,2,6,'Chino Khaki Slim','Zara Man',155000,195000,'Pantalón chino en color khaki con corte slim fit. Cierre con botón y cremallera. Combinable con camisas formales o camisetas casuales.','prod17_1.jpg','prod17_2.jpg','prod17_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(18,2,6,'Jean Straight Dark Blue','Levi\'s 511',235000,295000,'Jean de corte recto en azul oscuro lavado. Cinco bolsillos clásicos. Tejido denim rígido de alta calidad. El pantalón básico de todo hombre.','prod18_1.jpg','prod18_2.jpg','prod18_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(19,2,6,'Pantalón Jogger Gris','Nike',175000,215000,'Pantalón jogger en felpa francesa color gris jaspeado. Cintura ajustable y bolsillos laterales con cremallera. Cómodo para el día a día.','prod19_1.jpg','prod19_2.jpg','prod19_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(20,2,7,'Chaqueta Cuero Sintético Negra','Zara Man',385000,480000,'Chaqueta de cuero sintético en negro mate. Cierre con cremallera frontal y bolsillos laterales. Forro interior acolchado.','prod20_1.jpg','prod20_2.jpg','prod20_3.jpg',18000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(21,2,7,'Bomber Verde Militar','Pull&Bear',265000,330000,'Chaqueta bomber en nylon verde militar. Puños y cintura elásticos. Bolsillo en el pecho con cremallera. Look urbano y moderno.','prod21_1.jpg','prod21_2.jpg','prod21_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(22,2,8,'Conjunto Deportivo Gris','Adidas',320000,395000,'Sudadera y pantalón jogger a juego en gris melange. Logo Adidas en contraste blanco. Tela de algodón orgánico.','prod22_1.jpg','prod22_2.jpg','prod22_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(23,2,8,'Camiseta Técnica Azul','Under Armour',125000,155000,'Camiseta de entrenamiento con tecnología HeatGear. Tejido de secado ultra rápido y protección UV. Ideal para running y deportes al aire libre.','prod23_1.jpg','prod23_2.jpg','prod23_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(24,3,9,'Bolso Shopper Cuero Camel','Michael Kors',520000,680000,'Bolso shopper grande en cuero genuino color camel. Asas largas de cuero trenzado. Interior con bolsillo con cremallera y bolsillos laterales.','prod24_1.jpg','prod24_2.jpg','prod24_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(25,3,9,'Clutch Noche Dorado','Zara',185000,235000,'Clutch de noche en tejido metálico dorado. Cierre magnético y cadena desmontable. Capacidad para teléfono, tarjetas y llaves.','prod25_1.jpg','prod25_2.jpg','prod25_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(26,3,9,'Mochila Mini Negra','Calvin Klein',295000,370000,'Mochila mini en nylon negro con logo grabado. Perfecta para el día a día. Compartimento principal con bolsillos internos.','prod26_1.jpg','prod26_2.jpg','prod26_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(27,3,10,'Collar Perlas Naturales','Tous',345000,430000,'Collar de perlas cultivadas con cierre de plata 925. Longitud 45cm ajustable. Viene en caja de regalo.','prod27_1.jpg','prod27_2.jpg','prod27_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(28,3,10,'Aretes Aro Dorados XL','Pandora',125000,155000,'Aretes de aro extra grandes en baño de oro 18k. Diámetro 5cm. Ligeros y cómodos para uso diario.','prod28_1.jpg','prod28_2.jpg','prod28_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(29,3,10,'Pulsera Charm Plata','Pandora',280000,350000,'Pulsera de plata 925 con 3 charms incluidos. Se pueden agregar más charms. Cierre con serpiente característica de la marca.','prod29_1.jpg','prod29_2.jpg','prod29_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(30,3,11,'Reloj Minimalista Blanco','Daniel Wellington',680000,850000,'Reloj de pulsera con esfera blanca y correa de cuero marrón intercambiable. Movimiento de cuarzo. Resistente al agua 3ATM.','prod30_1.jpg','prod30_2.jpg','prod30_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(31,3,11,'Reloj Smartwatch Negro','Samsung Galaxy',1250000,1550000,'Smartwatch con pantalla AMOLED 1.4\". Monitor de ritmo cardíaco, GPS integrado, resistencia al agua IP68. Compatible con Android e iOS.','prod31_1.jpg','prod31_2.jpg','prod31_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(32,3,12,'Gafas Aviador Doradas','Ray-Ban',520000,650000,'Gafas de sol estilo aviador con montura dorada y lentes verdes clásico. Protección UV400. Incluye estuche y paño de limpieza.','prod32_1.jpg','prod32_2.jpg','prod32_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(33,3,12,'Gafas Cat Eye Carey','Hawkers',185000,230000,'Gafas de sol estilo cat-eye en carey con lentes degradados rosas. Montura de acetato premium. Protección UV400 homologada.','prod33_1.jpg','prod33_2.jpg','prod33_3.jpg',0,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(34,4,13,'Stiletto Negro Clásico','Steve Madden',380000,475000,'Stiletto de 10cm en cuero negro con puntera fina. Plantilla acolchada para mayor comodidad. Ideal para eventos formales y cenas.','prod34_1.jpg','prod34_2.jpg','prod34_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(35,4,13,'Mule Tacón Bajo Camel','Zara',245000,305000,'Mule destalonado con tacón cuadrado bajo en color camel. Tira en el empeine con hebilla dorada. Muy cómoda para uso prolongado.','prod35_1.jpg','prod35_2.jpg','prod35_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(36,4,14,'Sneakers Blancas Classic','Adidas Stan Smith',385000,480000,'Zapatillas clásicas Stan Smith en cuero blanco con detalle verde. La zapatilla icónica de todos los tiempos. Suela de goma vulcanizada.','prod36_1.jpg','prod36_2.jpg','prod36_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(37,4,14,'Running Boost Neon','Nike Air Max',465000,580000,'Zapatillas de running con cámara de aire Max visible. Suela React para máxima amortiguación. Upper de mesh transpirable en colorway neon.','prod37_1.jpg','prod37_2.jpg','prod37_3.jpg',15000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(38,4,14,'Sneakers Plataforma Beige','New Balance',420000,525000,'Zapatillas chunky con plataforma de 4cm en color beige. Upper de cuero y mesh. Tendencia dad shoes que no pasa de moda.','prod38_1.jpg','prod38_2.jpg','prod38_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(39,4,15,'Botín Chelsea Camel','Zara',365000,455000,'Botín Chelsea en cuero genuino color camel. Elásticos laterales y lengüeta trasera. Suela de goma antideslizante.','prod39_1.jpg','prod39_2.jpg','prod39_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(40,4,15,'Botín Cowboy Bordado','Pull&Bear',285000,355000,'Botín estilo cowboy con bordados florales en tono turquesa. Punta cuadrada y tacón western. Muy trendy esta temporada.','prod40_1.jpg','prod40_2.jpg','prod40_3.jpg',12000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(41,4,16,'Sandalia Plana Trenzada','Mango',155000,195000,'Sandalia plana en cuero trenzado color cuero natural. Cierre con hebilla dorada en el tobillo. Perfecta para el verano.','prod41_1.jpg','prod41_2.jpg','prod41_3.jpg',9000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL),(42,4,16,'Sandalia Gladiadora Negra','Zara',185000,230000,'Sandalia gladiadora con múltiples tiras que llegan hasta la rodilla. Suela plana de cuero. Un must-have de la temporada.','prod42_1.jpg','prod42_2.jpg','prod42_3.jpg',10000,'In Stock','2026-04-18 03:18:01',NULL,0.00,0,0,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restock_notifications`
--

DROP TABLE IF EXISTS `restock_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restock_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restock_notifications`
--

LOCK TABLES `restock_notifications` WRITE;
/*!40000 ALTER TABLE `restock_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `restock_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') DEFAULT 'pending',
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `refund_method` varchar(50) DEFAULT NULL,
  `refund_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1076 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_logo','assets/images/logos/logo_1774937582.png'),(2,'site_name','Personal Shoppexxx11'),(3,'footer_tagline','cosmetics'),(4,'footer_hours_weekday','08:00 - 18:00'),(5,'footer_hours_saturday','08:00 - 18:00'),(6,'footer_hours_sunday','08:00 - 18:00'),(7,'footer_city','Cl. 72 #72 A 55'),(8,'footer_phone','3222476963'),(9,'footer_email','edw@gmail.com'),(10,'social_facebook','https://www.tiktok.com/@honeyval__4'),(11,'social_twitter','https://www.tiktok.com/@honeyval__4'),(12,'social_linkedin','https://www.tiktok.com/@honeyval__4'),(13,'social_rss','https://www.tiktok.com/@honeyval__4'),(14,'social_pinterest','https://www.tiktok.com/@honeyval__4'),(67,'social_tiktok','https://www.tiktok.com/@honeyval__4'),(180,'smtp_host',''),(181,'smtp_port',''),(182,'smtp_user',''),(183,'smtp_pass',''),(184,'smtp_from',''),(185,'smtp_from_name',''),(246,'site_favicon','assets/images/logos/favicon_1775263010.gif'),(247,'cookie_consent_enabled','1'),(268,'google_client_id',''),(269,'google_client_secret',''),(270,'google_oauth_enabled','0'),(296,'deal_title','Oferta especial del día'),(297,'deal_subtitle','No te pierdas estos precios exclusivos por tiempo limitado'),(298,'deal_end',''),(299,'deal_active','0'),(353,'maintenance_mode','0'),(354,'mp_sandbox','1'),(614,'currency_symbol','$'),(634,'admin_email',''),(635,'review_request_days',''),(636,'cron_token',''),(642,'low_stock_threshold','5'),(643,'mp_access_token',''),(644,'currency_usd_rate',''),(645,'currency_eur_rate',''),(646,'currency_brl_rate',''),(647,'search_synonyms',''),(648,'ga4_measurement_id',''),(649,'meta_pixel_id','');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_zones`
--

DROP TABLE IF EXISTS `shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(100) NOT NULL,
  `departments` text NOT NULL COMMENT 'CSV de departamentos/ciudades',
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_per_kg` decimal(10,2) DEFAULT 0.00,
  `delivery_days_min` tinyint(3) unsigned DEFAULT 1,
  `delivery_days_max` tinyint(3) unsigned DEFAULT 3,
  `free_from` decimal(12,2) DEFAULT NULL COMMENT 'Envío gratis si total >= este valor',
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_zones`
--

LOCK TABLES `shipping_zones` WRITE;
/*!40000 ALTER TABLE `shipping_zones` DISABLE KEYS */;
INSERT INTO `shipping_zones` VALUES (1,'bogota','',0.00,0.00,1,3,NULL,1,'2026-05-18 01:01:52'),(2,'bogota','bogota',20000.00,0.00,1,3,NULL,1,'2026-05-18 01:03:46');
/*!40000 ALTER TABLE `shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sliders`
--

DROP TABLE IF EXISTS `sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sliders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `keyword` varchar(100) DEFAULT '',
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active_from` date DEFAULT NULL,
  `active_to` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sliders`
--

LOCK TABLES `sliders` WRITE;
/*!40000 ALTER TABLE `sliders` DISABLE KEYS */;
INSERT INTO `sliders` VALUES (7,'assets/images/sliders/banner_1.jpg','mujer',1,1,'2026-04-18 03:18:03',NULL,NULL),(8,'assets/images/sliders/banner_2.jpg','hombre',2,1,'2026-04-18 03:18:05',NULL,NULL);
/*!40000 ALTER TABLE `sliders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategory`
--

DROP TABLE IF EXISTS `subcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) DEFAULT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL,
  `subcategoryImage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategory`
--

LOCK TABLES `subcategory` WRITE;
/*!40000 ALTER TABLE `subcategory` DISABLE KEYS */;
INSERT INTO `subcategory` VALUES (1,1,'Vestidos','2026-04-18 03:18:01',NULL,'sub_1.jpg'),(2,1,'Blusas','2026-04-18 03:18:01',NULL,'sub_2.jpg'),(3,1,'Pantalones','2026-04-18 03:18:01',NULL,'sub_3.jpg'),(4,1,'Ropa Deportiva','2026-04-18 03:18:01',NULL,'sub_4.jpg'),(5,2,'Camisas','2026-04-18 03:18:01',NULL,'sub_5.jpg'),(6,2,'Pantalones Hombre','2026-04-18 03:18:01',NULL,'sub_6.jpg'),(7,2,'Chaquetas','2026-04-18 03:18:01',NULL,'sub_7.jpg'),(8,2,'Ropa Deportiva Hombre','2026-04-18 03:18:01',NULL,'sub_8.jpg'),(9,3,'Bolsos','2026-04-18 03:18:01',NULL,'sub_9.jpg'),(10,3,'Joyería','2026-04-18 03:18:01',NULL,'sub_10.jpg'),(11,3,'Relojes','2026-04-18 03:18:01',NULL,'sub_11.jpg'),(12,3,'Gafas de Sol','2026-04-18 03:18:01',NULL,'sub_12.jpg'),(13,4,'Tacones','2026-04-18 03:18:01',NULL,'sub_13.jpg'),(14,4,'Deportivos','2026-04-18 03:18:01',NULL,'sub_14.jpg'),(15,4,'Botines','2026-04-18 03:18:01',NULL,'sub_15.jpg'),(16,4,'Sandalias','2026-04-18 03:18:01',NULL,'sub_16.jpg');
/*!40000 ALTER TABLE `subcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT '',
  `product_id` int(11) DEFAULT NULL,
  `price_monthly` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_pct` tinyint(3) unsigned DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_premium` tinyint(1) DEFAULT 0,
  `free_shipping` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'plan1','plan1',28,100000.00,10,1,'2026-05-14 00:35:15',0,0);
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `next_billing` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `next_billing` (`next_billing`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Proveedor Alpha','Carlos GarcÝa','alpha@proveedor.com','3001234567','Proveedor principal',1),(2,'Distribuidora Beta','MarÝa L¾pez','beta@dist.com','3109876543','Proveedor secundario',1);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_challenges`
--

DROP TABLE IF EXISTS `user_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_challenges`
--

LOCK TABLES `user_challenges` WRITE;
/*!40000 ALTER TABLE `user_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_memberships`
--

DROP TABLE IF EXISTS `user_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_memberships`
--

LOCK TABLES `user_memberships` WRITE;
/*!40000 ALTER TABLE `user_memberships` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT '',
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `ref_key` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_uid_ref` (`userId`,`ref_key`),
  KEY `userId` (`userId`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=704 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notifications`
--

LOCK TABLES `user_notifications` WRITE;
/*!40000 ALTER TABLE `user_notifications` DISABLE KEYS */;
INSERT INTO `user_notifications` VALUES (1,1,'Orden #21 recibida correctamente','order-details.php?orderid=21',0,'2026-05-13 23:15:02','order_21'),(2,1,'Orden #2 recibida correctamente','order-details.php?orderid=2',0,'2026-05-13 23:15:02','order_2'),(3,1,'Orden #1 recibida correctamente','order-details.php?orderid=1',0,'2026-05-13 23:15:02','order_1'),(193,11,'Bomber Verde Militar de tu wishlist está disponible','product-details.php?pid=21',1,'2026-05-25 22:55:52','wl_avail_21'),(218,11,'Orden #27 recibida correctamente','order-details.php?orderid=27',0,'2026-05-25 23:27:20','order_27'),(219,11,'Orden #26 recibida correctamente','order-details.php?orderid=26',0,'2026-05-25 23:27:20','order_26');
/*!40000 ALTER TABLE `user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points`
--

DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_points` (
  `userId` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points`
--

LOCK TABLES `user_points` WRITE;
/*!40000 ALTER TABLE `user_points` DISABLE KEYS */;
INSERT INTO `user_points` VALUES (11,670,'2026-05-25 23:26:56');
/*!40000 ALTER TABLE `user_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points_log`
--

DROP TABLE IF EXISTS `user_points_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_points_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `delta` int(11) NOT NULL,
  `reason` varchar(120) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points_log`
--

LOCK TABLES `user_points_log` WRITE;
/*!40000 ALTER TABLE `user_points_log` DISABLE KEYS */;
INSERT INTO `user_points_log` VALUES (1,11,335,'Compra — checkout rápido','2026-05-25 23:23:14'),(2,11,335,'Compra — checkout rápido','2026-05-25 23:26:56');
/*!40000 ALTER TABLE `user_points_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_segments`
--

DROP TABLE IF EXISTS `user_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_segments` (
  `user_id` int(11) NOT NULL,
  `segments` varchar(255) DEFAULT '',
  `total_orders` int(11) DEFAULT 0,
  `total_spent` decimal(14,2) DEFAULT 0.00,
  `last_order_date` date DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_segments`
--

LOCK TABLES `user_segments` WRITE;
/*!40000 ALTER TABLE `user_segments` DISABLE KEYS */;
INSERT INTO `user_segments` VALUES (1,'VIP',3,1295000.00,'2026-04-03','2026-05-13 23:38:43'),(2,'VIP',3,665000.00,'2026-04-16','2026-05-13 23:38:43'),(3,'VIP',3,1655000.00,'2026-04-08','2026-05-13 23:38:43'),(4,'VIP',3,1617000.00,'2026-04-17','2026-05-13 23:38:43'),(5,'VIP',3,850000.00,'2026-03-24','2026-05-13 23:38:43'),(6,'Regular',2,485000.00,'2026-04-15','2026-05-13 23:38:43'),(7,'Regular',2,381000.00,'2026-03-31','2026-05-13 23:38:43'),(8,'VIP',2,610000.00,'2026-04-06','2026-05-13 23:38:43'),(9,'VIP',2,975000.00,'2026-04-14','2026-05-13 23:38:43'),(10,'Regular',2,290000.00,'2026-03-11','2026-05-13 23:38:43');
/*!40000 ALTER TABLE `user_segments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `userlog`
--

DROP TABLE IF EXISTS `userlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userEmail` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userlog`
--

LOCK TABLES `userlog` WRITE;
/*!40000 ALTER TABLE `userlog` DISABLE KEYS */;
INSERT INTO `userlog` VALUES (1,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 11:18:50','',1),(2,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 11:29:33','',1),(3,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 11:30:11','',1),(4,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 15:00:23','26-02-2017 11:12:06 PM',1),(5,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 18:08:58','',0),(6,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 18:09:41','',0),(7,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 18:10:04','',0),(8,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 18:10:31','',0),(9,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-26 18:13:43','',1),(10,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-27 18:52:58','',0),(11,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-02-27 18:53:07','',1),(12,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-03 18:00:09','',0),(13,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-03 18:00:15','',1),(14,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-06 18:10:26','',1),(15,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-07 12:28:16','',1),(16,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-07 18:43:27','',1),(17,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-07 18:55:33','',1),(18,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-07 19:44:29','',1),(19,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-08 19:21:15','',1),(20,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-15 17:19:38','',1),(21,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-15 17:20:36','15-03-2017 10:50:39 PM',1),(22,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2017-03-16 01:13:57','',1),(23,'hgfhgf@gmass.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2018-04-29 09:30:40','',1),(24,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 05:50:58','31-03-2026 11:21:30 AM',1),(25,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 05:52:27','31-03-2026 11:22:38 AM',1),(26,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 06:01:07','31-03-2026 11:56:34 AM',1),(27,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 06:26:52','31-03-2026 12:04:28 PM',1),(28,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 06:35:16','31-03-2026 12:05:25 PM',1),(29,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 06:40:47','31-03-2026 12:18:45 PM',1),(30,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 06:51:43','31-03-2026 12:43:07 PM',1),(31,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-03-31 07:15:52','31-03-2026 01:04:41 PM',1),(32,'anuj.lpul1@gmail.com','192.168.1.10\0\0\0\0','2026-03-31 07:35:23',NULL,0),(33,'anuj.lpul1@gmail.com','192.168.1.10\0\0\0\0','2026-03-31 07:36:05',NULL,0),(34,'uj.lpul1@gmail.com','192.168.1.10\0\0\0\0','2026-03-31 07:36:31',NULL,0),(35,'anuj.lpu1@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-01 03:34:35',NULL,1),(36,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-03 03:26:02','03-04-2026 08:56:06 AM',1),(37,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 00:07:30',NULL,0),(38,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 00:07:42','04-04-2026 05:37:48 AM',1),(39,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 00:14:15','04-04-2026 05:44:19 AM',1),(40,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 02:20:09','04-04-2026 08:01:21 AM',1),(41,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 03:18:01','04-04-2026 08:48:04 AM',1),(42,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 03:22:47','04-04-2026 08:55:53 AM',1),(43,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-04 03:26:17',NULL,1),(44,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 02:17:46','07-04-2026 08:26:48 AM',1),(45,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 04:05:28',NULL,0),(46,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 04:05:33','07-04-2026 09:35:59 AM',1),(47,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 04:09:40','07-04-2026 09:47:20 AM',1),(48,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 04:18:52','07-04-2026 09:52:41 AM',1),(49,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 04:23:07','07-04-2026 09:57:49 AM',1),(50,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-07 05:01:53',NULL,1),(51,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:29:23',NULL,0),(52,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:29:29',NULL,0),(53,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:30:58',NULL,0),(54,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:31:18',NULL,0),(55,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:31:23',NULL,0),(56,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-04-19 07:31:36',NULL,0),(57,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:39:55',NULL,0),(58,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:40:01',NULL,0),(59,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:41:13',NULL,0),(60,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:41:46',NULL,0),(61,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:41:57',NULL,0),(62,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:42:20',NULL,0),(63,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-14 03:43:09',NULL,1),(64,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 04:38:40',NULL,0),(65,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 04:38:47','21-05-2026 10:08:54 AM',1),(66,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 06:55:52','21-05-2026 01:08:36 PM',1),(67,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 07:41:08','21-05-2026 01:11:47 PM',1),(68,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 07:47:50',NULL,1),(69,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 07:49:49','21-05-2026 01:20:40 PM',1),(70,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 07:56:12',NULL,1),(71,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 08:12:37','21-05-2026 01:43:53 PM',1),(72,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-21 08:14:02',NULL,1),(73,'e5556@hotmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 03:47:57',NULL,0),(74,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 03:48:02','26-05-2026 09:18:09 AM',1),(75,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 03:48:55','26-05-2026 09:26:25 AM',1),(76,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 03:57:01','26-05-2026 09:29:35 AM',1),(77,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 04:16:27','26-05-2026 09:51:06 AM',1),(78,'edwardcuestaandrade@gmail.com','::1\0\0\0\0\0\0\0\0\0\0\0\0\0','2026-05-26 04:21:11',NULL,1);
/*!40000 ALTER TABLE `userlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contactno` bigint(11) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `shippingAddress` longtext DEFAULT NULL,
  `shippingState` varchar(255) DEFAULT NULL,
  `shippingCity` varchar(255) DEFAULT NULL,
  `shippingPincode` int(11) DEFAULT NULL,
  `billingAddress` longtext DEFAULT NULL,
  `billingState` varchar(255) DEFAULT NULL,
  `billingCity` varchar(255) DEFAULT NULL,
  `billingPincode` int(11) DEFAULT NULL,
  `regDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL,
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `birthday` date DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Laura Martínez','laura@gmail.com',3001234567,'$2y$10$PC8JEiePEdlOeEgB/s5HZOI7Ax46bYO./l/FBhjwYPwgkGCuNxw52','Calle 45 #12-30','Cundinamarca','Bogotá',110111,'Calle 45 #12-30','Cundinamarca','Bogotá',110111,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(2,'Carlos Rodríguez','carlos@hotmail.com',3109876543,'$2y$10$MgpZvGKXcpu0TWz1UyfvIuJq66If/xm9Tmnm4qfxmlQhjPMijZg0G','Carrera 70 #5-15','Antioquia','Medellín',50001,'Carrera 70 #5-15','Antioquia','Medellín',50001,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(3,'María Gómez','maria@yahoo.com',3156789012,'$2y$10$BXpXsJ85ZSZ7xc2132fMqOHV2bNBbDsoYk2PpTBLHqdzsxf1zpizm','Avenida El Dorado #68-95','Cundinamarca','Bogotá',110221,'Avenida El Dorado #68-95','Cundinamarca','Bogotá',110221,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(4,'Andrés Torres','andres@gmail.com',3204567890,'$2y$10$1CPMmdlRC7NxF/ZOYoPCxO2OovuvhDgQxopp7NvjgDxkxfW/vAjfm','Calle 93 #14-20','Cundinamarca','Bogotá',110221,'Calle 93 #14-20','Cundinamarca','Bogotá',110221,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(5,'Valentina López','vale@gmail.com',3001122334,'$2y$10$N6A6hWkX/n.OYI3PofRwY.rufsQKUamfQfChn6bVafPVQVQrvvRGu','Carrera 15 #82-65','Cundinamarca','Bogotá',110221,'Carrera 15 #82-65','Cundinamarca','Bogotá',110221,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(6,'Diego Hernández','diego@outlook.com',3215678901,'$2y$10$K5Lr33AEUWy/evSa.5w/xOJ2CUMS/kxq4rIlaEDALyXdxwvFhRNp.','Calle 10 #43-20','Valle del Cauca','Cali',760001,'Calle 10 #43-20','Valle del Cauca','Cali',760001,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(7,'Camila Sánchez','camila@gmail.com',3123456789,'$2y$10$8t3w03jzY8RtrjKw.Qtzx.hQBlOKJxAdR1A6UIVD2jzmsgdhJwN9y','Carrera 43A #5-113','Antioquia','Medellín',50021,'Carrera 43A #5-113','Antioquia','Medellín',50021,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(8,'Sebastián Vargas','sebas@gmail.com',3187654321,'$2y$10$mVMymycMUhABqZTmYJzG6uKBU4hTOOvLcBmazAR1lUa290fL68lim','Calle 72 #10-07','Atlántico','Barranquilla',80001,'Calle 72 #10-07','Atlántico','Barranquilla',80001,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(9,'Isabella Moreno','isa@gmail.com',3009988776,'$2y$10$vH2ySz2uz1z6Sv8EqyGEeOrE.ZoeMGrqWxHtmGlZSgArug.FxoH4G','Carrera 9 #74-08','Cundinamarca','Bogotá',110221,'Carrera 9 #74-08','Cundinamarca','Bogotá',110221,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(10,'Felipe Castro','felipe@gmail.com',3145566778,'$2y$10$7Tei71Bp1veS8SNZDyxK..64PAMj7WJq4mjRyxfBJcvSJEQkHz2WW','Calle 134 #55-30','Cundinamarca','Bogotá',110111,'Calle 134 #55-30','Cundinamarca','Bogotá',110111,'2026-04-18 03:18:01',NULL,NULL,NULL,NULL,0,NULL,NULL),(11,'Edward Cuesta','edwardcuestaandrade@gmail.com',NULL,'$2y$10$AAeDd.MMZVN9K0d1Ved1G.P21woEsnnXJa.p694UYKYBxk2BzVUl2','Cll 42 f bis sur 81 g 17 Bogota','BOGOTA D.C','BOGOTA',111051,NULL,NULL,NULL,NULL,'2026-05-14 03:41:31',NULL,NULL,NULL,NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `productId` int(11) DEFAULT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,11,21,'2026-05-26 03:55:48');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'shopping'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-06  0:12:05
