USE api_jwt_db;

ALTER TABLE `users` 
ADD COLUMN 
`cpf` VARCHAR(20) NOT NULL AFTER `password`, 
CHANGE `role` `role` ENUM('admin','user','super','rh') 
CHARSET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'user' NULL, 
ADD UNIQUE INDEX `cpf` (`cpf`); 
