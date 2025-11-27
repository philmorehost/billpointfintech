CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `security_pin` varchar(255) DEFAULT NULL, -- Hashed PIN
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paystack_customer_code` varchar(100) DEFAULT NULL,
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_token_expiry` datetime DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `remember_token` (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ... (rest of the database schema remains the same)
