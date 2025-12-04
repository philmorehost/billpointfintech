-- Billpoint Seed Data
-- This file populates the database with essential data for the application to function.

--
-- Data for table `banks`
--
CREATE TABLE IF NOT EXISTS `banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `banks` (`name`, `code`) VALUES
('Access Bank', '044'), ('Citibank', '023'), ('Diamond Bank', '063'),
('Ecobank Nigeria', '050'), ('Fidelity Bank Nigeria', '070'), ('First Bank of Nigeria', '011'),
('First City Monument Bank', '214'), ('Guaranty Trust Bank', '058'), ('Heritage Bank Plc', '030'),
('Jaiz Bank', '301'), ('Keystone Bank Limited', '082'), ('Providus Bank Plc', '101'),
('Polaris Bank', '076'), ('Stanbic IBTC Bank', '221'), ('Standard Chartered Bank', '068'),
('Sterling Bank', '232'), ('Suntrust Bank', '100'), ('Union Bank of Nigeria', '032'),
('United Bank for Africa', '033'), ('Unity Bank Plc', '215'), ('Wema Bank', '035'), ('Zenith Bank', '057');

--
-- Data for table `cable_tv_packages`
--
CREATE TABLE IF NOT EXISTS `cable_tv_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_code` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cable_tv_packages` (`provider`, `package_name`, `package_code`, `price`) VALUES
('DSTV', 'DStv Padi', 'DStv-Padi', 2500.00), ('DSTV', 'DStv Yanga', 'DStv-Yanga', 3500.00),
('DSTV', 'DStv Confam', 'DStv-Confam', 6200.00), ('DSTV', 'DStv Compact', 'DStv-Compact', 10500.00),
('GOTV', 'GOtv Smallie', 'GOtv-Smallie', 1100.00), ('GOTV', 'GOtv Jinja', 'GOtv-Jinja', 2250.00);

--
-- Data for table `electricity_discos`
--
CREATE TABLE IF NOT EXISTS `electricity_discos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `electricity_discos` (`name`, `code`) VALUES
('Ikeja Electric', 'ikeja-electric'), ('Eko Electric', 'eko-electric'),
('Kano Electric', 'kano-electric'), ('Port Harcourt Electric', 'portharcourt-electric'),
('Jos Electric', 'jos-electric'), ('Ibadan Electric', 'ibadan-electric');

--
-- Data for table `exam_products`
--
CREATE TABLE IF NOT EXISTS `exam_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `exam_products` (`name`, `code`, `price`) VALUES
('WAEC Result Checker PIN', 'WAEC', 3500.00),
('NECO Result Token', 'NECO', 1500.00);

--
-- Data for table `data_plans`
--
CREATE TABLE IF NOT EXISTS `data_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `network` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `data_plans` (`network`, `type`, `quantity`, `price`) VALUES
('MTN', 'SME', '500MB', 150.00), ('MTN', 'SME', '1GB', 280.00),
('GLO', 'CG', '1GB', 300.00), ('AIRTEL', 'CG', '1GB', 310.00),
('9MOBILE', 'GIFTING', '1GB', 350.00);

--
-- Security Tables
--
CREATE TABLE IF NOT EXISTS `transaction_limits` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT DEFAULT NULL, `service` VARCHAR(50) DEFAULT NULL, `limit_type` ENUM('daily', 'monthly') NOT NULL, `max_amount` DECIMAL(15, 2) NOT NULL, UNIQUE KEY `user_service_type` (`user_id`, `service`, `limit_type`) ) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS `blacklist` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `identifier_type` VARCHAR(50) NOT NULL, `identifier_value` VARCHAR(255) NOT NULL, `reason` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY `type_value` (`identifier_type`, `identifier_value`) ) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS `number_limits` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `service` VARCHAR(50) NOT NULL, `recipient` VARCHAR(255) NOT NULL, `daily_total_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, `last_transaction_date` DATE NOT NULL, UNIQUE KEY `service_recipient` (`service`, `recipient`) ) ENGINE=InnoDB;

--
-- Add new columns to users table
--
ALTER TABLE `users` ADD `bonus_balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `wallet_balance`;
ALTER TABLE `users` ADD `beewave_va_details` TEXT DEFAULT NULL AFTER `bonus_balance`;
ALTER TABLE `users` ADD `referral_code` VARCHAR(50) DEFAULT NULL UNIQUE AFTER `beewave_va_details`;
ALTER TABLE `users` ADD `security_pin` VARCHAR(255) DEFAULT NULL AFTER `password`;
