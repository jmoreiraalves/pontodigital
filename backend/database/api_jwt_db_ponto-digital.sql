/*
SQLyog Community v12.4.0 (64 bit)
MySQL - 10.4.32-MariaDB : Database - api_jwt_db
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`api_jwt_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `api_jwt_db`;

/*Table structure for table `employees` */

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `cpf` varchar(30) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ctps` varchar(20) DEFAULT NULL,
  `pis` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL COMMENT 'data contratacao',
  `user_id` int(11) DEFAULT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `phone_contact` varchar(20) DEFAULT NULL,
  `turno` enum('manha','tarde','noite','admin') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `employees` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cpf` varchar(20) NOT NULL,
  `role` enum('admin','user','super','rh') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`password`,`cpf`,`role`,`status`,`reset_token`,`reset_token_expiry`,`created_at`,`updated_at`) values 
(1,'Administrador','admin@email.com','$2y$10$5IroL0fz/bSVjV/7RDPb..ad8jwxdh0ibQvhTXSxsgR.QTfkpfdTu','','admin','active',NULL,NULL,'2025-12-18 13:27:48','2025-12-18 15:22:11'),
(2,'João','joao@email.com','$2y$10$2FYA/exVElrlxZkLK55S4eS5P0AO8ysi9IiNMYN/36zvtZ4MRnATe','','user','active',NULL,NULL,'2025-12-18 15:08:49','2025-12-18 15:08:49'),
(4,'João Admin','adminjoao@email.com','$2y$10$RWPCfNGXncRndLLnmJhQGeeCWrOP.tVAHjFAVOQPjrhEx8Cb5ZtZm','','user','active',NULL,NULL,'2025-12-18 15:10:17','2025-12-18 15:10:17');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
