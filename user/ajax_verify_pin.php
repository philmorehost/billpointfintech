<?php
// user/ajax_verify_pin.php
require_once '../core/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

$pin = $_POST['pin'] ?? '';

if (!preg_match('/^\d{4}$/', $pin)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid PIN format.']);
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hashed_pin = $stmt->fetchColumn();

if ($hashed_pin && password_verify($pin, $hashed_pin)) {
    // PIN is correct, update the last activity time to reset the timer
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'The PIN you entered is incorrect.']);
}
exit;
