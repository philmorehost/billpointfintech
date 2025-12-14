<?php
// admin/ajax_ticket_handler.php
require_once '../core/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$ticket_id = $_POST['ticket_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (empty($ticket_id) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$pdo = db_connect();

try {
    $pdo->beginTransaction();

    // Insert the new admin message
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (?, ?, 'admin', ?)");
    $stmt->execute([$ticket_id, $admin_id, $message]);

    // Update the ticket's status to 'open' (as the admin has replied)
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$ticket_id]);

    $pdo->commit();

    $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_name = $stmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'Your reply has been sent.',
        'sender_name' => $admin_name ?? 'Admin',
        'sent_at' => date('M j, Y H:i')
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
exit;
