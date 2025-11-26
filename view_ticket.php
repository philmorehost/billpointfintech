<?php
$page_title = 'View Ticket';
require_once 'includes/header.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header("Location: support.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    // Ticket not found or doesn't belong to the user
    header("Location: support.php");
    exit();
}

// Fetch messages
$msg_stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
$msg_stmt->execute([$ticket_id]);
$messages = $msg_stmt->fetchAll();
?>

<div class="view-ticket-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
    <p>Status: <strong><?php echo ucfirst($ticket['status']); ?></strong></p>

    <div class="chat-box" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; height: 400px; overflow-y: scroll; margin-bottom: 1.5rem;">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['is_admin_reply'] ? 'admin-reply' : 'user-message'; ?>" style="margin-bottom: 1rem;">
                <p style="background: <?php echo $message['is_admin_reply'] ? '#f1f5f9' : '#e0e7ff'; ?>; padding: 0.75rem; border-radius: 0.5rem; display: inline-block;">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </p>
                <small style="display: block; color: #6b7280; margin-top: 0.25rem;"><?php echo date('M d, H:i', strtotime($message['created_at'])); ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
    <form action="support_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="reply_ticket">
        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
        <div class="form-group">
            <label for="message">Your Reply</label>
            <textarea id="message" name="message" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn-primary">Send Reply</button>
    </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
