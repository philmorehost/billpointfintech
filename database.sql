-- Billpoint SQL Schema

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `bvn` varchar(11) DEFAULT NULL,
  `nin` varchar(11) DEFAULT NULL,
  `kyc_level` int(11) NOT NULL DEFAULT '0',
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `virtual_account_ref` varchar(255) DEFAULT NULL,
  `virtual_bank_name` varchar(255) DEFAULT NULL,
  `virtual_account_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallets Table
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `balance` decimal(20,4) NOT NULL DEFAULT '0.0000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exam Products Table
CREATE TABLE `exam_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(255) NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `exam_products` (`exam_name`, `product_code`, `price`) VALUES
('WAEC Result Checker', 'waec', 3800),
('NECO Result Checker', 'neco', 1400),
('NABTEB Result Checker', 'nabteb', 950);

-- Transactions Table
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL,
  `description` text,
  `reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Tickets Table
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','closed','user_reply') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket Messages Table
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin_reply` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- P2P Transfers Table
CREATE TABLE `p2p_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction Limits Table (Risk Management)
CREATE TABLE `transaction_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_id` varchar(255) NOT NULL,
  `max_count` int(11) NOT NULL,
  `time_frame_seconds` int(11) NOT NULL,
  `is_whitelisted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Log Table
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Savings Goals Table
CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `target_amount` decimal(20,4) NOT NULL,
  `current_amount` decimal(20,4) NOT NULL DEFAULT '0.0000',
  `saving_frequency` enum('daily','weekly') NOT NULL,
  `saving_amount` decimal(20,4) NOT NULL,
  `next_deduction_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','paused') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Savings Transactions Table
CREATE TABLE `savings_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `savings_goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`savings_goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estimates & Invoices Tables
CREATE TABLE `estimates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `total_amount` decimal(20,4) NOT NULL,
  `status` enum('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `estimate_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estimate_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(20,4) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`estimate_id`) REFERENCES `estimates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `estimate_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `total_amount` decimal(20,4) NOT NULL,
  `status` enum('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(20,4) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Populate Services Table
INSERT INTO `services` (`name`, `url`, `icon`, `is_active`) VALUES
('Airtime', 'airtime.php', NULL, 1),
('Data', 'data.php', NULL, 1),
('Cable TV', 'cable.php', NULL, 1),
('Electricity', 'electricity.php', NULL, 1),
('P2P Transfer', 'p2p_transfer.php', NULL, 1),
('Savings', 'savings.php', NULL, 1),
('Exam PINs', 'exam.php', NULL, 1),
('Recharge Card', 'recharge_card.php', NULL, 0),
('Bulk SMS', 'bulk_sms.php', NULL, 0);

-- Gateway Fees Table
CREATE TABLE `gateway_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gateway_name` varchar(255) NOT NULL,
  `fee_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `gateway_name` (`gateway_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FX Rates Table
CREATE TABLE `fx_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_pair` varchar(20) NOT NULL,
  `rate` decimal(20,8) NOT NULL,
  `admin_markup` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_pair` (`currency_pair`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Providers Table
CREATE TABLE `api_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `api_providers` (`name`, `is_active`) VALUES
('Datagifting', 1),
('Clubconnect', 0),
('Reloadly', 0);

-- Airtime Pricing Table
CREATE TABLE `airtime_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `network` varchar(255) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `smart_earner_discount` decimal(5,2) NOT NULL DEFAULT '1.00',
  `agent_vendor_discount` decimal(5,2) NOT NULL DEFAULT '1.00',
  `api_vendor_discount` decimal(5,2) NOT NULL DEFAULT '1.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`provider_id`) REFERENCES `api_providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `airtime_pricing` (`network`, `provider_id`, `smart_earner_discount`, `agent_vendor_discount`, `api_vendor_discount`, `is_active`) VALUES
('mtn', 1, 1.00, 1.00, 1.00, 1),
('glo', 1, 1.00, 1.00, 1.00, 1),
('airtel', 1, 1.00, 1.00, 1.00, 1),
('9mobile', 1, 1.00, 1.00, 1.00, 1);

-- Data Pricing Table
CREATE TABLE `data_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `network` varchar(50) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `data_type` varchar(50) NOT NULL,
  `plan_id_from_provider` varchar(100) NOT NULL,
  `provider_price` decimal(10,2) NOT NULL,
  `price_smart_earner` decimal(10,2) NOT NULL,
  `price_agent` decimal(10,2) NOT NULL,
  `price_api` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`provider_id`) REFERENCES `api_providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `data_pricing` (`provider_id`, `network`, `plan_name`, `data_type`, `plan_id_from_provider`, `provider_price`, `price_smart_earner`, `price_agent`, `price_api`) VALUES
(1, 'mtn', '1GB SME', 'sme-data', '1gb', 590.00, 600.00, 590.00, 590.00),
(1, 'mtn', '2GB SME', 'sme-data', '2gb', 1180.00, 1200.00, 1180.00, 1180.00),
(1, 'mtn', '500MB CG', 'cg-data', '500mb', 345.00, 348.00, 345.00, 345.00),
(1, 'glo', '500MB CG', 'cg-data', '500mb', 220.00, 250.00, 220.00, 220.00),
(1, 'glo', '1GB CG', 'cg-data', '1gb', 420.00, 450.00, 420.00, 420.00),
(1, 'airtel', '1GB Awoof', 'dd-data', '1gb_awoof_2days', 495.00, 500.00, 495.00, 495.00),
(1, '9mobile', '1GB CG', 'cg-data', '1gb', 370.00, 380.00, 370.00, 370.00);

-- Cable Pricing Table
CREATE TABLE `cable_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `cable_type` varchar(50) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_code_from_provider` varchar(100) NOT NULL,
  `provider_price` decimal(10,2) NOT NULL,
  `price_smart_earner` decimal(10,2) NOT NULL,
  `price_agent` decimal(10,2) NOT NULL,
  `price_api` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`provider_id`) REFERENCES `api_providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cable_pricing` (`provider_id`, `cable_type`, `package_name`, `package_code_from_provider`, `provider_price`, `price_smart_earner`, `price_agent`, `price_api`) VALUES
(1, 'dstv', 'Padi', 'padi', 4378.00, 4400.00, 4378.00, 4378.00),
(1, 'dstv', 'Yanga', 'yanga', 5970.00, 6000.00, 5970.00, 5970.00),
(1, 'gotv', 'Smallie', 'smallie', 1890.05, 1900.00, 1890.05, 1890.05),
(1, 'gotv', 'Jinja', 'jinja', 3880.05, 3900.00, 3880.05, 3880.05),
(1, 'startimes', 'Nova Weekly', 'nova_weekly', 594.00, 600.00, 594.00, 594.00),
(1, 'startimes', 'Basic Weekly', 'basic_weekly', 1237.05, 1250.00, 1237.05, 1237.05);

-- Electricity Pricing Table
CREATE TABLE `electricity_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `provider_name_display` varchar(100) NOT NULL,
  `provider_code` varchar(50) NOT NULL,
  `discount_smart_earner` decimal(5,2) NOT NULL DEFAULT '0.03',
  `discount_agent` decimal(5,2) NOT NULL DEFAULT '0.05',
  `discount_api` decimal(5,2) NOT NULL DEFAULT '0.05',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_code` (`provider_code`),
  FOREIGN KEY (`provider_id`) REFERENCES `api_providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `electricity_pricing` (`provider_id`, `provider_name_display`, `provider_code`, `discount_smart_earner`, `discount_agent`, `discount_api`) VALUES
(1, 'Eko Electric', 'ekedc', 0.03, 0.05, 0.05),
(1, 'Ikeja Electric', 'ikedc', 0.03, 0.05, 0.05),
(1, 'Abuja Electric', 'aedc', 0.03, 0.05, 0.05),
(1, 'Kano Electric', 'kedco', 0.03, 0.05, 0.05),
(1, 'Port Harcourt Electric', 'phed', 0.03, 0.05, 0.05),
(1, 'Jos Electric', 'jedc', 0.03, 0.05, 0.05),
(1, 'Ibadan Electric', 'ibedc', 0.03, 0.05, 0.05),
(1, 'Enugu Electric', 'eedc', 0.03, 0.05, 0.05),
(1, 'Yola Electric', 'yedc', 0.03, 0.05, 0.05);
