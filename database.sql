-- Billpoint SQL Schema

-- Users Table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pin` varchar(255) NOT NULL, -- This will be a hashed value
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
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

-- Data Plans Table
CREATE TABLE `data_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `network` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cable Plans Table
CREATE TABLE `cable_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cable_provider` varchar(255) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_code` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Electricity Discos Table
CREATE TABLE `electricity_discos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(255) NOT NULL,
  `provider_code` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `electricity_discos` (`provider_name`, `provider_code`) VALUES
('Eko Electric', 'ekedc'),
('Ikeja Electric', 'ikedc'),
('Abuja Electric', 'aedc'),
('Kano Electric', 'kedco'),
('Port Harcourt Electric', 'phed'),
('Jos Electric', 'jedc'),
('Ibadan Electric', 'ibedc'),
('Kaduna Electric', 'kaedco'),
('Enugu Electric', 'eedc'),
('Yola Electric', 'yedc');

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
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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

-- Loans System Tables
CREATE TABLE `loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount_requested` decimal(20,4) NOT NULL,
  `amount_approved` decimal(20,4) DEFAULT NULL,
  `amount_repaid` decimal(20,4) NOT NULL DEFAULT '0.0000',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT '5.00', -- Example: 5%
  `status` enum('pending','active','repaid','rejected','defaulted') NOT NULL DEFAULT 'pending',
  `repayment_due_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `status` enum('successful','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
