
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
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(8) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `plate_number` varchar(255) NOT NULL,
  `vehicle_type` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `complaint` text DEFAULT NULL,
  `booking_date` date NOT NULL,
  `slot_time` varchar(5) NOT NULL,
  `dp_amount` int(10) unsigned NOT NULL DEFAULT 0,
  `dp_paid_at` datetime DEFAULT NULL,
  `dp_method` varchar(255) DEFAULT NULL,
  `dp_proof_path` varchar(255) DEFAULT NULL,
  `dp_proof_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'menunggu',
  `staff_note` text DEFAULT NULL,
  `work_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  UNIQUE KEY `bookings_work_order_id_unique` (`work_order_id`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_booking_date_slot_time_index` (`booking_date`,`slot_time`),
  CONSTRAINT `bookings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `bookings_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'BKNG2345','Booking Demo 1','081234567801','booking1@bengkel.test','F001BOOK','mobil','Toyota','Avanza',1,'Booking demo 09:00','2026-11-27','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'4GHYZJF7','Booking Demo 2','081234567802','booking2@bengkel.test','F002BOOK','mobil','Toyota','Avanza',1,'Booking demo 09:00','2026-11-27','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'BWYYH8JL','Booking Demo 3','081234567803','booking3@bengkel.test','F003BOOK','mobil','Toyota','Avanza',1,'Booking demo 09:00','2026-11-27','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'PK2M2SQG','Booking Demo 4','081234567804',NULL,'F004BOOK','mobil','Toyota','Avanza',1,'Booking demo 10:00','2026-11-27','10:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'2R6T458X','Booking Demo 5','081234567805',NULL,'F005BOOK','mobil','Toyota','Avanza',1,'Booking demo 11:00','2026-11-27','11:00',50000,'2026-11-26 10:30:05','transfer','dp-proofs/demo-transfer.svg','2026-11-26 03:30:05','dikonfirmasi',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,'T9B665B5','Booking Demo 6','081234567806',NULL,'F006BOOK','mobil','Toyota','Avanza',1,'Booking demo 11:00','2026-11-27','11:00',50000,'2026-11-26 10:30:05','transfer','dp-proofs/demo-transfer.svg','2026-11-26 03:30:05','dikonfirmasi',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,'3BEL3LR3','Booking Demo 7','081234567807',NULL,'F007BOOK','mobil','Toyota','Avanza',1,'Booking demo 12:00','2026-11-27','12:00',50000,NULL,NULL,NULL,NULL,'dibatalkan',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,'K7SU92NN','Booking Demo 8','081234567808',NULL,'F008BOOK','mobil','Toyota','Avanza',1,'Booking demo 13:00','2026-11-27','13:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,'LUSA2345','Booking Lusa 1','081234567901','lusa1@bengkel.test','F001LSA','mobil','Honda','Brio',2,'Booking lusa 09:00','2026-11-28','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,'LUSA02','Booking Lusa 2','081234567902','lusa2@bengkel.test','F002LSA','mobil','Honda','Brio',2,'Booking lusa 09:00','2026-11-28','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(11,'LUSA03','Booking Lusa 3','081234567903',NULL,'F003LSA','mobil','Honda','Brio',2,'Booking lusa 09:00','2026-11-28','09:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(12,'LUSA04','Booking Lusa 4','081234567904',NULL,'F004LSA','mobil','Honda','Brio',2,'Booking lusa 10:00','2026-11-28','10:00',50000,NULL,NULL,NULL,NULL,'menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(13,'LUSA05','Booking Lusa 5','081234567905',NULL,'F005LSA','mobil','Honda','Brio',2,'Booking lusa 11:00','2026-11-28','11:00',50000,'2026-11-26 10:30:05','transfer','dp-proofs/demo-transfer.svg','2026-11-26 03:30:05','dikonfirmasi',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(14,'LUSA06','Booking Lusa 6','081234567906',NULL,'F006LSA','mobil','Honda','Brio',2,'Booking lusa 12:00','2026-11-28','12:00',50000,NULL,'qris','dp-proofs/demo-transfer.svg','2026-11-26 03:30:05','menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(15,'LUSA07','Booking Lusa 7','081234567907',NULL,'F007LSA','mobil','Honda','Brio',2,'Booking lusa 13:00','2026-11-28','13:00',50000,NULL,NULL,NULL,NULL,'dibatalkan',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(16,'HRINI01','Pelanggan Baru Check-in','081399990010','baru.checkin@example.test','D7777ZZ','mobil','Toyota','Rush',2,'Servis ringan, pelanggan baru','2026-11-26','14:00',50000,'2026-11-26 08:30:05','transfer','dp-proofs/demo-transfer.svg','2026-11-26 01:30:05','dikonfirmasi',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(17,'HRINI03','Sari Wulandari','081399990003','sari@example.test','D9003XY','mobil','Daihatsu','Terios',3,'Servis mendadak hari ini','2026-11-26','15:00',50000,NULL,'qris','dp-proofs/demo-transfer.svg','2026-11-26 02:50:05','menunggu',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(18,'HRINI02','Wulandari Putri','081311110010',NULL,'B010ABC','mobil','Suzuki','Ignis',1,'Ganti oli, sudah check-in pagi','2026-11-26','08:00',50000,'2026-11-26 09:30:05','qris','dp-proofs/demo-transfer.svg','2026-11-26 02:30:05','check_in',NULL,15,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(19,'LEWAT01','Pelanggan Tidak Hadir','081399990001',NULL,'D9001XY','mobil','Toyota','Yaris',1,'Servis rutin','2026-11-25','09:00',50000,NULL,NULL,NULL,NULL,'tidak_hadir',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(20,'LEWAT02','Pelanggan Batal','081399990002',NULL,'D9002XY','mobil','Honda','Jazz',2,'Servis ringan','2026-11-24','10:00',50000,NULL,NULL,NULL,NULL,'dibatalkan',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(21,'LEWAT03','Pelanggan Belum Datang','081399990003','belum.datang@example.test','D9003LW','mobil','Suzuki','Swift',2,'Servis ringan','2026-11-25','11:00',50000,'2026-11-25 10:30:05','transfer','dp-proofs/demo-transfer.svg','2026-11-25 03:30:05','dikonfirmasi',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Darimin Rahman Setiawan','081311110001','darimin@example.test','Jl. Melati 12, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'Siti Nurhaliza','081311110002','siti@example.test','Jl. Kenanga 4, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'Andi Wijaya','081311110003','andi@example.test','Jl. Cempaka 8, Cimahi','2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'Rina Marlina','081311110004',NULL,'Jl. Anggrek 21, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'Hendra Gunawan','081311110005','hendra@example.test','Jl. Dahlia 3, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,'Maya Anggraini','081311110006',NULL,'Jl. Flamboyan 15, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,'Dedi Kurniawan','081311110007','dedi@example.test','Jl. Mawar 9, Cimahi','2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,'Lestari Handayani','081311110008','lestari@example.test','Jl. Kamboja 2, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,'Fajar Nugroho','081311110009',NULL,'Jl. Teratai 30, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,'Wulandari Putri','081311110010','wulan@example.test','Jl. Sakura 7, Bandung','2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `mechanics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mechanics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mechanics` WRITE;
/*!40000 ALTER TABLE `mechanics` DISABLE KEYS */;
INSERT INTO `mechanics` VALUES (1,'Budi Santoso','081200000001',1,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'Agus Pratama','081200000002',1,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'Rudi Hartono','081200000003',1,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'Slamet Riyadi','081200000004',1,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'Mekanik Nonaktif','081200000005',0,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `mechanics` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_19_033049_create_personal_access_tokens_table',1),(5,'2026_11_19_040001_create_customers_table',1),(6,'2026_11_19_040002_create_vehicles_table',1),(7,'2026_11_19_040003_create_mechanics_table',1),(8,'2026_11_19_040004_create_services_table',1),(9,'2026_11_19_040005_create_spareparts_table',1),(10,'2026_11_19_040006_create_work_orders_table',1),(11,'2026_11_19_040007_create_work_order_services_table',1),(12,'2026_11_19_040008_create_work_order_parts_table',1),(13,'2026_11_19_040009_create_payments_table',1),(14,'2026_11_19_040010_create_work_order_status_logs_table',1),(15,'2026_11_19_040011_create_bookings_table',1),(16,'2026_11_19_040012_create_notification_logs_table',1),(17,'2026_11_24_000001_add_down_payment_to_bookings_table',1),(18,'2026_11_24_000002_add_dp_proof_to_bookings_table',1),(19,'2026_11_24_000003_add_dp_proof_at_to_bookings_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `work_order_id` bigint(20) unsigned DEFAULT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `error` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_logs_work_order_id_foreign` (`work_order_id`),
  KEY `notification_logs_booking_id_foreign` (`booking_id`),
  CONSTRAINT `notification_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notification_logs_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notification_logs` WRITE;
/*!40000 ALTER TABLE `notification_logs` DISABLE KEYS */;
INSERT INTO `notification_logs` VALUES (1,'order_selesai','email','',6,NULL,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'order_selesai','email','dedi@example.test',7,NULL,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'order_selesai','email','lestari@example.test',8,NULL,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'order_selesai','email','darimin@example.test',10,NULL,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'order_selesai','email','darimin@example.test',11,NULL,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,'order_selesai','email','darimin@example.test',12,NULL,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,'booking_dibuat','email','booking1@bengkel.test',NULL,1,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,'booking_dibuat','email','booking2@bengkel.test',NULL,2,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,'booking_dibuat','email','booking3@bengkel.test',NULL,3,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,'booking_dibuat','email','',NULL,4,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(11,'booking_dibuat','email','',NULL,5,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(12,'booking_dibuat','email','',NULL,6,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(13,'booking_dikonfirmasi','email','',NULL,5,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(14,'booking_dikonfirmasi','email','',NULL,6,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(15,'booking_dibuat','email','',NULL,7,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(16,'booking_dibatalkan','email','',NULL,7,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(17,'booking_dibuat','email','',NULL,8,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(18,'booking_dibuat','email','lusa1@bengkel.test',NULL,9,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(19,'booking_dibuat','email','lusa2@bengkel.test',NULL,10,'terkirim',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(20,'booking_dibuat','email','',NULL,11,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(21,'booking_dibuat','email','',NULL,12,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(22,'booking_dibuat','email','',NULL,13,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(23,'booking_dikonfirmasi','email','',NULL,13,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(24,'booking_dibuat','email','',NULL,14,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(25,'booking_dibuat','email','',NULL,15,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(26,'booking_dibatalkan','email','',NULL,15,'dilewati',NULL,NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `notification_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint(20) unsigned NOT NULL,
  `method` varchar(255) NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `paid_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_work_order_id_foreign` (`work_order_id`),
  CONSTRAINT `payments_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,7,'tunai',550000,'2026-11-26 10:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,8,'qris',525000,'2026-11-26 10:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,10,'tunai',415000,'2026-11-26 10:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,11,'transfer',980000,'2026-11-26 10:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,12,'tunai',450000,'2026-11-26 10:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,15,'dp',50000,'2026-11-26 09:30:05','2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `est_minutes` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Ganti Oli Mesin Mobil',350000,30,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'Servis Ringan',450000,60,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'Tune Up',500000,120,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'Ganti Kampas Rem Mobil',600000,90,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'Servis AC Mobil',400000,90,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,'Balancing Roda',200000,60,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,'Spooring',350000,120,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,'Ganti Aki',850000,45,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,'Tambal Ban',30000,30,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,'Cuci Mesin',100000,45,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `spareparts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spareparts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `stock` int(10) unsigned NOT NULL,
  `min_stock` int(10) unsigned NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spareparts_sku_unique` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `spareparts` WRITE;
/*!40000 ALTER TABLE `spareparts` DISABLE KEYS */;
INSERT INTO `spareparts` VALUES (1,'OLI-001','Oli Mesin 4L',280000,23,6,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,'FLT-001','Filter Oli',65000,17,5,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'FLT-002','Filter Udara',95000,12,4,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,'BRM-001','Kampas Rem Depan',320000,8,4,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,'BRM-002','Kampas Rem Belakang',280000,7,4,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,'AKI-001','Aki Kering 60Ah',1250000,5,3,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,'BSH-001','Busi Iridium',120000,12,6,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,'BLT-001','V-Belt',210000,8,3,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,'ACR-001','Freon AC R134a',150000,9,4,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,'CLN-001','Cairan Pembersih Mesin',45000,14,5,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(11,'TBN-001','Tambalan Ban',25000,30,10,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(12,'BOL-001','Baut Roda Set',75000,6,4,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(13,'LOW-001','Wiper Blade 20 inci',90000,2,5,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(14,'LOW-002','Kunci Kontak Universal',130000,1,5,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(15,'LOW-003','Lampu Sein Depan',85000,0,4,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `spareparts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'kasir',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Bengkel','admin@bengkel.test',NULL,'$2y$12$ruK/voSgCi80.THMmLdIR.MTxIQOAipddDYm96X4bzsGSDGBHz6ve','admin',NULL,'2026-11-26 03:30:04','2026-11-26 03:30:04'),(2,'Kasir Satu','kasir1@bengkel.test',NULL,'$2y$12$e8NoC7kjkEqu/umId7MR4u5AoxkkD03CfIisyYQ7Wl3Y3mdZxtSBG','kasir',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,'Kasir Dua','kasir2@bengkel.test',NULL,'$2y$12$9uOWqZqwUy2u8O4ilgmu/ONFzIXIXfMrJSTio631C5CCAJ68DXTs2','kasir',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `plate_number` varchar(15) NOT NULL,
  `type` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  KEY `vehicles_customer_id_foreign` (`customer_id`),
  CONSTRAINT `vehicles_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,1,'B001ABC','mobil','Toyota','Avanza',2020,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,2,'B002ABC','mobil','Honda','Brio',2021,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,3,'B003ABC','mobil','Daihatsu','Xenia',2019,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,4,'B004ABC','mobil','Suzuki','Ertiga',2022,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,5,'B005ABC','mobil','Toyota','Innova',2018,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,6,'B006ABC','mobil','Honda','Mobilio',2020,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,7,'B007ABC','mobil','Mitsubishi','Xpander',2021,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,8,'B008ABC','mobil','Nissan','Grand Livina',2017,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,9,'B009ABC','mobil','Toyota','Calya',2019,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,10,'B010ABC','mobil','Suzuki','Ignis',2022,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(11,1,'B011ABC','mobil','Honda','HR-V',2023,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(12,2,'B012ABC','mobil','Mazda','CX-5',2020,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `work_order_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_order_parts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint(20) unsigned NOT NULL,
  `sparepart_id` bigint(20) unsigned NOT NULL,
  `qty` smallint(5) unsigned NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_order_parts_work_order_id_sparepart_id_unique` (`work_order_id`,`sparepart_id`),
  KEY `work_order_parts_sparepart_id_foreign` (`sparepart_id`),
  CONSTRAINT `work_order_parts_sparepart_id_foreign` FOREIGN KEY (`sparepart_id`) REFERENCES `spareparts` (`id`),
  CONSTRAINT `work_order_parts_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `work_order_parts` WRITE;
/*!40000 ALTER TABLE `work_order_parts` DISABLE KEYS */;
INSERT INTO `work_order_parts` VALUES (1,3,1,1,280000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,6,4,1,320000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,7,9,1,150000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,10,2,1,65000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,11,7,4,120000,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `work_order_parts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `work_order_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_order_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_order_services_work_order_id_service_id_unique` (`work_order_id`,`service_id`),
  KEY `work_order_services_service_id_foreign` (`service_id`),
  CONSTRAINT `work_order_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `work_order_services_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `work_order_services` WRITE;
/*!40000 ALTER TABLE `work_order_services` DISABLE KEYS */;
INSERT INTO `work_order_services` VALUES (1,3,1,350000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,4,2,450000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,6,4,600000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,7,5,400000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,8,7,350000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,8,6,200000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,10,1,350000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,11,3,500000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,12,2,450000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,13,3,500000,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `work_order_services` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `work_order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_order_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `work_order_status_logs_work_order_id_foreign` (`work_order_id`),
  KEY `work_order_status_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `work_order_status_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_order_status_logs_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `work_order_status_logs` WRITE;
/*!40000 ALTER TABLE `work_order_status_logs` DISABLE KEYS */;
INSERT INTO `work_order_status_logs` VALUES (1,1,2,NULL,'menunggu','2026-11-26 03:30:05'),(2,2,2,NULL,'menunggu','2026-11-26 03:30:05'),(3,3,2,NULL,'menunggu','2026-11-26 03:30:05'),(4,4,2,NULL,'menunggu','2026-11-26 03:30:05'),(5,5,2,NULL,'menunggu','2026-11-26 03:30:05'),(6,3,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(7,4,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(8,5,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(9,6,2,NULL,'menunggu','2026-11-26 03:30:05'),(10,6,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(11,6,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(12,7,2,NULL,'menunggu','2026-11-26 03:30:05'),(13,7,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(14,7,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(15,7,NULL,'selesai','dibayar','2026-11-26 03:30:05'),(16,8,2,NULL,'menunggu','2026-11-26 03:30:05'),(17,8,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(18,8,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(19,8,NULL,'selesai','dibayar','2026-11-26 03:30:05'),(20,9,2,NULL,'menunggu','2026-11-26 03:30:05'),(21,9,NULL,'menunggu','batal','2026-11-26 03:30:05'),(22,10,2,NULL,'menunggu','2026-11-26 03:30:05'),(23,10,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(24,10,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(25,10,NULL,'selesai','dibayar','2026-11-26 03:30:05'),(26,11,2,NULL,'menunggu','2026-11-26 03:30:05'),(27,11,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(28,11,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(29,11,NULL,'selesai','dibayar','2026-11-26 03:30:05'),(30,12,2,NULL,'menunggu','2026-11-26 03:30:05'),(31,12,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(32,12,NULL,'dikerjakan','selesai','2026-11-26 03:30:05'),(33,12,NULL,'selesai','dibayar','2026-11-26 03:30:05'),(34,13,2,NULL,'menunggu','2026-11-26 03:30:05'),(35,13,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05'),(36,14,2,NULL,'menunggu','2026-11-26 03:30:05'),(37,14,NULL,'menunggu','batal','2026-11-26 03:30:05'),(38,15,2,NULL,'menunggu','2026-11-26 03:30:05'),(39,15,NULL,'menunggu','dikerjakan','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `work_order_status_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `mechanic_id` bigint(20) unsigned DEFAULT NULL,
  `queue_date` date NOT NULL,
  `queue_number` smallint(5) unsigned NOT NULL,
  `tracking_code` varchar(8) NOT NULL,
  `complaint` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'menunggu',
  `public_note` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `discount` int(10) unsigned NOT NULL DEFAULT 0,
  `total` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_orders_queue_date_queue_number_unique` (`queue_date`,`queue_number`),
  UNIQUE KEY `work_orders_tracking_code_unique` (`tracking_code`),
  KEY `work_orders_vehicle_id_foreign` (`vehicle_id`),
  KEY `work_orders_user_id_foreign` (`user_id`),
  KEY `work_orders_mechanic_id_foreign` (`mechanic_id`),
  CONSTRAINT `work_orders_mechanic_id_foreign` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`),
  CONSTRAINT `work_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_orders_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES (1,1,2,NULL,'2026-11-26',1,'G93D2R3G','Rem berbunyi kasar saat mengerem','menunggu',NULL,NULL,NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(2,2,2,NULL,'2026-11-26',2,'4K3ACGD9','AC tidak dingin sejak seminggu terakhir','menunggu',NULL,NULL,NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(3,3,2,1,'2026-11-26',3,'TRACK234','Ganti oli dan filter','dikerjakan',NULL,'2026-11-26 00:30:05',NULL,0,630000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(4,4,2,2,'2026-11-26',4,'2TURHEXV','Servis ringan 40.000 km','dikerjakan',NULL,'2026-11-26 02:15:05',NULL,0,450000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(5,5,2,3,'2026-11-26',5,'89QM225E','Tune up mesin','dikerjakan',NULL,'2026-11-26 03:10:05',NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(6,6,2,4,'2026-11-26',6,'3PDDKQ5C','Kampas rem depan aus','selesai',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',0,920000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(7,7,2,1,'2026-11-26',7,'CTMGR8GD','Servis AC rutin','dibayar',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',0,550000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(8,8,2,2,'2026-11-26',8,'CSZ5MCFN','Spooring dan balancing','dibayar',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',25000,525000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(9,9,2,NULL,'2026-11-26',9,'V6J4ELF8','Minta perbaikan bodi, di luar layanan','batal',NULL,NULL,NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(10,11,2,3,'2026-11-25',1,'WDE4WRSD','Ganti oli berkala','dibayar',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',0,415000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(11,11,2,1,'2026-11-21',1,'R997MX97','Tune up dan ganti busi','dibayar',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',0,980000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(12,11,2,1,'2026-11-14',1,'GCYWE2UC','Servis ringan','dibayar',NULL,'2026-11-26 03:30:05','2026-11-26 03:30:05',0,450000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(13,9,2,2,'2026-11-25',2,'7ECF7Y3A','Servis kopling, mobil ditinggal semalam','dikerjakan',NULL,'2026-11-25 07:00:00',NULL,0,500000,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(14,12,2,NULL,'2026-11-23',1,'EKUBMN9Q','Batal karena pelanggan berubah pikiran','batal',NULL,NULL,NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05'),(15,10,2,3,'2026-11-26',10,'XNDJR83B','Ganti oli, sudah check-in pagi','dikerjakan',NULL,'2026-11-26 03:30:05',NULL,0,0,'2026-11-26 03:30:05','2026-11-26 03:30:05');
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
