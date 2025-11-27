<?php
// user/ajax_ticket_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$ticket_id = $_POST['ticket_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (empty($ticket_id) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$pdo = db_connect();

try {
    // First, verify the user owns the ticket they are replying to
    $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Authorization failed.");
    }

    $pdo->beginTransaction();

    // Insert the new message
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$ticket_id, $user_id, $message]);

    // Update the ticket's status to 'awaiting_reply' (from the admin)
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'awaiting_reply', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$ticket_id]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Your reply has been sent.',
        'sent_at' => date('M j, Y H:i')
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
exit;
