USE api_jwt_db;

ALTER TABLE `employees` 
ADD COLUMN `cpf` VARCHAR(30) NULL AFTER `name`, 
ADD COLUMN `rg` VARCHAR(20) NULL AFTER `cpf`, 
ADD COLUMN `ctps` VARCHAR(20) NULL AFTER `email`, 
ADD COLUMN `pis` VARCHAR(20) NULL AFTER `ctps`, 
ADD COLUMN `cel` VARCHAR(20) NULL AFTER `phone`, CHANGE `hire_date` `hire_date` DATE NULL COMMENT 'data contratacao', 
ADD COLUMN `contato` VARCHAR(100) NULL AFTER `user_id`, 
ADD COLUMN `phone_contact` VARCHAR(20) NULL AFTER `contato`, 
ADD COLUMN `turno` ENUM('manha','tarde','noite','admin') NULL AFTER `phone_contact`; 