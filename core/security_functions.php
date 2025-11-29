<?php
// core/security_functions.php

function check_transaction_limit($pdo, $user_id, $service_name, $amount) {
    // ... (existing function from before)
}

function is_blacklisted($pdo, $identifier_type, $identifier_value) {
    // ... (existing function from before)
}

function check_recipient_limit($pdo, $service, $recipient, $amount) {
    // 1. Check if the recipient is whitelisted for this service (or all services)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM whitelist WHERE recipient = ? AND (service = ? OR service = '*')");
    $stmt->execute([$recipient, $service]);
    if ($stmt->fetchColumn() > 0) {
        return ['allowed' => true]; // Whitelisted, bypass all checks
    }

    // 2. Get the global daily limit for this service per number
    $setting_key = "limit_{$service}_number";
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$setting_key]);
    $limit = $stmt->fetchColumn();

    if (!$limit || $limit <= 0) {
        return ['allowed' => true]; // No limit is set
    }

    // 3. Check and update the recipient's daily total
    $today = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT daily_total_amount FROM number_limits
         WHERE service = ? AND recipient = ? AND last_transaction_date = ?"
    );
    $stmt->execute([$service, $recipient, $today]);
    $todays_total = $stmt->fetchColumn() ?? 0;

    if (($todays_total + $amount) > $limit) {
        return [
            'allowed' => false,
            'message' => "The daily transaction limit of ₦" . number_format($limit, 2) . " has been reached for this recipient."
        ];
    }

    // If the check passes, we can proceed. The total will be updated after a successful transaction.
    return ['allowed' => true];
}

function update_recipient_total($pdo, $service, $recipient, $amount) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare(
        "INSERT INTO number_limits (service, recipient, daily_total_amount, last_transaction_date)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
         daily_total_amount = IF(last_transaction_date = VALUES(last_transaction_date), daily_total_amount + VALUES(daily_total_amount), VALUES(daily_total_amount)),
         last_transaction_date = VALUES(last_transaction_date)"
    );
    $stmt->execute([$service, $recipient, $amount, $today]);
}


/**
 * Checks if the user needs to re-enter their security PIN.
 *
 * @param PDO $pdo The database connection object.
 * @param int $user_id The ID of the current user.
 * @return bool True if PIN is required, false otherwise.
 */
function is_pin_required($pdo, $user_id) {
    // 1. Check if the user has a PIN set.
    $stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $pin_exists = $stmt->fetchColumn();

    if (!$pin_exists) {
        return false; // No PIN is set, so it can't be required.
    }

    // 2. Check the last activity time against a timeout.
    $timeout = 300; // 5 minutes
    $last_activity = $_SESSION['last_pin_verification'] ?? 0;

    if ((time() - $last_activity) > $timeout) {
        return true; // Timeout exceeded, PIN is required.
    }

    return false; // Within timeout period, PIN is not required.
}
