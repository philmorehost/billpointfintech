<?php
// core/security_functions.php

function check_transaction_limit($pdo, $user_id, $service_name, $amount) {
    // 1. Get the limit for this service
    $stmt = $pdo->prepare("SELECT daily_limit FROM transaction_limits WHERE service_name = ?");
    $stmt->execute([$service_name]);
    $limit = $stmt->fetchColumn();

    // If limit is 0 or not set, there is no restriction
    if (!$limit || $limit <= 0) {
        return ['allowed' => true];
    }

    // 2. Calculate user's total for this service in the last 24 hours
    $stmt = $pdo->prepare(
        "SELECT SUM(amount) FROM transactions
         WHERE user_id = ? AND service = ? AND status = 'success' AND created_at >= NOW() - INTERVAL 1 DAY"
    );
    $stmt->execute([$user_id, $service_name]);
    $todays_total = $stmt->fetchColumn() ?? 0;

    if (($todays_total + $amount) > $limit) {
        return [
            'allowed' => false,
            'message' => "You have exceeded your daily transaction limit of ₦" . number_format($limit, 2) . " for this service. Your total for today is ₦" . number_format($todays_total, 2) . "."
        ];
    }

    return ['allowed' => true];
}

function is_blacklisted($pdo, $identifier_type, $identifier_value) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blacklist WHERE identifier_type = ? AND identifier_value = ?");
    $stmt->execute([$identifier_type, $identifier_value]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return [
            'blacklisted' => true,
            'message' => "The provided identifier ({$identifier_type}: {$identifier_value}) is restricted from this service."
        ];
    }

    return ['blacklisted' => false];
}
