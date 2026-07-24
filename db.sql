/*
SQLyog Ultimate v13.1.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - uniform_system
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`uniform_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `uniform_system`;

/*Table structure for table `admin_users` */

DROP TABLE IF EXISTS `admin_users`;

CREATE TABLE `admin_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('system_admin','operation','sales','accounting') NOT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `blocked_reason` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_users_user_id_foreign` (`user_id`),
  CONSTRAINT `admin_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `admin_users` */

insert  into `admin_users`(`id`,`user_id`,`role`,`blocked_at`,`blocked_reason`,`deleted_at`,`deleted_reason`,`created_at`,`updated_at`) values 
(1,3,'system_admin',NULL,NULL,NULL,NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09'),
(2,4,'operation',NULL,NULL,NULL,NULL,'2025-12-19 20:38:09','2025-12-22 13:56:12'),
(3,5,'sales',NULL,NULL,NULL,NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09'),
(4,6,'accounting',NULL,NULL,NULL,NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09');

/*Table structure for table `application_items` */

DROP TABLE IF EXISTS `application_items`;

CREATE TABLE `application_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `uniform_product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `action` enum('loan','return','exchange_out','exchange_in','transfer_change') NOT NULL DEFAULT 'loan',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_items_application_id_foreign` (`application_id`),
  KEY `application_items_uniform_product_id_foreign` (`uniform_product_id`),
  CONSTRAINT `application_items_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_items_uniform_product_id_foreign` FOREIGN KEY (`uniform_product_id`) REFERENCES `uniform_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `application_items` */

insert  into `application_items`(`id`,`application_id`,`uniform_product_id`,`quantity`,`action`,`created_at`,`updated_at`) values 
(10,6,66,3,'loan','2025-12-23 15:57:27','2025-12-23 15:57:27'),
(11,7,66,3,'loan','2025-12-23 15:57:29','2025-12-23 15:57:29'),
(12,8,66,3,'loan','2025-12-23 15:57:29','2025-12-23 15:57:29');

/*Table structure for table `applications` */

DROP TABLE IF EXISTS `applications`;

CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `type` enum('new_loan','resize','return','exchange','transfer') NOT NULL,
  `status` enum('pending','waiting_special_approval','approved','rejected','cancel_request','cancel_approved','cancel_rejected') NOT NULL DEFAULT 'pending',
  `billing_status` enum('waiting','approved','rejected') DEFAULT 'waiting',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `delivery_location_id` bigint(20) unsigned DEFAULT NULL,
  `requested_by_user_id` bigint(20) unsigned NOT NULL,
  `special_approval_reason` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by_admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applications_medical_institution_id_foreign` (`medical_institution_id`),
  KEY `applications_department_id_foreign` (`department_id`),
  KEY `applications_employee_id_foreign` (`employee_id`),
  KEY `applications_delivery_location_id_foreign` (`delivery_location_id`),
  KEY `applications_requested_by_user_id_foreign` (`requested_by_user_id`),
  KEY `applications_approved_by_admin_user_id_foreign` (`approved_by_admin_user_id`),
  CONSTRAINT `applications_approved_by_admin_user_id_foreign` FOREIGN KEY (`approved_by_admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_delivery_location_id_foreign` FOREIGN KEY (`delivery_location_id`) REFERENCES `delivery_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `applications` */

insert  into `applications`(`id`,`medical_institution_id`,`department_id`,`employee_id`,`type`,`status`,`billing_status`,`start_date`,`end_date`,`return_date`,`delivery_location_id`,`requested_by_user_id`,`special_approval_reason`,`rejection_reason`,`approved_at`,`approved_by_admin_user_id`,`created_at`,`updated_at`) values 
(6,1,4,20,'new_loan','pending','waiting','2025-12-23','2025-12-27',NULL,3,1,NULL,NULL,'2025-12-23 16:13:09',3,'2025-12-23 15:57:27','2025-12-23 16:13:09'),
(7,1,4,21,'new_loan','pending','waiting','2025-12-23','2025-12-27',NULL,3,1,NULL,NULL,'2025-12-23 16:11:02',3,'2025-12-23 15:57:29','2025-12-23 16:11:02'),
(8,1,4,22,'new_loan','pending','waiting','2025-12-23','2025-12-27',NULL,3,1,NULL,NULL,'2025-12-23 16:12:48',3,'2025-12-23 15:57:29','2025-12-23 16:12:48');

/*Table structure for table `billing_snapshots` */

DROP TABLE IF EXISTS `billing_snapshots`;

CREATE TABLE `billing_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `target_date` date NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `generated_by_admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_snapshots_medical_institution_id_foreign` (`medical_institution_id`),
  KEY `billing_snapshots_generated_by_admin_user_id_foreign` (`generated_by_admin_user_id`),
  KEY `billing_snapshots_department_id_foreign` (`department_id`),
  CONSTRAINT `billing_snapshots_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_snapshots_generated_by_admin_user_id_foreign` FOREIGN KEY (`generated_by_admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_snapshots_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `billing_snapshots` */

/*Table structure for table `cache` */

DROP TABLE IF EXISTS `cache`;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache` */

/*Table structure for table `cache_locks` */

DROP TABLE IF EXISTS `cache_locks`;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache_locks` */

/*Table structure for table `contracts` */

DROP TABLE IF EXISTS `contracts`;

CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `contract_number` varchar(255) DEFAULT NULL,
  `contract_type` enum('FIXED','POINT_BASED') NOT NULL DEFAULT 'FIXED',
  `billing_model` enum('fixed_per_person','point_usage') NOT NULL DEFAULT 'fixed_per_person',
  `per_person_loan_limit` int(10) unsigned DEFAULT NULL,
  `loan_limit_per_item` int(10) unsigned DEFAULT NULL,
  `point_unit_price` decimal(10,2) DEFAULT NULL,
  `fixed_unit_price` decimal(10,2) DEFAULT NULL,
  `cleaning_multiplier` decimal(5,2) NOT NULL DEFAULT 1.67 COMMENT 'Multiplier to calculate cleaning count from return points (default: 1.67)',
  `billing_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_rules`)),
  `inventory_constraints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inventory_constraints`)),
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contracts_contract_number_unique` (`contract_number`),
  KEY `contracts_medical_institution_id_foreign` (`medical_institution_id`),
  CONSTRAINT `contracts_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `contracts` */

insert  into `contracts`(`id`,`medical_institution_id`,`contract_number`,`contract_type`,`billing_model`,`per_person_loan_limit`,`loan_limit_per_item`,`point_unit_price`,`fixed_unit_price`,`cleaning_multiplier`,`billing_rules`,`inventory_constraints`,`valid_from`,`valid_until`,`created_at`,`updated_at`) values 
(1,1,'CT-20001','POINT_BASED','point_usage',20,10,100.00,5000.00,2.00,NULL,NULL,'2024-12-19','2026-12-19','2025-12-19 20:38:08','2025-12-23 16:47:31');

/*Table structure for table `customer_users` */

DROP TABLE IF EXISTS `customer_users`;

CREATE TABLE `customer_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `role` enum('institution_admin','department_admin') NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_users_user_id_foreign` (`user_id`),
  KEY `customer_users_medical_institution_id_foreign` (`medical_institution_id`),
  KEY `customer_users_department_id_foreign` (`department_id`),
  CONSTRAINT `customer_users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_users_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `customer_users` */

insert  into `customer_users`(`id`,`user_id`,`medical_institution_id`,`role`,`department_id`,`created_at`,`updated_at`) values 
(1,1,1,'institution_admin',NULL,'2025-12-19 20:38:08','2025-12-19 20:38:08'),
(2,2,1,'department_admin',1,'2025-12-19 20:38:08','2025-12-19 20:38:08'),
(3,7,1,'department_admin',4,'2025-12-22 18:44:24','2025-12-22 18:44:24');

/*Table structure for table `delivery_locations` */

DROP TABLE IF EXISTS `delivery_locations`;

CREATE TABLE `delivery_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_locations_medical_institution_id_foreign` (`medical_institution_id`),
  CONSTRAINT `delivery_locations_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `delivery_locations` */

insert  into `delivery_locations`(`id`,`medical_institution_id`,`name`,`note`,`created_at`,`updated_at`) values 
(1,1,'本館 1階 受付','平日 9:00-17:00','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(2,1,'別館 2階 倉庫','平日 10:00-16:00','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(3,1,'中央倉庫','24時間対応','2025-12-19 20:38:08','2025-12-19 20:38:08');

/*Table structure for table `departments` */

DROP TABLE IF EXISTS `departments`;

CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_medical_institution_id_foreign` (`medical_institution_id`),
  CONSTRAINT `departments_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `departments` */

insert  into `departments`(`id`,`medical_institution_id`,`name`,`code`,`created_at`,`updated_at`) values 
(1,1,'外科','DEPT-001','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(2,1,'内科','DEPT-002','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(3,1,'看護部','DEPT-003','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(4,1,'循環器内科','DEPT-004','2025-12-19 20:40:51','2025-12-23 09:07:02');

/*Table structure for table `employee_loans` */

DROP TABLE IF EXISTS `employee_loans`;

CREATE TABLE `employee_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `loan_start_date` date DEFAULT NULL,
  `loan_end_date` date DEFAULT NULL,
  `uniform_product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('in_use','scheduled_return','returned') NOT NULL DEFAULT 'in_use',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `application_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_loans_uniform_product_id_foreign` (`uniform_product_id`),
  KEY `employee_loans_application_id_foreign` (`application_id`),
  KEY `employee_loans_employee_id_loan_start_date_index` (`employee_id`,`loan_start_date`),
  KEY `employee_loans_employee_id_loan_end_date_index` (`employee_id`,`loan_end_date`),
  CONSTRAINT `employee_loans_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_loans_uniform_product_id_foreign` FOREIGN KEY (`uniform_product_id`) REFERENCES `uniform_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `employee_loans` */

insert  into `employee_loans`(`id`,`employee_id`,`loan_start_date`,`loan_end_date`,`uniform_product_id`,`quantity`,`status`,`created_at`,`updated_at`,`application_id`) values 
(14,21,'2025-12-23',NULL,66,3,'in_use','2025-12-23 16:11:02','2025-12-23 16:11:02',7),
(15,22,'2025-12-23',NULL,66,3,'in_use','2025-12-23 16:12:48','2025-12-23 16:12:48',8),
(16,20,'2025-12-23',NULL,66,3,'in_use','2025-12-23 16:13:09','2025-12-23 16:13:09',6);

/*Table structure for table `employee_positions` */

DROP TABLE IF EXISTS `employee_positions`;

CREATE TABLE `employee_positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `job_type_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_positions_employee_id_foreign` (`employee_id`),
  KEY `employee_positions_department_id_foreign` (`department_id`),
  KEY `employee_positions_job_type_id_foreign` (`job_type_id`),
  CONSTRAINT `employee_positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_positions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_positions_job_type_id_foreign` FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `employee_positions` */

/*Table structure for table `employee_status_history` */

DROP TABLE IF EXISTS `employee_status_history`;

CREATE TABLE `employee_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `status` enum('employed','on_leave','retired') NOT NULL DEFAULT 'employed',
  `effective_from` date NOT NULL,
  `effective_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `changed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_status_history_changed_by_user_id_foreign` (`changed_by_user_id`),
  KEY `employee_status_history_employee_id_effective_from_index` (`employee_id`,`effective_from`),
  KEY `employee_status_history_employee_id_effective_until_index` (`employee_id`,`effective_until`),
  CONSTRAINT `employee_status_history_changed_by_user_id_foreign` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_status_history_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `employee_status_history` */

/*Table structure for table `employees` */

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `staff_no` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `top_size` varchar(255) DEFAULT NULL,
  `bottom_size` varchar(255) DEFAULT NULL,
  `shoe_size` varchar(255) DEFAULT NULL,
  `status` enum('employed','on_leave','retired') NOT NULL DEFAULT 'employed',
  `primary_department_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_medical_institution_id_foreign` (`medical_institution_id`),
  KEY `employees_primary_department_id_foreign` (`primary_department_id`),
  KEY `employees_staff_no_index` (`staff_no`),
  CONSTRAINT `employees_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_primary_department_id_foreign` FOREIGN KEY (`primary_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `employees` */

insert  into `employees`(`id`,`medical_institution_id`,`staff_no`,`name`,`gender`,`top_size`,`bottom_size`,`shoe_size`,`status`,`primary_department_id`,`created_at`,`updated_at`) values 
(19,1,'10001','従業員1','male','S','S','22.5','employed',3,'2025-12-22 15:09:02','2025-12-23 10:16:26'),
(20,1,'10002','従業員2','male','XS','XS','28.0','employed',4,'2025-12-22 15:09:02','2025-12-23 09:03:15'),
(21,1,'10011','従業員3','male','XS','S','27.0','employed',4,'2025-12-22 18:43:16','2025-12-23 15:40:33'),
(22,1,'11111','従業員4','male','XS','XS','26.5','employed',4,'2025-12-22 18:45:25','2025-12-23 09:03:54');

/*Table structure for table `failed_jobs` */

DROP TABLE IF EXISTS `failed_jobs`;

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

/*Data for the table `failed_jobs` */

/*Table structure for table `job_batches` */

DROP TABLE IF EXISTS `job_batches`;

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

/*Data for the table `job_batches` */

/*Table structure for table `job_types` */

DROP TABLE IF EXISTS `job_types`;

CREATE TABLE `job_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_types_medical_institution_id_foreign` (`medical_institution_id`),
  CONSTRAINT `job_types_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_types` */

insert  into `job_types`(`id`,`medical_institution_id`,`name`,`code`,`created_at`,`updated_at`) values 
(1,1,'医師','JOB-001','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(2,1,'看護師','JOB-002','2025-12-19 20:38:08','2025-12-19 20:38:08'),
(3,1,'薬剤師','JOB-003','2025-12-19 20:38:08','2025-12-19 20:38:08');

/*Table structure for table `jobs` */

DROP TABLE IF EXISTS `jobs`;

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

/*Data for the table `jobs` */

/*Table structure for table `login_attempts` */

DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_attempts_user_id_foreign` (`user_id`),
  CONSTRAINT `login_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `login_attempts` */

insert  into `login_attempts`(`id`,`user_id`,`email`,`is_success`,`attempted_at`,`created_at`,`updated_at`) values 
(1,3,'kamatani-admin@example.com',1,'2025-12-19 20:39:23','2025-12-19 20:39:23','2025-12-19 20:39:23'),
(2,1,'hospital-admin@example.com',1,'2025-12-19 20:41:11','2025-12-19 20:41:11','2025-12-19 20:41:11'),
(3,5,'sales-admin@example.com',0,'2025-12-19 22:05:28','2025-12-19 22:05:28','2025-12-19 22:05:28'),
(4,5,'sales-admin@example.com',1,'2025-12-19 22:05:37','2025-12-19 22:05:37','2025-12-19 22:05:37'),
(5,3,'kamatani-admin@example.com',1,'2025-12-20 02:27:31','2025-12-20 02:27:31','2025-12-20 02:27:31'),
(6,1,'hospital-admin@example.com',1,'2025-12-20 06:06:34','2025-12-20 06:06:34','2025-12-20 06:06:34'),
(7,2,'department-admin@example.com',1,'2025-12-20 16:52:32','2025-12-20 16:52:32','2025-12-20 16:52:32'),
(8,3,'kamatani-admin@example.com',1,'2025-12-20 16:53:18','2025-12-20 16:53:18','2025-12-20 16:53:18'),
(9,3,'kamatani-admin@example.com',1,'2025-12-21 01:15:16','2025-12-21 01:15:16','2025-12-21 01:15:16'),
(10,3,'kamatani-admin@example.com',1,'2025-12-21 02:45:34','2025-12-21 02:45:34','2025-12-21 02:45:34'),
(11,1,'hospital-admin@example.com',1,'2025-12-21 03:20:55','2025-12-21 03:20:55','2025-12-21 03:20:55'),
(12,3,'kamatani-admin@example.com',1,'2025-12-21 12:56:07','2025-12-21 12:56:07','2025-12-21 12:56:07'),
(13,1,'hospital-admin@example.com',0,'2025-12-22 08:44:31','2025-12-22 08:44:31','2025-12-22 08:44:31'),
(14,1,'hospital-admin@example.com',0,'2025-12-22 08:44:47','2025-12-22 08:44:47','2025-12-22 08:44:47'),
(15,4,'operations-admin@example.com',1,'2025-12-22 08:45:36','2025-12-22 08:45:36','2025-12-22 08:45:36'),
(16,5,'sales-admin@example.com',1,'2025-12-22 08:46:16','2025-12-22 08:46:16','2025-12-22 08:46:16'),
(17,6,'accounting-admin@example.com',1,'2025-12-22 08:46:48','2025-12-22 08:46:48','2025-12-22 08:46:48'),
(18,NULL,'uniform-admin@example.com',0,'2025-12-22 11:59:00','2025-12-22 11:59:00','2025-12-22 11:59:00'),
(19,1,'hospital-admin@example.com',1,'2025-12-22 12:22:08','2025-12-22 12:22:08','2025-12-22 12:22:08'),
(20,3,'kamatani-admin@example.com',1,'2025-12-22 16:18:18','2025-12-22 16:18:18','2025-12-22 16:18:18'),
(21,1,'hospital-admin@example.com',1,'2025-12-22 16:53:08','2025-12-22 16:53:08','2025-12-22 16:53:08'),
(22,1,'hospital-admin@example.com',1,'2025-12-22 17:23:56','2025-12-22 17:23:56','2025-12-22 17:23:56'),
(23,7,'shitianheshu13@gmail.com',1,'2025-12-22 18:44:42','2025-12-22 18:44:42','2025-12-22 18:44:42'),
(24,1,'hospital-admin@example.com',1,'2025-12-22 18:45:54','2025-12-22 18:45:54','2025-12-22 18:45:54'),
(25,4,'operations-admin@example.com',0,'2025-12-22 18:55:51','2025-12-22 18:55:51','2025-12-22 18:55:51'),
(26,4,'operations-admin@example.com',0,'2025-12-22 18:56:00','2025-12-22 18:56:00','2025-12-22 18:56:00'),
(27,4,'operations-admin@example.com',0,'2025-12-22 18:56:00','2025-12-22 18:56:00','2025-12-22 18:56:00'),
(28,4,'operations-admin@example.com',0,'2025-12-22 18:56:00','2025-12-22 18:56:00','2025-12-22 18:56:00'),
(29,3,'kamatani-admin@example.com',0,'2025-12-22 18:56:15','2025-12-22 18:56:15','2025-12-22 18:56:15'),
(30,4,'operations-admin@example.com',1,'2025-12-22 18:56:30','2025-12-22 18:56:30','2025-12-22 18:56:30'),
(31,6,'accounting-admin@example.com',1,'2025-12-22 19:02:11','2025-12-22 19:02:11','2025-12-22 19:02:11'),
(32,3,'kamatani-admin@example.com',1,'2025-12-22 19:58:56','2025-12-22 19:58:56','2025-12-22 19:58:56'),
(33,3,'kamatani-admin@example.com',1,'2025-12-22 20:27:55','2025-12-22 20:27:55','2025-12-22 20:27:55'),
(34,3,'kamatani-admin@example.com',1,'2025-12-23 05:32:57','2025-12-23 05:32:57','2025-12-23 05:32:57'),
(35,1,'hospital-admin@example.com',0,'2025-12-23 07:39:41','2025-12-23 07:39:41','2025-12-23 07:39:41'),
(36,1,'hospital-admin@example.com',0,'2025-12-23 07:39:52','2025-12-23 07:39:52','2025-12-23 07:39:52'),
(37,1,'hospital-admin@example.com',0,'2025-12-23 07:40:15','2025-12-23 07:40:15','2025-12-23 07:40:15'),
(38,1,'hospital-admin@example.com',1,'2025-12-23 07:41:00','2025-12-23 07:41:00','2025-12-23 07:41:00');

/*Table structure for table `medical_institutions` */

DROP TABLE IF EXISTS `medical_institutions`;

CREATE TABLE `medical_institutions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `billing_model` enum('fixed_per_person','point_usage') NOT NULL DEFAULT 'fixed_per_person',
  `notification_email` varchar(255) DEFAULT NULL,
  `per_person_loan_limit` int(10) unsigned DEFAULT NULL,
  `point_unit_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_institutions_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `medical_institutions` */

insert  into `medical_institutions`(`id`,`name`,`code`,`billing_model`,`notification_email`,`per_person_loan_limit`,`point_unit_price`,`created_at`,`updated_at`) values 
(1,'総合病院1','HOSP-001','fixed_per_person','uniform-admin@example.com',5,10.00,'2025-12-19 20:38:08','2025-12-23 09:01:41');

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_12_18_000100_create_medical_institutions_and_masters',1),
(5,'2025_12_18_000200_create_employees_and_positions',1),
(6,'2025_12_18_000300_create_uniforms_and_contracts',1),
(7,'2025_12_18_000400_create_applications_and_billing',1),
(8,'2025_12_18_000500_create_login_attempts_and_role_tables',1),
(9,'2025_12_18_105526_create_notifications_table',1),
(10,'2025_12_18_110057_add_rejection_reason_to_applications_table',1),
(11,'2025_12_19_152338_enhance_contracts_table_add_missing_fields',1),
(12,'2025_12_19_152343_create_employee_status_history_table',1),
(13,'2025_12_19_152347_add_loan_start_end_dates_to_employee_loans',1),
(14,'2025_12_20_000001_add_cleaning_multiplier_and_contract_number_to_contracts',2),
(15,'2025_12_21_012346_add_block_delete_fields_to_admin_users_table',2);

/*Table structure for table `notifications` */

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `notifications_created_at_index` (`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notifications` */

insert  into `notifications`(`id`,`user_id`,`type`,`title`,`message`,`link`,`is_read`,`read_at`,`data`,`created_at`,`updated_at`) values 
(18,3,'application_created','新しい申請が登録されました','申請ID: #APP-6 (従業員2)','http://localhost:8000/admin/applications/6',0,NULL,'{\"application_id\":6}','2025-12-23 15:57:27','2025-12-23 15:57:27'),
(19,4,'application_created','新しい申請が登録されました','申請ID: #APP-6 (従業員2)','http://localhost:8000/admin/applications/6',0,NULL,'{\"application_id\":6}','2025-12-23 15:57:27','2025-12-23 15:57:27'),
(20,3,'application_created','新しい申請が登録されました','申請ID: #APP-7 (従業員3)','http://localhost:8000/admin/applications/7',0,NULL,'{\"application_id\":7}','2025-12-23 15:57:29','2025-12-23 15:57:29'),
(21,4,'application_created','新しい申請が登録されました','申請ID: #APP-7 (従業員3)','http://localhost:8000/admin/applications/7',0,NULL,'{\"application_id\":7}','2025-12-23 15:57:29','2025-12-23 15:57:29'),
(22,3,'application_created','新しい申請が登録されました','申請ID: #APP-8 (従業員4)','http://localhost:8000/admin/applications/8',0,NULL,'{\"application_id\":8}','2025-12-23 15:57:29','2025-12-23 15:57:29'),
(23,4,'application_created','新しい申請が登録されました','申請ID: #APP-8 (従業員4)','http://localhost:8000/admin/applications/8',0,NULL,'{\"application_id\":8}','2025-12-23 15:57:29','2025-12-23 15:57:29'),
(25,1,'application_approved','申請が承認されました','申請ID: #APP-7 が承認されました','http://localhost:8000/applications/7',0,NULL,'{\"application_id\":7}','2025-12-23 16:11:02','2025-12-23 16:11:02'),
(26,1,'application_approved','申請が承認されました','申請ID: #APP-8 が承認されました','http://localhost:8000/applications/8',0,NULL,'{\"application_id\":8}','2025-12-23 16:12:48','2025-12-23 16:12:48'),
(27,1,'application_approved','申請が承認されました','申請ID: #APP-6 が承認されました','http://localhost:8000/applications/6',0,NULL,'{\"application_id\":6}','2025-12-23 16:13:09','2025-12-23 16:13:09');

/*Table structure for table `password_reset_tokens` */

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_reset_tokens` */

/*Table structure for table `sessions` */

DROP TABLE IF EXISTS `sessions`;

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

/*Data for the table `sessions` */

insert  into `sessions`(`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) values 
('Omk3jOsywmai9AN0BAPtjT9Bd1j9Cl4I287DHekV',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWnFTNERZazcwVWE0YlFuRTd0aTgzQlRIblZ3WERJUWFVUXV2TjNOOSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO3M6NToicm91dGUiO3M6MzA6ImFwaS5ub3RpZmljYXRpb25zLnVucmVhZF9jb3VudCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==',1766518822),
('QKKRWTUuB1yan0nSz6xcYBrIFhJitOznapQ7tMeL',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVWZBRzlUM0cxZElqS0x5RklLbG1HY2lqWWoxV1RqVUxtVElsbHZQdiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO3M6NToicm91dGUiO3M6MzA6ImFwaS5ub3RpZmljYXRpb25zLnVucmVhZF9jb3VudCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1766518792);

/*Table structure for table `uniform_products` */

DROP TABLE IF EXISTS `uniform_products`;

CREATE TABLE `uniform_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_institution_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `product_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('top','bottom','shoe','other') NOT NULL DEFAULT 'other',
  `size` varchar(255) NOT NULL,
  `gender` enum('male','female','unisex') NOT NULL DEFAULT 'unisex',
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniform_products_product_code_unique` (`product_code`),
  KEY `uniform_products_medical_institution_id_foreign` (`medical_institution_id`),
  KEY `uniform_products_department_id_foreign` (`department_id`),
  CONSTRAINT `uniform_products_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uniform_products_medical_institution_id_foreign` FOREIGN KEY (`medical_institution_id`) REFERENCES `medical_institutions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `uniform_products` */

insert  into `uniform_products`(`id`,`medical_institution_id`,`department_id`,`product_code`,`name`,`category`,`size`,`gender`,`stock_quantity`,`image_path`,`created_at`,`updated_at`) values 
(47,1,2,'TOP-F-1-M','医療用上衣（女性用）','top','M','female',27,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-22 13:17:01','2025-12-23 14:20:48'),
(48,1,2,'TOP-F-1-S','医療用上衣（女性用）','top','S','female',9,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-22 13:17:01','2025-12-23 14:20:48'),
(49,1,3,'TOP-M-1-M','医療用上衣（男性用）','top','M','male',29,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-22 13:17:01','2025-12-23 14:32:00'),
(50,1,1,'BOT-F-2-M','医療用ズボン（女性用）','bottom','M','female',20,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-22 13:17:01','2025-12-23 14:25:51'),
(51,1,4,'BOT-M-1-M','医療用ズボン （男性用）','bottom','M','male',20,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-22 13:17:01','2025-12-23 14:30:39'),
(52,1,4,'BOT-M-1-XL','医療用ズボン （男性用）','bottom','XL','male',30,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-22 13:17:01','2025-12-23 14:30:39'),
(53,1,1,'BOT-F-2-S','医療用ズボン（女性用）','bottom','S','female',30,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-22 13:18:10','2025-12-23 14:25:51'),
(54,1,2,'TOP-F-1-XS','医療用上衣（女性用）','top','XS','female',20,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-23 14:20:48','2025-12-23 14:20:48'),
(55,1,2,'TOP-F-1-L','医療用上衣（女性用）','top','L','female',20,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-23 14:20:48','2025-12-23 14:20:48'),
(56,1,2,'TOP-F-1-XL','医療用上衣（女性用）','top','XL','female',20,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-23 14:20:48','2025-12-23 14:20:48'),
(57,1,2,'TOP-F-1-XXL','医療用上衣（女性用）','top','XXL','female',20,'uniforms/OxhfqwVzwrXmNSBlF0BmixPL0BVUHAIN4jpDNPcB.png','2025-12-23 14:20:48','2025-12-23 14:20:48'),
(58,1,1,'BOT-F-2-XS','医療用ズボン（女性用）','bottom','XS','female',20,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-23 14:25:51','2025-12-23 14:25:51'),
(59,1,1,'BOT-F-2-XL','医療用ズボン（女性用）','bottom','XL','female',20,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-23 14:25:51','2025-12-23 14:25:51'),
(60,1,1,'BOT-F-2-L','医療用ズボン（女性用）','bottom','L','female',10,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-23 14:25:51','2025-12-23 14:25:51'),
(61,1,1,'BOT-F-2-XXL','医療用ズボン（女性用）','bottom','XXL','female',10,'uniforms/l8WFQtJKVmEjfYBojD3IkTu8uOeM7UfFWduSlSj7.png','2025-12-23 14:25:51','2025-12-23 14:31:02'),
(62,1,4,'BOT-M-1-XS','医療用ズボン （男性用）','bottom','XS','male',10,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-23 14:30:40','2025-12-23 14:30:40'),
(63,1,4,'BOT-M-1-S','医療用ズボン （男性用）','bottom','S','male',20,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-23 14:30:40','2025-12-23 14:30:40'),
(64,1,4,'BOT-M-1-L','医療用ズボン （男性用）','bottom','L','male',20,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-23 14:30:40','2025-12-23 14:30:40'),
(65,1,4,'BOT-M-1-XXL','医療用ズボン （男性用）','bottom','XXL','male',10,'uniforms/HlsD2sI5bBE9J1GaE2rUGsatH7SEZmTuLQTxvry7.png','2025-12-23 14:30:40','2025-12-23 14:30:40'),
(66,1,3,'TOP-M-1-XS','医療用上衣（男性用）','top','XS','male',10,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-23 14:32:00','2025-12-23 19:38:16'),
(67,1,3,'TOP-M-1-S','医療用上衣（男性用）','top','S','male',10,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-23 14:32:00','2025-12-23 14:32:00'),
(68,1,3,'TOP-M-1-L','医療用上衣（男性用）','top','L','male',10,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-23 14:32:00','2025-12-23 14:32:00'),
(69,1,3,'TOP-M-1-XL','医療用上衣（男性用）','top','XL','male',10,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-23 14:32:00','2025-12-23 14:32:00'),
(70,1,3,'TOP-M-1-XXL','医療用上衣（男性用）','top','XXL','male',10,'uniforms/lekWOxbYVb63bWFjZhtA0175YhtEfIYAhswC2tuT.png','2025-12-23 14:32:00','2025-12-23 14:32:00');

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'customer_admin',
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`,`type`,`locked_until`) values 
(1,'医療機関 全体管理者','hospital-admin@example.com',NULL,'$2y$12$4h9PLg4xO5EI4n4TiezJy.Oj5zWxhgAt/ddCS.mUsd5WxRtfSBcJm','jmbC5hf89ynrUSP7s8C4b60omgYTfPdnChppoLrq2C544abVVxgmrLzQ3CxB','2025-12-19 20:38:08','2025-12-23 07:40:51','customer_admin',NULL),
(2,'部署管理者','department-admin@example.com',NULL,'$2y$12$WnckbME/bgGgUD0niFp9BO6ayMqKJ1wpylI42.IJ0gA5o8GbVFWMe',NULL,'2025-12-19 20:38:08','2025-12-19 20:38:08','customer_admin',NULL),
(3,'鎌谷 システム管理者','kamatani-admin@example.com',NULL,'$2y$12$jJsgAo2w.SDuvBXYDf3Rhe/8u0qcZRLDc6WFz9OJbKZZwfOoErCha',NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09','kamatani_admin',NULL),
(4,'運営管理者','operations-admin@example.com',NULL,'$2y$12$KFNXjXLzxDqo/AJ8llXNbeXfsCMfKeswXN8JUDVAvEcw5Sr7rmqtO',NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09','kamatani_admin',NULL),
(5,'営業管理者','sales-admin@example.com',NULL,'$2y$12$Y.aF4bt11WJO/MExAkdmkunN00NxMpPgXcuwl/mMCzgpWCsoTf0xO',NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09','kamatani_admin',NULL),
(6,'経理管理者','accounting-admin@example.com',NULL,'$2y$12$3oFdNIa3WDqukhnhEYUOteFNJmNopZIU0pZdY3.SwnfwfJSca1EQO',NULL,'2025-12-19 20:38:09','2025-12-19 20:38:09','kamatani_admin',NULL),
(7,'後藤 章太','shitianheshu13@gmail.com',NULL,'$2y$12$0YlHpPzKbFhZp6IICcS6nezWoi7u1PEEnxBYfe07KHGele81qfEim',NULL,'2025-12-22 18:44:24','2025-12-22 18:44:24','customer_admin',NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
