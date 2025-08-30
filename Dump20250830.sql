-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: agmsdb
-- ------------------------------------------------------
-- Server version	8.0.39

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `arts`
--

DROP TABLE IF EXISTS `arts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `artist_id` int NOT NULL,
  `art_title` text NOT NULL,
  `art_description` text NOT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0=unpublished,1=published',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arts`
--

LOCK TABLES `arts` WRITE;
/*!40000 ALTER TABLE `arts` DISABLE KEYS */;
INSERT INTO `arts` VALUES (2,2,'Sample Art 2','&lt;p style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Ut faucibus pulvinar elementum integer enim neque volutpat ac. Nunc scelerisque viverra mauris in aliquam sem fringilla ut morbi. Sed odio morbi quis commodo odio aenean. Leo urna molestie at elementum eu. Sed elementum tempus egestas sed sed. Eget dolor morbi non arcu risus quis varius quam quisque. Blandit volutpat maecenas volutpat blandit. Diam phasellus vestibulum lorem sed risus ultricies. At tempor commodo ullamcorper a lacus vestibulum sed. Quis hendrerit dolor magna eget est lorem ipsum. Pellentesque habitant morbi tristique senectus et. Sagittis aliquam malesuada bibendum arcu vitae elementum curabitur. Ante metus dictum at tempor commodo ullamcorper. Commodo quis imperdiet massa tincidunt nunc pulvinar. Sit amet porttitor eget dolor morbi non arcu risus quis.&lt;/p&gt;&lt;p style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Vitae aliquet nec ullamcorper sit amet risus. Nisi porta lorem mollis aliquam ut. Eget est lorem ipsum dolor sit amet consectetur adipiscing elit. Faucibus purus in massa tempor. Ullamcorper sit amet risus nullam eget felis eget. Lorem ipsum dolor sit amet consectetur. Non consectetur a erat nam at lectus. Amet nisl suscipit adipiscing bibendum est ultricies integer. Pretium nibh ipsum consequat nisl vel pretium lectus quam. Sit amet cursus sit amet dictum sit. Enim ut sem viverra aliquet. Suscipit tellus mauris a diam maecenas sed. At tempor commodo ullamcorper a lacus. Vitae tempus quam pellentesque nec.&lt;/p&gt;&lt;h4 style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;&lt;b style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Materials:&lt;/b&gt;&lt;/h4&gt;&lt;p&gt;&lt;ul&gt;&lt;li&gt;&lt;b style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Sample 1&lt;/b&gt;&lt;/li&gt;&lt;li&gt;&lt;b style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Sample 2&lt;/b&gt;&lt;/li&gt;&lt;li&gt;&lt;b style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Sample 3&lt;/b&gt;&lt;/li&gt;&lt;/ul&gt;&lt;/p&gt;',1),(4,2,'Sample Art','Amet mauris commodo quis imperdiet massa tincidunt nunc pulvinar. In aliquam sem fringilla ut morbi tincidunt augue interdum. Laoreet suspendisse interdum consectetur libero id. Pulvinar sapien et ligula ullamcorper malesuada proin libero nunc. Cursus in hac habitasse platea dictumst. Viverra mauris in aliquam sem fringilla. Sociis natoque penatibus et magnis dis parturient montes. Tellus id interdum velit laoreet id donec ultrices tincidunt arcu. Libero volutpat sed cras ornare. Congue quisque egestas diam in. Dictum fusce ut placerat orci nulla pellentesque dignissim enim. Non sodales neque sodales ut etiam. Hendrerit gravida rutrum quisque non tellus orci ac auctor. Faucibus in ornare quam viverra orci sagittis eu volutpat.&lt;h4&gt;&lt;b&gt;Materials&lt;/b&gt;&lt;/h4&gt;&lt;p&gt;&lt;ul&gt;&lt;li&gt;&lt;b&gt;Sample&lt;/b&gt;&lt;/li&gt;&lt;li&gt;&lt;b&gt;sample&lt;/b&gt;&lt;/li&gt;&lt;li&gt;&lt;b&gt;sample&lt;/b&gt;&lt;/li&gt;&lt;/ul&gt;&lt;/p&gt;',0);
/*!40000 ALTER TABLE `arts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `arts_fs`
--

DROP TABLE IF EXISTS `arts_fs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arts_fs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `art_id` text NOT NULL,
  `price` float NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=onhand,1= sold',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arts_fs`
--

LOCK TABLES `arts_fs` WRITE;
/*!40000 ALTER TABLE `arts_fs` DISABLE KEYS */;
INSERT INTO `arts_fs` VALUES (1,'2',300000,0,'2020-10-09 13:27:45'),(2,'4',250000,0,'2020-10-09 13:47:04');
/*!40000 ALTER TABLE `arts_fs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive',
  `description` text,
  `category_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Dress','active','Women Dress',NULL,'2025-08-29 20:58:47'),(2,'Women Top','active','Elegant fashion top',NULL,'2025-08-29 21:22:48'),(3,'dress toppp','active','gghhh',NULL,'2025-08-29 21:49:24'),(4,'dress toppp','active','teshl',NULL,'2025-08-29 21:57:07'),(5,'dress toppp','active','testtttt',NULL,'2025-08-29 22:00:07'),(6,'yeyutrtyu','active','dfghjk',NULL,'2025-08-29 22:05:21'),(7,'dress toppp','active','test',NULL,'2025-08-29 22:06:49'),(8,'hhh','active','hhh',NULL,'2025-08-29 22:07:26'),(9,'dress toppp','active','tesrr',NULL,'2025-08-29 22:11:19'),(10,'test 1','active','test',NULL,'2025-08-29 22:15:46'),(11,'hhhhhhhhhhhhhhh','active','hhhhhhhhhhhhh',NULL,'2025-08-29 22:26:15'),(12,'products','active','products',NULL,'2025-08-29 22:30:12'),(13,'jjj','active','j',NULL,'2025-08-29 22:34:27'),(14,'Men Dress','active','Men Dress only',NULL,'2025-08-30 08:13:34'),(15,'Men Pants','active','men pants','uploads/categories/996349716cd0fcd2.png','2025-08-30 08:49:10'),(16,'Women Pants','active','Women Pants','uploads/categories/97a48270358a0c7e.png','2025-08-30 09:14:35');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `artist_id` int NOT NULL,
  `content` text NOT NULL,
  `event_datetime` datetime NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'My Createion',2,'&lt;p style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Neque ornare aenean euismod elementum nisi. Vestibulum lectus mauris ultrices eros in. Felis donec et odio pellentesque. Amet aliquam id diam maecenas ultricies mi eget mauris pharetra. Ut ornare lectus sit amet. Accumsan in nisl nisi scelerisque eu. Ac odio tempor orci dapibus. Risus ultricies tristique nulla aliquet enim tortor at auctor urna. Feugiat in ante metus dictum at tempor commodo. Et malesuada fames ac turpis egestas maecenas. Rhoncus dolor purus non enim. Faucibus nisl tincidunt eget nullam non nisi est sit amet.&lt;/p&gt;&lt;p style=&quot;-webkit-tap-highlight-color: rgba(0, 0, 0, 0); margin-top: 1.5em; margin-bottom: 1.5em; line-height: 1.5; animation: 1000ms linear 0s 1 normal none running fadeInLorem;&quot;&gt;Cras adipiscing enim eu turpis egestas pretium aenean. Neque aliquam vestibulum morbi blandit cursus. Eu turpis egestas pretium aenean pharetra magna ac placerat. Laoreet suspendisse interdum consectetur libero id. Et ultrices neque ornare aenean euismod elementum nisi. Placerat in egestas erat imperdiet sed euismod nisi porta. Diam volutpat commodo sed egestas egestas fringilla phasellus faucibus scelerisque. Nulla facilisi morbi tempus iaculis. Etiam tempor orci eu lobortis. Sit amet dictum sit amet justo.&lt;/p&gt;','2020-10-12 15:00:00','2020-10-09 14:27:27');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `art_fs_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=for verification,1= confirmed,2 = cancel, 3= delivered',
  `deliver_schedule` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,4,0,'2020-10-13');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `min_stock` int DEFAULT '0',
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `sizes` json DEFAULT NULL,
  `colors` json DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `tags` text,
  `weight` decimal(8,2) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `season` enum('spring','summer','fall','winter','all-season') DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `track_inventory` tinyint(1) DEFAULT '1',
  `allow_backorder` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'test','TEST-894','Dress','','test',10000.00,10000.00,6000.00,50,5,'active','[\"XS\"]','[\"Pink\"]',NULL,'0',10.00,'cotton','all-season',0,1,0,'2025-08-30 08:10:56','2025-08-30 08:10:56'),(2,'Red Dress','D-997','Dress','Zara','Women Dress',30000.00,30000.00,15000.00,20,5,'active','[\"S\"]','[\"Gold\"]',NULL,'0',1.40,'cotton','all-season',1,1,1,'2025-08-30 08:12:32','2025-08-30 08:12:32'),(3,'Orange Dress','REDDRE-559','Dress','','orange dress',10000.00,10000.00,6000.00,10,3,'active','[\"L\"]','[\"Green\"]','uploads/products/8295156fd89cab5d.png','0',10.00,'cotton','all-season',1,1,1,'2025-08-30 08:50:26','2025-08-30 08:50:26'),(4,'Women Pants','W-930','Women Pants','test','test',15000.00,15000.00,8000.00,10,3,'active','[\"M\"]','[\"Black\", \"Green\"]','uploads/products/133e6cef72871e32.jpg','0',10.00,'Cotton','all-season',1,1,1,'2025-08-30 09:16:23','2025-08-30 09:16:23');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `cover_img` text NOT NULL,
  `about_content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'Art Gallery Management System','info@sample.comm','+6948 8542 623','1602247020_photo-1533158326339-7f3cf2404354.jpg','&lt;p style=&quot;text-align: center; background: transparent; position: relative;&quot;&gt;&lt;span style=&quot;color: rgb(0, 0, 0); font-family: &amp;quot;Open Sans&amp;quot;, Arial, sans-serif; font-weight: 400; text-align: justify;&quot;&gt;&amp;nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&rsquo;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.&lt;/span&gt;&lt;br&gt;&lt;/p&gt;&lt;p style=&quot;text-align: center; background: transparent; position: relative;&quot;&gt;&lt;br&gt;&lt;/p&gt;&lt;p style=&quot;text-align: center; background: transparent; position: relative;&quot;&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;/p&gt;');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbladmin`
--

DROP TABLE IF EXISTS `tbladmin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbladmin` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AdminName` varchar(45) DEFAULT NULL,
  `UserName` varchar(50) DEFAULT NULL,
  `MobileNumber` bigint DEFAULT NULL,
  `Email` varchar(120) DEFAULT NULL,
  `Password` varchar(120) DEFAULT NULL,
  `AdminRegdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbladmin`
--

LOCK TABLES `tbladmin` WRITE;
/*!40000 ALTER TABLE `tbladmin` DISABLE KEYS */;
INSERT INTO `tbladmin` VALUES (1,'Admin','admin',987654331,'tester1@gmail.com','f925916e2754e5e03f75dd58a5733251','2022-12-29 06:21:53');
/*!40000 ALTER TABLE `tbladmin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblartist`
--

DROP TABLE IF EXISTS `tblartist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblartist` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(250) DEFAULT NULL,
  `MobileNumber` bigint DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `Education` mediumtext,
  `Award` mediumtext,
  `Profilepic` varchar(250) DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblartist`
--

LOCK TABLES `tblartist` WRITE;
/*!40000 ALTER TABLE `tblartist` DISABLE KEYS */;
INSERT INTO `tblartist` VALUES (1,'Mohan Das',7987987987,'mohan@gmail.com','Completed his fine arts from kg fine arts college.\r\nSpecialized in drawing and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ecebbecf28c2692aeb021597fbddb174.jpg','2022-12-21 13:31:25'),(2,'Dev',3287987987,'dev@gmail.com','Completed his fine arts from kg fine arts college.\r\nSpecialized in painting and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(3,'Kanha',9687987987,'kanha@gmail.com','Completed his fine arts from kg fine arts college.\r\nSpecialized in painting and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(4,'Abir Rajwansh',5687987987,'abir@gmail.com','Completed his fine arts from klijfine arts college.\r\nSpecialized in painting and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(5,'Krisna Dutt',9187987987,'krish@gmail.com','Completed his fine arts from kg fine arts college.\r\nSpecialized in painting and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(6,'Kajol Mannati',8187987987,'kajol@gmail.com','Completed his fine arts from kg fine arts college.\r\nSpecialized in painting and ceramic.','Winner of Hugo Boss Prize in 2019, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(7,'Meera Singh',2987987987,'meera@gmail.com','Fine Arts in Painting from College of Art, New Delhi in 2012,\r\nSpecialized in printmaking and ceramic.','award-winning artist, and has received a scholarship from the Ministry of Culture, Government of India in 2014 as well as the Jean-Claude Reynal Scholarship (France) in 2019.\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25'),(8,'Narayan Das',9987987987,'narayan@gmail.com','Completed his fine arts from hjai fine arts college.\r\nSpecialized in painting and ceramic.','Winner of Young Artist Award in 2009, MacArthur Fellowship\r\n','ad04ad2d96ae326a9ca9de47d9e2fc74.jpg','2022-12-21 13:31:25');
/*!40000 ALTER TABLE `tblartist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblartmedium`
--

DROP TABLE IF EXISTS `tblartmedium`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblartmedium` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ArtMedium` varchar(250) DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblartmedium`
--

LOCK TABLES `tblartmedium` WRITE;
/*!40000 ALTER TABLE `tblartmedium` DISABLE KEYS */;
INSERT INTO `tblartmedium` VALUES (1,'Wood and Bronze','2022-12-22 04:57:04'),(2,'Acrylic on canvas','2022-12-22 04:57:34'),(3,'Resin','2022-12-22 04:58:00'),(4,'Mixed Media','2022-12-22 06:09:12'),(5,'Bronze','2022-12-22 06:09:35'),(6,'Fibre','2022-12-22 06:09:53'),(7,'Steel','2022-12-22 06:10:16'),(8,'Metal','2022-12-22 06:10:35'),(9,'Oil on Canvas','2022-12-22 06:11:31'),(10,'Oil on Linen','2022-12-22 06:12:12'),(11,'Acrylics on paper','2022-12-22 06:13:11'),(12,'Hand-painted on particle wood/MDF','2022-12-22 06:14:03');
/*!40000 ALTER TABLE `tblartmedium` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblartproduct`
--

DROP TABLE IF EXISTS `tblartproduct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblartproduct` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(250) DEFAULT NULL,
  `Dimension` varchar(250) DEFAULT NULL,
  `Orientation` varchar(100) DEFAULT NULL,
  `Size` varchar(100) DEFAULT NULL,
  `Artist` int DEFAULT NULL,
  `ArtType` int DEFAULT NULL,
  `ArtMedium` int DEFAULT NULL,
  `SellingPricing` decimal(10,0) DEFAULT NULL,
  `Description` mediumtext,
  `Image` varchar(250) DEFAULT NULL,
  `Image1` varchar(250) DEFAULT NULL,
  `Image2` varchar(250) DEFAULT NULL,
  `Image3` varchar(250) DEFAULT NULL,
  `Image4` varchar(250) DEFAULT NULL,
  `RefNum` int DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblartproduct`
--

LOCK TABLES `tblartproduct` WRITE;
/*!40000 ALTER TABLE `tblartproduct` DISABLE KEYS */;
INSERT INTO `tblartproduct` VALUES (2,'Radhe Krishna Painting','56x56','Landscape','Medium',1,4,9,200,'It is a painting of Radha Krishna.\r\nIt is a painting of Radha Krishna.\r\nIt is a painting of Radha Krishna.It is a painting of Radha Krishna.\r\nIt is a painting of Radha Krishna.It is a painting of Radha Krishna.It is a painting of Radha Krishna.','c565ad988a4c6fc0a9f429af43c47cce1671771454.jpg','48424793dc9ea732f6118d4ba4326509.jpg','','','',586429003,'2022-12-23 04:57:34'),(3,'Shiv Tandav Painting','100X50 inches','Potrait','Large',6,4,10,350,'It is a painting of shiv tandav.\r\nIt is a painting of shiv tandav.\r\nIt is a painting of shiv tandav.It is a painting of shiv tandav.It is a painting of shiv tandav.It is a painting of shiv tandav.It is a painting of shiv tandav.\r\nIt is a painting of shiv tandav.It is a painting of shiv tandav.','cd235e034297cda7b6f935dbd4881a2f1671771582.jpg','cd235e034297cda7b6f935dbd4881a2f1671771582.jpg','','','',686429002,'2022-12-23 04:59:42'),(4,'Stutue of Afel Tower','45 inches tall','Landscape','Medium',7,1,8,500,'It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,It is a stute of afel tower which is made up of metal,','508652faabdd333b34a0ce4a1dd443411671771753.jpg','','','','',686429003,'2022-12-23 05:02:33'),(5,'HKjhkj','100x200','Landscape','Large',7,3,9,200,'gjhgj','7d108db512f6a6a929cd0d0ad3b593e81671772410.jpg','','','','',586429004,'2022-12-23 05:13:30');
/*!40000 ALTER TABLE `tblartproduct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblarttype`
--

DROP TABLE IF EXISTS `tblarttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblarttype` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ArtType` varchar(250) DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblarttype`
--

LOCK TABLES `tblarttype` WRITE;
/*!40000 ALTER TABLE `tblarttype` DISABLE KEYS */;
INSERT INTO `tblarttype` VALUES (1,'Sculptures','2022-12-21 14:21:13'),(2,'Serigraphs','2022-12-21 14:24:46'),(3,'Prints','2022-12-21 14:25:00'),(4,'Painting','2022-12-21 14:25:31'),(5,'Street Art','2022-12-21 14:26:06'),(6,'Visual art ','2022-12-21 14:26:29'),(7,'Conceptual art','2022-12-21 14:26:45');
/*!40000 ALTER TABLE `tblarttype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblenquiry`
--

DROP TABLE IF EXISTS `tblenquiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblenquiry` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EnquiryNumber` varchar(10) NOT NULL,
  `Artpdid` int DEFAULT NULL,
  `FullName` varchar(120) DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `MobileNumber` bigint DEFAULT NULL,
  `Message` varchar(250) DEFAULT NULL,
  `EnquiryDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(10) DEFAULT NULL,
  `AdminRemark` varchar(200) NOT NULL,
  `AdminRemarkdate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `CardId` (`Artpdid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblenquiry`
--

LOCK TABLES `tblenquiry` WRITE;
/*!40000 ALTER TABLE `tblenquiry` DISABLE KEYS */;
INSERT INTO `tblenquiry` VALUES (1,'230873611',4,'Anuj kumar','ak@test.com',1234567890,'This is for testing Purpose.','2023-01-02 18:16:47','Answer','test purpose','2023-01-01 18:30:00'),(2,'227883179',5,'Amit Kumar','amitk55@test.com',1234434321,'I want this painting','2023-01-02 18:42:42','Answer','testing purpose','2023-01-02 18:43:16');
/*!40000 ALTER TABLE `tblenquiry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblpage`
--

DROP TABLE IF EXISTS `tblpage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblpage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageType` varchar(200) DEFAULT NULL,
  `PageTitle` mediumtext,
  `PageDescription` mediumtext,
  `Email` varchar(200) DEFAULT NULL,
  `MobileNumber` bigint DEFAULT NULL,
  `UpdationDate` date DEFAULT NULL,
  `Timing` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblpage`
--

LOCK TABLES `tblpage` WRITE;
/*!40000 ALTER TABLE `tblpage` DISABLE KEYS */;
INSERT INTO `tblpage` VALUES (1,'aboutus','About Us','<span style=\"color: rgb(32, 33, 36); font-family: arial, sans-serif; font-size: 16px;\">An art gallery is&nbsp;</span><b style=\"color: rgb(32, 33, 36); font-family: arial, sans-serif; font-size: 16px;\">an exhibition space to display and sell artworks</b><span style=\"color: rgb(32, 33, 36); font-family: arial, sans-serif; font-size: 16px;\">. As a result, the art gallery is a commercial enterprise working with a portfolio of artists. The gallery acts as the dealer representing, supporting, and distributing the artworks by the artists in question.</span><br>',NULL,NULL,NULL,''),(2,'contactus','Contact Us','890,Sector 62, Gyan Sarovar, GAIL Noida(Delhi/NCR)','info@gmail.com',1234567890,NULL,'10:30 am to 7:30 pm');
/*!40000 ALTER TABLE `tblpage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `address` text NOT NULL,
  `contact` varchar(50) NOT NULL,
  `user_type` tinyint(1) NOT NULL DEFAULT '3' COMMENT '1 = admin, 2= artist,3= customers',
  `username` text NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','','',1,'admin','0192023a7bbd73250516f069df18b500'),(2,'John Smith','Sample','+18456-5455-55',2,'',''),(3,'George Wilson','Sample Address','+14526-5455-44',2,'',''),(4,'Customer 1','Sample','+123545',3,'','');
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

-- Dump completed on 2025-08-30 11:41:04
