<?php
// Simple database update script
// IMPORTANT: Make a backup of your database before running this script!

require_once 'core/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.<br>";

    // 1. Add 'bonus_balance' column to 'users' table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'bonus_balance'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD `bonus_balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `wallet_balance`");
        echo " - Column 'bonus_balance' added to 'users' table.<br>";
    } else {
        echo " - Column 'bonus_balance' already exists in 'users' table.<br>";
    }

    // 2. Create and populate 'banks' table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `banks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `code` VARCHAR(10) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `banks`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `banks` (`name`, `code`) VALUES
            ('Access Bank', '044'), ('Citibank', '023'), ('Diamond Bank', '063'),
            ('Dynamic Standard Bank', ''), ('Ecobank Nigeria', '050'),
            ('Fidelity Bank Nigeria', '070'), ('First Bank of Nigeria', '011'),
            ('First City Monument Bank', '214'), ('Guaranty Trust Bank', '058'),
            ('Heritage Bank Plc', '030'), ('Jaiz Bank', '301'),
            ('Keystone Bank Limited', '082'), ('Providus Bank Plc', '101'),
            ('Polaris Bank', '076'), ('Stanbic IBTC Bank Nigeria Limited', '221'),
            ('Standard Chartered Bank', '068'), ('Sterling Bank', '232'),
            ('Suntrust Bank Nigeria Limited', '100'), ('Union Bank of Nigeria', '032'),
            ('United Bank for Africa', '033'), ('Unity Bank Plc', '215'),
            ('Wema Bank', '035'), ('Zenith Bank', '057');
        ");
        echo " - Table 'banks' created and populated.<br>";
    } else {
        echo " - Table 'banks' already exists and is populated.<br>";
    }

    // 3. Create and populate 'cable_tv_packages' table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cable_tv_packages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `provider` VARCHAR(50) NOT NULL,
            `package_name` VARCHAR(255) NOT NULL,
            `package_code` VARCHAR(50) NOT NULL,
            `price` DECIMAL(10, 2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `cable_tv_packages`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `cable_tv_packages` (`provider`, `package_name`, `package_code`, `price`) VALUES
            ('DSTV', 'DStv Padi', 'DStv-Padi', 2500.00), ('DSTV', 'DStv Yanga', 'DStv-Yanga', 3500.00),
            ('DSTV', 'DStv Confam', 'DStv-Confam', 6200.00), ('DSTV', 'DStv Compact', 'DStv-Compact', 10500.00),
            ('DSTV', 'DStv Compact Plus', 'DStv-Compact-Plus', 16600.00), ('DSTV', 'DStv Premium', 'DStv-Premium', 24500.00),
            ('GOTV', 'GOtv Smallie', 'GOtv-Smallie', 1100.00), ('GOTV', 'GOtv Jinja', 'GOtv-Jinja', 2250.00),
            ('GOTV', 'GOtv Jolli', 'GOtv-Jolli', 3300.00), ('GOTV', 'GOtv Max', 'GOtv-Max', 4850.00),
            ('GOTV', 'GOtv Supa', 'GOtv-Supa', 6400.00), ('STARTIMES', 'Nova - Daily', 'nova-daily', 90.00),
            ('STARTIMES', 'Nova - Weekly', 'nova-weekly', 300.00), ('STARTIMES', 'Nova - Monthly', 'nova-monthly', 900.00),
            ('STARTIMES', 'Basic - Daily', 'basic-daily', 160.00), ('STARTIMES', 'Basic - Weekly', 'basic-weekly', 500.00),
            ('STARTIMES', 'Basic - Monthly', 'basic-monthly', 1700.00), ('STARTIMES', 'Smart - Daily', 'smart-daily', 200.00),
            ('STARTIMES', 'Smart - Weekly', 'smart-weekly', 650.00), ('STARTIMES', 'Smart - Monthly', 'smart-monthly', 2200.00);
        ");
        echo " - Table 'cable_tv_packages' created and populated.<br>";
    } else {
         echo " - Table 'cable_tv_packages' already exists and is populated.<br>";
    }

    // 4. Create and populate 'electricity_discos' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `electricity_discos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `code` VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `electricity_discos`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `electricity_discos` (`name`, `code`) VALUES
            ('Ikeja Electric', 'ikeja-electric'), ('Eko Electric', 'eko-electric'),
            ('Kano Electric', 'kano-electric'), ('Port Harcourt Electric', 'portharcourt-electric'),
            ('Jos Electric', 'jos-electric'), ('Ibadan Electric', 'ibadan-electric'),
            ('Kaduna Electric', 'kaduna-electric'), ('Abuja Electric', 'abuja-electric'),
            ('Enugu Electric', 'enugu-electric'), ('Benin Electric', 'benin-electric'),
            ('Yola Electric', 'yola-electric');
        ");
        echo " - Table 'electricity_discos' created and populated.<br>";
    } else {
        echo " - Table 'electricity_discos' already exists and is populated.<br>";
    }

    // 5. Create and populate 'exam_products' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `exam_products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `code` VARCHAR(50) NOT NULL,
            `price` DECIMAL(10, 2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `exam_products`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `exam_products` (`name`, `code`, `price`) VALUES
            ('WAEC Result Checker PIN', 'WAEC', 3500.00),
            ('NECO Result Token', 'NECO', 1500.00),
            ('NABTEB Result Checker PIN', 'NABTEB', 1000.00);
        ");
        echo " - Table 'exam_products' created and populated.<br>";
    } else {
        echo " - Table 'exam_products' already exists and is populated.<br>";
    }

    // 6. Create 'transaction_limits' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `transaction_limits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT NULL,
            `service` VARCHAR(50) DEFAULT NULL,
            `limit_type` ENUM('daily', 'monthly') NOT NULL,
            `max_amount` DECIMAL(15, 2) NOT NULL,
            UNIQUE KEY `user_service_type` (`user_id`, `service`, `limit_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo " - Table 'transaction_limits' created or already exists.<br>";

    // 7. Create 'blacklist' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `blacklist` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `identifier_type` VARCHAR(50) NOT NULL COMMENT 'e.g., phone, account_number, user_id',
            `identifier_value` VARCHAR(255) NOT NULL,
            `reason` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `type_value` (`identifier_type`, `identifier_value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo " - Table 'blacklist' created or already exists.<br>";

    // 8. Add columns for Beewave integration to 'users' table
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'beewave_va_details'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD `beewave_va_details` TEXT DEFAULT NULL AFTER `bonus_balance`");
        echo " - Column 'beewave_va_details' added to 'users' table.<br>";
    } else {
        echo " - Column 'beewave_va_details' already exists in 'users' table.<br>";
    }

    // 9. Add 'referral_code' column to 'users' table
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'referral_code'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD `referral_code` VARCHAR(50) DEFAULT NULL UNIQUE AFTER `beewave_va_details`");
        echo " - Column 'referral_code' added to 'users' table.<br>";
    } else {
        echo " - Column 'referral_code' already exists in 'users' table.<br>";
    }


    echo "<br><strong>Database update complete! You can now delete this file.</strong>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
