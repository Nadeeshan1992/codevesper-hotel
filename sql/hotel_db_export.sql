-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: gateway01.ap-northeast-1.prod.aws.tidbcloud.com    Database: hotel_db
-- ------------------------------------------------------
-- Server version	8.0.11-TiDB-v8.5.3-serverless

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=60041;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'System','Database Initialization','Hotel management system initialized with 13 rooms, 4 roles and default catalog.',NULL,'2026-10-25 14:44:16'),(2,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 15:10:22'),(3,4,'Auth','User Login','User waiter logged into the system.','::1','2026-10-25 15:10:46'),(4,4,'Auth','User Login','User waiter logged into the system.','::1','2026-10-25 15:12:15'),(5,4,'POS','Order Placed','Order ORD-261025-2118 created by Waiter waiter','::1','2026-10-25 15:12:16'),(6,4,'Auth','User Login','User waiter logged into the system.','::1','2026-10-25 15:13:54'),(7,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 15:14:10'),(8,1,'Privileges','Permission Updated','Updated permission ID 4 for Role ID 4 to status 1','::1','2026-10-25 15:14:10'),(9,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 15:14:41'),(10,1,'Billing','Folio Checkout','Checked out guest Alexander Wright from Room 102 with invoice INV-20261025-8815. Room set to Dirty.','::1','2026-10-25 15:14:41'),(11,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 15:16:19'),(12,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 15:28:31'),(13,1,'Billing','Folio Checkout','Checked out guest Elena Rostova from Room 201 with invoice INV-20261025-3258. Room set to Dirty.','::1','2026-10-25 15:35:04'),(14,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:01:38'),(15,1,'Rooms','Room Created','Added new Room 401 on floor 4 at $250/night','::1','2026-10-25 16:01:38'),(16,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:02:24'),(17,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:03:54'),(18,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:04:59'),(19,1,'Rooms','Room Updated','Updated details for Suite 401-A','::1','2026-10-25 16:04:59'),(20,1,'Rooms','Room Deleted','Deleted Suite 401- from property inventory','::1','2026-10-25 16:04:59'),(21,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:23:13'),(22,4,'Auth','User Login','User waiter logged into the system.','::1','2026-10-25 16:24:29'),(23,4,'POS','Order Placed','Order ORD-261025-8659 created by Waiter waiter','::1','2026-10-25 16:24:29'),(24,1,'Auth','User Login','User admin logged into the system.','::1','2026-10-25 16:50:04'),(25,4,'POS','Order Placed','Order ORD-261025-4659 created by Waiter waiter','127.0.0.1','2026-10-25 17:10:04'),(26,4,'POS','Order Placed','Order ORD-261025-9905 created by Waiter waiter','127.0.0.1','2026-10-25 17:32:13'),(27,1,'POS','Order Placed','Order ORD-261025-1404 created by Waiter admin','::1','2026-10-25 17:33:11'),(28,1,'KOT','Status Changed','Order #7 changed status to ready','::1','2026-10-25 17:34:31'),(29,1,'Rooms','Room Created','Added new 14 on floor 1 at $5000/night','::1','2026-10-25 17:35:39'),(30,1,'POS','Room Dining Charged','Order ORD-261025-7829 of $50.05 charged to Room ID 2','::1','2026-10-25 17:36:18'),(31,1,'Rooms','Status Changed','Room ID 2 status changed to vacant by admin','::1','2026-10-25 17:46:24'),(32,1,'POS','Room Dining Charged','Order ORD-261025-5074 of $89.10 charged to Room ID 10','::1','2026-10-25 17:48:04'),(33,1,'Billing','Folio Checkout','Checked out guest Marcus Vance from Room 205 with invoice INV-20261025-5179. Room set to Dirty.','::1','2026-10-25 17:48:48'),(34,1,'Auth','User Logout','User signed out from terminal.','::1','2026-10-25 17:49:00'),(35,2,'Auth','User Login','User owner logged into the system.','::1','2026-10-25 17:49:08'),(36,2,'Auth','User Logout','User signed out from terminal.','::1','2026-10-25 17:50:18'),(37,4,'Auth','User Login','User waiter logged into the system.','::1','2026-10-25 17:50:36'),(38,4,'Auth','User Logout','User signed out from terminal.','::1','2026-10-25 17:52:57'),(39,3,'Auth','User Login','User reception logged into the system.','::1','2026-10-25 17:53:01'),(40,3,'FrontDesk','Walk-in Check-in','Checked in guest nadeeshan thilina into Room 101','::1','2026-10-25 17:53:43'),(30041,1,'Auth','User Login','User admin logged into the system.','127.0.0.1','2026-10-26 02:46:09'),(30042,1,'Billing','Folio Checkout','Checked out guest nadeeshan thilina from Room 101 with invoice INV-20261026-2398. Room set to Dirty.','127.0.0.1','2026-10-26 02:54:51'),(30043,1,'Billing','Folio Checkout','Checked out guest nadeeshan thilina from Room 101 with invoice INV-20261026-5569. Room set to Dirty.','127.0.0.1','2026-10-26 02:54:53');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `check_ins`
--

DROP TABLE IF EXISTS `check_ins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_ins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `room_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `check_in_time` datetime NOT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `deposit_paid` decimal(10,2) DEFAULT '0.00',
  `status` enum('active','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `reservation_id` (`reservation_id`),
  KEY `room_id` (`room_id`),
  KEY `guest_id` (`guest_id`),
  CONSTRAINT `check_ins_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  CONSTRAINT `check_ins_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `check_ins_ibfk_3` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30005;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `check_ins`
--

LOCK TABLES `check_ins` WRITE;
/*!40000 ALTER TABLE `check_ins` DISABLE KEYS */;
INSERT INTO `check_ins` VALUES (1,1,2,1,'2026-10-25 20:14:15','2026-10-28 20:14:15','2026-10-25 20:44:41',120.00,100.00,'completed',3),(2,2,6,2,'2026-10-25 20:14:15','2026-10-27 20:14:15','2026-10-25 21:05:04',120.00,120.00,'completed',3),(3,3,10,3,'2026-10-25 20:14:15','2026-10-29 20:14:15','2026-10-25 23:18:48',190.00,300.00,'completed',3),(4,6,1,6,'2026-10-25 23:23:43','2026-10-26 23:23:43','2026-10-26 02:54:52',18000.00,0.00,'completed',3);
/*!40000 ALTER TABLE `check_ins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dining_tables`
--

DROP TABLE IF EXISTS `dining_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dining_tables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Main Dining',
  `capacity` int DEFAULT '4',
  `status` enum('available','occupied','reserved','cleaning') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `table_number` (`table_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30009;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dining_tables`
--

LOCK TABLES `dining_tables` WRITE;
/*!40000 ALTER TABLE `dining_tables` DISABLE KEYS */;
INSERT INTO `dining_tables` VALUES (1,'Table T1','Indoor Hall',2,'occupied'),(2,'Table T2','Indoor Hall',4,'occupied'),(3,'Table T3','Indoor Hall',4,'occupied'),(4,'Table T4','Indoor Hall',6,'occupied'),(5,'Table T5','Terrace Garden',4,'available'),(6,'Table T6','Terrace Garden',4,'occupied'),(7,'Table T7','Poolside',2,'available'),(8,'Table T8','Bar Lounge',2,'available');
/*!40000 ALTER TABLE `dining_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guests`
--

DROP TABLE IF EXISTS `guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` enum('Passport','National ID','Driving License','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'National ID',
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Domestic',
  `special_requests` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30007;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guests`
--

LOCK TABLES `guests` WRITE;
/*!40000 ALTER TABLE `guests` DISABLE KEYS */;
INSERT INTO `guests` VALUES (1,'Alexander Wright','+1 555-7821','alex.wright@gmail.com','Passport','P987654321',NULL,'United States',NULL,'2026-10-25 14:44:15'),(2,'Elena Rostova','+44 7911-123456','elena.rostova@outlook.com','Passport','GB8823190',NULL,'United Kingdom',NULL,'2026-10-25 14:44:15'),(3,'Marcus Vance','+1 555-4390','marcus.v@corporate.com','Driving License','DL-2023-9912',NULL,'Canada',NULL,'2026-10-25 14:44:15'),(4,'Sophia Chen','+94 77 123 4567','sophia.c@asia-travel.com','Passport','E89123049',NULL,'Singapore',NULL,'2026-10-25 16:40:48'),(5,'Dr. Kasun Fernando','+94 71 987 6543','k.fernando@colombo.med.lk','National ID','198421098471',NULL,'Sri Lanka',NULL,'2026-10-25 16:40:48'),(6,'nadeeshan thilina','+94 71 047 5200',NULL,'National ID','922553645',NULL,'Domestic',NULL,'2026-10-25 17:53:43');
/*!40000 ALTER TABLE `guests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_id` int NOT NULL,
  `room_id` int DEFAULT NULL,
  `reservation_id` int DEFAULT NULL,
  `room_charges` decimal(10,2) DEFAULT '0.00',
  `restaurant_charges` decimal(10,2) DEFAULT '0.00',
  `amenity_charges` decimal(10,2) DEFAULT '0.00',
  `tax_rate` decimal(5,2) DEFAULT '10.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `balance_due` decimal(10,2) NOT NULL,
  `payment_status` enum('unpaid','partially_paid','paid') COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `guest_id` (`guest_id`),
  KEY `room_id` (`room_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`),
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=60004;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-20261025-8815',1,2,1,36000.00,5400.00,0.00,10.00,4140.00,0.00,45540.00,45540.00,0.00,'paid',1,'2026-10-25 15:14:41'),(2,'INV-20261025-3258',2,6,2,18000.00,0.00,0.00,10.00,1800.00,0.00,19800.00,19800.00,0.00,'paid',1,'2026-10-25 15:35:04'),(3,'INV-20261025-5179',3,10,3,28000.00,8600.00,0.00,10.00,3660.00,0.00,40260.00,40260.00,0.00,'paid',1,'2026-10-25 17:48:48'),(30004,'INV-20261026-2398',6,1,6,18000.00,0.00,0.00,10.00,1800.00,0.00,19800.00,19800.00,0.00,'paid',1,'2026-10-26 02:54:49');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_categories`
--

DROP TABLE IF EXISTS `menu_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'utensils',
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30008;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_categories`
--

LOCK TABLES `menu_categories` WRITE;
/*!40000 ALTER TABLE `menu_categories` DISABLE KEYS */;
INSERT INTO `menu_categories` VALUES (1,'Appetizers & Starters','bowl-food',1,'active'),(2,'Main Course','plate-wheat',2,'active'),(3,'Grill & Steaks','fire',3,'active'),(4,'Pasta & Italian','pizza-slice',4,'active'),(5,'Desserts','ice-cream',5,'active'),(6,'Beverages & Mocktails','glass-water',6,'active'),(7,'Bar & Cocktails','martini-glass-citrus',7,'active');
/*!40000 ALTER TABLE `menu_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT '0.00',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_food.jpg',
  `is_available` tinyint(1) DEFAULT '1',
  `prep_time_minutes` int DEFAULT '15',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30017;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,'Crispy Calamari Rings','Served with garlic aioli and lemon wedges',2200.00,1100.00,'calamari.jpg',1,12),(2,1,'Truffle Mushroom Bruschetta','Toasted sourdough, wild sautéed mushrooms, truffle oil',1800.00,850.00,'bruschetta.jpg',1,10),(3,1,'Creamy Tomato Basil Soup','Fresh roasted Roma tomatoes with parmesan croutons',1400.00,600.00,'tomato_soup.jpg',1,8),(4,2,'Grilled Norwegian Salmon','Lemon butter emulsion, herb mashed potatoes, asparagus',4800.00,2400.00,'salmon.jpg',1,20),(5,2,'Roasted Herb Chicken','Half free-range chicken roasted with rosemary, root vegetables',3200.00,1500.00,'roast_chicken.jpg',1,25),(6,2,'Royal Chicken Biryani','Fragrant basmati rice slow-cooked with aromatic whole spices',2600.00,1200.00,'biryani.jpg',1,18),(7,3,'Prime Ribeye Steak (300g)','Grass-fed beef steak with peppercorn glaze and truffle fries',6500.00,3200.00,'ribeye_steak.jpg',1,22),(8,3,'Grilled Lamb Chops','Marinated with mint garlic chimichurri, grilled baby carrots',5800.00,2800.00,'lamb_chops.jpg',1,20),(9,4,'Fettuccine Alfredo with Chicken','Rich parmesan cream sauce, grilled chicken breast, parsley',2800.00,1300.00,'alfredo_pasta.jpg',1,15),(10,4,'Seafood Spaghetti Marinara','Prawns, calamari, mussels in rich garlic tomato basil sauce',3400.00,1600.00,'seafood_pasta.jpg',1,18),(11,5,'Classic Italian Tiramisu','Savoiardi soaked in espresso and Marsala with mascarpone',1600.00,700.00,'tiramisu.jpg',1,5),(12,5,'Warm Chocolate Lava Cake','Rich molten center served with vanilla bean ice cream',1800.00,800.00,'lava_cake.jpg',1,10),(13,6,'Fresh Tropical Fruit Smoothie','Mango, passion fruit and pineapple blended fresh',1500.00,650.00,'smoothie.jpg',1,5),(14,6,'Artisan Cappuccino','Double shot espresso, velvety steamed milk',2200.00,950.00,'cappuccino.jpg',1,4),(15,7,'Signature Smoked Old Fashioned','Bourbon, angostura bitters, orange peel, applewood smoke',950.00,400.00,'old_fashioned.jpg',1,6),(16,7,'Classic Mojito','White rum, fresh garden mint, lime juice, sparkling soda',850.00,350.00,'mojito.jpg',1,5);
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `special_instructions` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kot_status` enum('sent','cooking','ready','served') COLLATE utf8mb4_unicode_ci DEFAULT 'sent',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30021;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,4,1,24.50,24.50,'Well done salmon','cooking'),(2,1,1,1,11.50,11.50,'Extra lemon slices','ready'),(3,1,14,1,7.50,7.50,'Regular sugar','served'),(4,2,5,1,18.00,18.00,'Deliver with fresh cutlery','served'),(5,2,11,1,8.50,8.50,'Cold dessert','served'),(6,2,14,1,3.00,3.00,'Double shot','served'),(7,3,4,2,24.50,49.00,'Extra crispy skin','cooking'),(8,3,16,2,11.00,22.00,'Less ice','cooking'),(9,4,6,2,1650.00,3300.00,'Extra raita','cooking'),(10,5,1,2,11.50,23.00,'Extra crispy','cooking'),(11,6,1,2,11.50,23.00,'Extra crispy','cooking'),(12,7,3,1,7.00,7.00,'','cooking'),(13,7,1,1,11.50,11.50,'','cooking'),(14,7,2,1,9.50,9.50,'','cooking'),(15,8,11,1,8.50,8.50,'','cooking'),(16,8,10,1,21.00,21.00,'','cooking'),(17,8,9,1,16.00,16.00,'','cooking'),(18,9,8,1,28.50,28.50,'','cooking'),(19,9,1,3,11.50,34.50,'','cooking'),(20,9,5,1,18.00,18.00,'','cooking');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_type` enum('dine_in','room_service','takeaway') COLLATE utf8mb4_unicode_ci DEFAULT 'dine_in',
  `table_id` int DEFAULT NULL,
  `room_id` int DEFAULT NULL,
  `guest_id` int DEFAULT NULL,
  `waiter_id` int NOT NULL,
  `status` enum('pending','in_kitchen','ready','served','billed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','paid','billed_to_room') COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `order_number` (`order_number`),
  KEY `table_id` (`table_id`),
  KEY `room_id` (`room_id`),
  KEY `guest_id` (`guest_id`),
  KEY `waiter_id` (`waiter_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `dining_tables` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`),
  CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`waiter_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30010;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-2001','dine_in',2,NULL,NULL,4,'in_kitchen',43.50,4.35,0.00,47.85,'unpaid',NULL,'Table T2 dinner order','2026-10-25 14:44:15'),(2,'ORD-2002','room_service',NULL,2,1,4,'billed',29.50,2.95,0.00,32.45,'paid',NULL,'Room 102 delivery for guest Alexander Wright','2026-10-25 14:44:15'),(3,'ORD-261025-2118','dine_in',3,NULL,NULL,4,'in_kitchen',71.00,7.10,0.00,78.10,'unpaid','unpaid','','2026-10-25 15:12:16'),(4,'ORD-261025-8659','dine_in',1,NULL,NULL,4,'in_kitchen',3300.00,330.00,0.00,3630.00,'unpaid','unpaid','','2026-10-25 16:24:29'),(5,'ORD-261025-4659','dine_in',1,NULL,NULL,4,'in_kitchen',23.00,2.30,0.00,25.30,'unpaid','unpaid','','2026-10-25 17:10:04'),(6,'ORD-261025-9905','dine_in',1,NULL,NULL,4,'in_kitchen',23.00,2.30,0.00,25.30,'unpaid','unpaid','','2026-10-25 17:32:13'),(7,'ORD-261025-1404','dine_in',4,NULL,NULL,1,'ready',28.00,2.80,0.00,30.80,'unpaid','unpaid','','2026-10-25 17:33:11'),(8,'ORD-261025-7829','room_service',NULL,2,NULL,1,'in_kitchen',45.50,4.55,0.00,50.05,'billed_to_room','unpaid','','2026-10-25 17:36:18'),(9,'ORD-261025-5074','room_service',NULL,10,3,1,'billed',81.00,8.10,0.00,89.10,'paid','unpaid','','2026-10-25 17:48:04');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `invoice_id` (`invoice_id`),
  KEY `order_id` (`order_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=60004;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,NULL,45540.00,'Credit Card',NULL,'Guest checked out smoothly, expressed satisfaction with dinner.',1,'2026-10-25 15:14:41'),(2,2,NULL,19800.00,'Cash',NULL,'',1,'2026-10-25 15:35:04'),(3,3,NULL,40260.00,'Cash',NULL,'',1,'2026-10-25 17:48:48'),(30004,30004,NULL,19800.00,'Cash',NULL,'',1,'2026-10-26 02:54:50');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30025;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Dashboard','View Executive Analytics','dashboard.executive','Access to high-level financial KPIs and charts'),(2,'Dashboard','View Front Desk Dashboard','dashboard.frontdesk','Access to room status and arrival/departure feeds'),(3,'Privileges','Manage System Roles & Permissions','privileges.manage','Ability to edit role privileges and module access'),(4,'Rooms','View 13-Room Status Grid','rooms.view','View real-time room occupancy and cleaning status'),(5,'Rooms','Manage Room Details & Pricing','rooms.manage','Add, edit room configurations and price schemes'),(6,'Reservations','Create & Manage Bookings','reservations.manage','Create reservations, manage guest bookings'),(7,'Check-In/Out','Perform Check-In and Check-Out','frontdesk.checkin_checkout','Register guests, issue keys and process check-outs'),(8,'Housekeeping','Update Housekeeping Status','housekeeping.update','Mark rooms clean, dirty, or under maintenance'),(9,'Restaurant','Tablet POS Order Taking','pos.orders','Create and modify restaurant and room dining orders'),(10,'Restaurant','Manage Restaurant Tables','tables.manage','Configure dining tables and table status'),(11,'Restaurant','Manage Menu Categories & Items','menu.manage','Add, edit or disable food and beverage menu items'),(12,'Restaurant','Kitchen Order Tickets (KOT)','pos.kot','Generate, view, and update kitchen tickets'),(13,'Restaurant','Charge Order to Room Folio','pos.room_charge','Post restaurant bills directly to guest room invoices'),(14,'Billing','Generate Invoices & Print Receipts','billing.invoices','Generate final checkout folio and tax invoices'),(15,'Billing','Collect Payments & Settle Accounts','billing.payments','Record cash, card, and online payment settlements'),(16,'Billing','Apply Discounts & Voids','billing.discounts','Apply special discounts or void bill line items'),(17,'Staff','View Staff Directory','staff.view','View employee roster and contact directory'),(18,'Staff','Manage Staff Profiles & Salaries','staff.manage','Add, edit staff, salary structure and designations'),(19,'Staff','Mark Daily Attendance','staff.attendance','Record daily clock-in/out and leave status'),(20,'Stock','View Inventory & Stock Levels','stock.view','Monitor kitchen, bar and hotel amenity supplies'),(21,'Stock','Manage Purchases & Stock Inward','stock.purchases','Log supplier purchases and stock additions'),(22,'Stock','Stock Adjustments & Wastage','stock.adjustments','Record kitchen consumption, amenity usage and wastage'),(23,'Reports','View Financial & Daily Sales Reports','reports.financial','Access daily revenue, occupancy rate and P&L reports'),(24,'Reports','View Audit & Activity Logs','reports.audit','Inspect system audit trail and user activities');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `adults` int DEFAULT '2',
  `children` int DEFAULT '0',
  `total_room_cost` decimal(10,2) NOT NULL,
  `advance_deposit` decimal(10,2) DEFAULT '0.00',
  `status` enum('confirmed','checked_in','checked_out','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'confirmed',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `booking_ref` (`booking_ref`),
  KEY `guest_id` (`guest_id`),
  KEY `room_id` (`room_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30007;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,'BK-1001',1,2,'2026-10-25','2026-10-28',2,0,360.00,100.00,'checked_out',3,'2026-10-25 14:44:15'),(2,'BK-1002',2,6,'2026-10-25','2026-10-27',1,0,240.00,120.00,'checked_out',3,'2026-10-25 14:44:15'),(3,'BK-1003',3,10,'2026-10-25','2026-10-29',2,1,760.00,300.00,'checked_out',3,'2026-10-25 14:44:15'),(4,'BK-1004',4,5,'2026-10-25','2026-10-27',2,0,240.00,100.00,'confirmed',3,'2026-10-25 16:40:48'),(5,'BK-1005',5,13,'2026-10-26','2026-10-29',2,1,960.00,300.00,'confirmed',3,'2026-10-25 16:40:48'),(6,'BK-8958',6,1,'2026-10-25','2026-10-26',2,0,18000.00,0.00,'checked_out',3,'2026-10-25 17:53:43');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `is_granted` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `unique_role_perm` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30066;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,16,1),(2,1,14,1),(3,1,15,1),(4,1,1,1),(5,1,2,1),(6,1,7,1),(7,1,8,1),(8,1,11,1),(9,1,12,1),(10,1,9,1),(11,1,13,1),(12,1,3,1),(13,1,24,1),(14,1,23,1),(15,1,6,1),(16,1,5,1),(17,1,4,1),(18,1,19,1),(19,1,18,1),(20,1,17,1),(21,1,22,1),(22,1,21,1),(23,1,20,1),(24,1,10,1),(32,2,14,1),(33,2,1,1),(34,2,2,1),(35,2,24,1),(36,2,23,1),(37,2,4,1),(38,2,17,1),(39,2,20,1),(47,3,14,1),(48,3,15,1),(49,3,2,1),(50,3,7,1),(51,3,8,1),(52,3,12,1),(53,3,9,1),(54,3,13,1),(55,3,6,1),(56,3,4,1),(57,3,19,1),(58,3,17,1),(59,3,20,1),(62,4,12,1),(63,4,9,1),(64,4,13,1),(65,4,4,1);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30005;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','admin','Full system access, configurations, user management and reports','2026-10-25 14:44:15'),(2,'Hotel Owner','owner','Executive business analytics, revenues, P&L, audits and view permissions','2026-10-25 14:44:15'),(3,'Receptionist','reception','Front desk, 13 rooms rack, reservations, check-in/out, folio and billing','2026-10-25 14:44:15'),(4,'Waiter / Steward','waiter','Tablet POS, table & room service order taking, KOT management','2026-10-25 14:44:15');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `capacity` int DEFAULT '2',
  `amenities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30005;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_types`
--

LOCK TABLES `room_types` WRITE;
/*!40000 ALTER TABLE `room_types` DISABLE KEYS */;
INSERT INTO `room_types` VALUES (1,'Deluxe King','DLX-K',18000.00,2,'King Bed, AC, Smart TV, Wi-Fi, Mini Bar, Balcony','Spacious deluxe room with balcony and luxury king bed'),(2,'Deluxe Twin','DLX-T',16000.00,2,'Twin Beds, AC, Smart TV, Wi-Fi, Coffee Maker','Comfortable twin bedding perfect for friends or colleagues'),(3,'Executive Suite','EXEC-S',28000.00,3,'King Bed + Sofa, Jacuzzi, Ocean/Garden View, Kitchenette','Premium suite with separate seating lounge and panoramic views'),(4,'Presidential Penthouse','PRES-P',55000.00,4,'Master King, Dining Area, Terrace, Private Jacuzzi','Top-tier luxury penthouse on 3rd floor with private terrace');
/*!40000 ALTER TABLE `room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_type_id` int NOT NULL,
  `floor` int NOT NULL,
  `status` enum('vacant','reserved','occupied','dirty','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'vacant',
  `daily_rate` decimal(10,2) NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type_id` (`room_type_id`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30016;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'Room 101',1,1,'dirty',18000.00,'Ground floor garden view'),(2,'Room 102',1,1,'vacant',18000.00,'Garden view with easy lobby access'),(3,'Room 103',2,1,'vacant',16000.00,'Twin beds, quiet wing'),(4,'Room 104',2,1,'dirty',16000.00,'Checked out this afternoon - needs turnaround'),(5,'Room 105',1,1,'reserved',18000.00,'Reserved for VIP arrival tonight'),(6,'Room 201',1,2,'dirty',18000.00,'First floor king suite with balcony'),(7,'Room 202',1,2,'vacant',18000.00,'First floor king with balcony'),(8,'Room 203',2,2,'vacant',16000.00,'First floor twin beds'),(9,'Room 204',2,2,'maintenance',16000.00,'AC servicing in progress'),(10,'Room 205',3,2,'dirty',28000.00,'Corner Executive Suite with jacuzzi'),(11,'Room 301',3,3,'vacant',28000.00,'Top floor executive luxury suite'),(12,'Room 302',3,3,'vacant',28000.00,'Top floor executive luxury suite'),(13,'Room 303',4,3,'reserved',55000.00,'Presidential Penthouse with panoramic terrace'),(15,'Room 106',2,1,'vacant',16000.00,'');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `staff_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` enum('Management','Front Office','F&B Service','Kitchen','Housekeeping','Maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `join_date` date NOT NULL,
  `status` enum('active','on_leave','terminated') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `staff_code` (`staff_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30006;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,1,'STF-001','System','Admin','IT & Systems Manager','Management','+94 77 123 0101','admin@luxuryhotel.com',85000.00,'2023-01-15','active'),(2,3,'STF-002','Sarah','Jenkins','Senior Receptionist','Front Office','+94 77 123 0103','sarah.j@luxuryhotel.com',65000.00,'2023-03-01','active'),(3,4,'STF-003','David','Miller','Head Waiter','F&B Service','+94 77 123 0104','david.m@luxuryhotel.com',55000.00,'2023-05-10','active'),(4,NULL,'STF-004','Chef Antonio','Rossi','Executive Head Chef','Kitchen','+94 77 123 0188','antonio.chef@luxuryhotel.com',95000.00,'2022-11-01','active'),(5,NULL,'STF-005','Maria','Santos','Housekeeping Supervisor','Housekeeping','+94 77 123 0192','maria.s@luxuryhotel.com',50000.00,'2023-02-15','active');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `status` enum('present','late','absent','half_day','on_leave') COLLATE utf8mb4_unicode_ci DEFAULT 'present',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `unique_daily_attendance` (`staff_id`,`attendance_date`),
  CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30006;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_attendance`
--

LOCK TABLES `staff_attendance` WRITE;
/*!40000 ALTER TABLE `staff_attendance` DISABLE KEYS */;
INSERT INTO `staff_attendance` VALUES (1,1,'2026-10-25','08:00:00',NULL,'present','On duty'),(2,2,'2026-10-25','07:45:00',NULL,'present','Morning shift front desk'),(3,3,'2026-10-25','10:00:00',NULL,'present','Restaurant lunch/dinner shift'),(4,4,'2026-10-25','09:30:00',NULL,'present','Kitchen prep'),(5,5,'2026-10-25','08:15:00',NULL,'present','Room inspection rounds');
/*!40000 ALTER TABLE `staff_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_categories`
--

DROP TABLE IF EXISTS `stock_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('kitchen','bar','room_amenities','cleaning') COLLATE utf8mb4_unicode_ci DEFAULT 'kitchen',
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30006;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_categories`
--

LOCK TABLES `stock_categories` WRITE;
/*!40000 ALTER TABLE `stock_categories` DISABLE KEYS */;
INSERT INTO `stock_categories` VALUES (1,'Meats & Seafood','kitchen'),(2,'Dairy & Produce','kitchen'),(3,'Spirits & Beverages','bar'),(4,'Guest Room Amenities','room_amenities'),(5,'Housekeeping & Linens','cleaning');
/*!40000 ALTER TABLE `stock_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_items`
--

DROP TABLE IF EXISTS `stock_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `minimum_stock` decimal(10,2) NOT NULL DEFAULT '5.00',
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `supplier` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `category_id` (`category_id`),
  CONSTRAINT `stock_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `stock_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30010;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_items`
--

LOCK TABLES `stock_items` WRITE;
/*!40000 ALTER TABLE `stock_items` DISABLE KEYS */;
INSERT INTO `stock_items` VALUES (1,1,'Fresh Atlantic Salmon Fillet','kg',14.50,5.00,3800.00,'Ocean Fresh Fish Co.','2026-10-26 03:02:34'),(2,1,'Prime Ribeye Beef Loins','kg',18.00,6.00,4500.00,'Highland Prime Meats','2026-10-26 03:02:34'),(3,2,'Fresh Whole Milk','liters',24.00,10.00,320.00,'Green Valley Dairy','2026-10-26 03:02:35'),(4,2,'Parmesan Cheese Wheels','kg',8.50,3.00,2800.00,'Euro Imports Ltd.','2026-10-26 03:02:35'),(5,3,'Bourbon Whiskey (750ml)','bottles',12.00,4.00,5500.00,'Vintage Spirits Dist.','2026-10-26 03:02:35'),(6,4,'Luxury Shampoo & Shower Gel (50ml)','bottles',180.00,50.00,1800.00,'Aroma Hospitality Supplies','2026-10-26 03:02:35'),(7,4,'Egyptian Cotton Bath Towels','pcs',45.00,20.00,450.00,'Linen Crafts Co.','2026-10-26 03:02:36'),(8,4,'Artisan Coffee Pods','boxes',30.00,10.00,250.00,'Master Roast Coffee','2026-10-26 03:02:36'),(9,1,'Jumbo Prawns (Tiger)','kg',3.50,5.00,180.00,'Ocean Fresh Fish Co.','2026-10-26 03:02:36');
/*!40000 ALTER TABLE `stock_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stock_item_id` int NOT NULL,
  `movement_type` enum('purchase_in','kitchen_out','room_out','wastage','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT '0.00',
  `reference_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `stock_item_id` (`stock_item_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`stock_item_id`) REFERENCES `stock_items` (`id`),
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30005;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'System Administrator','admin','$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6','admin@luxuryhotel.com','+94 77 123 0101','default_avatar.png','active','2026-10-25 14:44:15'),(2,2,'Robert Sterling (Owner)','owner','$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6','owner@luxuryhotel.com','+94 77 123 0102','default_avatar.png','active','2026-10-25 14:44:15'),(3,3,'Sarah Jenkins (Reception)','reception','$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6','reception@luxuryhotel.com','+94 77 123 0103','default_avatar.png','active','2026-10-25 14:44:15'),(4,4,'David Miller (Waiter)','waiter','$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6','waiter@luxuryhotel.com','+94 77 123 0104','default_avatar.png','active','2026-10-25 14:44:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-26  8:35:45
