<?php
// user/ajax_gamification_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'claim_daily_bonus') {
    $pdo = db_connect();
    $today = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("SELECT last_claimed_date FROM daily_rewards WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $last_claimed = $stmt->fetchColumn();

        if ($last_claimed === $today) {
            throw new Exception("You have already claimed your daily bonus today.");
        }

        // Generate a small random bonus between ₦5 and ₦20
        $bonus_amount = mt_rand(500, 2000) / 100;

        $pdo->beginTransaction();

        credit_wallet($user_id, $bonus_amount);

        // Log the claim
        $stmt = $pdo->prepare("INSERT INTO daily_rewards (user_id, last_claimed_date) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_claimed_date = VALUES(last_claimed_date)");
        $stmt->execute([$user_id, $today]);

        // Log it as a transaction for user's history
        create_transaction($user_id, 'Daily Bonus', 'Daily Login Bonus Claim', $bonus_amount, 'success', 'bonus_'.uniqid());

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Congratulations! You have received a bonus of ₦' . number_format($bonus_amount, 2) . '.',
            'bonus_amount' => $bonus_amount
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
