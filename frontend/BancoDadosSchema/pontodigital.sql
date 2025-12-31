/*
SQLyog Community v12.4.0 (64 bit)
MySQL - 10.4.32-MariaDB : Database - ponto_eletronico
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`ponto_eletronico` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `ponto_eletronico`;

/*Table structure for table `backups` */

DROP TABLE IF EXISTS `backups`;

CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `tamanho` varchar(20) DEFAULT NULL,
  `tipo` enum('completo','parcial') DEFAULT 'completo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `backups` */

/*Table structure for table `colaboradores` */

DROP TABLE IF EXISTS `colaboradores`;

CREATE TABLE `colaboradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` text DEFAULT NULL,
  `turno` enum('matutino','vespertino','noturno','flexivel') DEFAULT 'matutino',
  `ativo` tinyint(1) DEFAULT 1,
  `permite_duas_empresas` tinyint(1) DEFAULT 0,
  `empresa_secundaria_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `empresa_secundaria_id` (`empresa_secundaria_id`),
  KEY `idx_cpf` (`cpf`),
  KEY `idx_empresa_ativo` (`empresa_id`,`ativo`),
  CONSTRAINT `colaboradores_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `colaboradores_ibfk_2` FOREIGN KEY (`empresa_secundaria_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `colaboradores` */

insert  into `colaboradores`(`id`,`empresa_id`,`codigo`,`nome`,`cpf`,`senha`,`foto`,`turno`,`ativo`,`permite_duas_empresas`,`empresa_secundaria_id`,`created_at`,`updated_at`) values 
(1,1,'ABCD1234','João Carlos','111.111.111-11','',NULL,'matutino',1,0,NULL,'2025-12-23 20:10:40','2025-12-23 20:11:38');

/*Table structure for table `empresas` */

DROP TABLE IF EXISTS `empresas`;

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cnpj` varchar(18) NOT NULL,
  `prefixo` varchar(10) NOT NULL,
  `endereco` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  UNIQUE KEY `prefixo` (`prefixo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `empresas` */

insert  into `empresas`(`id`,`nome`,`cnpj`,`prefixo`,`endereco`,`telefone`,`email`,`ativa`,`created_at`,`updated_at`) values 
(1,'Empresa Principal','00.000.000/0001-00','EMP',NULL,NULL,'contato@empresa.com',1,'2025-12-23 14:12:01','2025-12-23 14:12:01');

/*Table structure for table `logs_sistema` */

DROP TABLE IF EXISTS `logs_sistema`;

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `colaborador_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `colaborador_id` (`colaborador_id`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logs_sistema_ibfk_2` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `logs_sistema` */

/*Table structure for table `registros_ponto` */

DROP TABLE IF EXISTS `registros_ponto`;

CREATE TABLE `registros_ponto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida','entrada_intervalo','retorno_intervalo') NOT NULL,
  `data_registro` date NOT NULL,
  `hora_registro` time NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metodo` enum('web','facial','mobile') DEFAULT 'web',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_colaborador_tipo_hora` (`colaborador_id`,`tipo`,`data_registro`,`hora_registro`),
  KEY `idx_colaborador_data` (`colaborador_id`,`data_registro`),
  KEY `idx_empresa_data` (`empresa_id`,`data_registro`),
  CONSTRAINT `registros_ponto_ibfk_1` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registros_ponto_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `registros_ponto` */

/*Table structure for table `trocas_turno` */

DROP TABLE IF EXISTS `trocas_turno`;

CREATE TABLE `trocas_turno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `colaborador_substituido_id` int(11) NOT NULL,
  `colaborador_substituto_id` int(11) NOT NULL,
  `data_troca` date NOT NULL,
  `periodo` enum('manha','tarde','noite','dia_inteiro') NOT NULL,
  `motivo` text DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `status` enum('pendente','aprovado','recusado') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `colaborador_substituido_id` (`colaborador_substituido_id`),
  KEY `colaborador_substituto_id` (`colaborador_substituto_id`),
  KEY `aprovado_por` (`aprovado_por`),
  CONSTRAINT `trocas_turno_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trocas_turno_ibfk_2` FOREIGN KEY (`colaborador_substituido_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trocas_turno_ibfk_3` FOREIGN KEY (`colaborador_substituto_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trocas_turno_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `trocas_turno` */

/*Table structure for table `usuarios` */

DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('super','admin','ti','gestor','user') DEFAULT 'admin',
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_empresa_ativo` (`empresa_id`,`ativo`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `usuarios` */

insert  into `usuarios`(`id`,`empresa_id`,`codigo`,`nome`,`cpf`,`email`,`senha`,`tipo`,`ativo`,`ultimo_login`,`created_at`,`updated_at`) values 
(1,1,'EMP001','João Carlos Moreira Alves Junior','000.000.000-00','admin@admin.com','$2y$10$yshRTvuF9URWi/bY12obdew/kAG//MEFxQK68QHovaTP/LipflL2G','super',1,NULL,'2025-12-23 14:12:01','2025-12-23 14:48:23');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
