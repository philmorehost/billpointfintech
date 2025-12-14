<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: support.php');
    exit;
}

$pdo = db_connect();
$user_id = $_SESSION['user_id'];

// Verify the user owns this ticket
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: support.php');
    exit;
}

// Fetch all messages for this ticket
$stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <h2>Ticket: <?php echo htmlspecialchars($ticket['subject']); ?></h2>
    <a href="support.php">&laquo; Back to All Tickets</a>

    <div class="chat-room">
        <div class="message-history" id="message-history">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender_type'] === 'user' ? 'user-message' : 'admin-message'; ?>">
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <span class="timestamp"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($message['created_at']))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <form id="reply-form">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
            <textarea name="message" id="reply-message" placeholder="Type your reply..." required></textarea>
            <button type="submit" id="submit-btn">Send Reply</button>
        </form>
    </div>
</div>

<style>
/* ... (same styles as before) ... */
</style>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageHistory = document.getElementById('message-history');
    const replyForm = document.getElementById('reply-form');
    const messageInput = document.getElementById('reply-message');
    const submitBtn = document.getElementById('submit-btn');

    messageHistory.scrollTop = messageHistory.scrollHeight;

    replyForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(replyForm);
        const messageText = messageInput.value;

        if (messageText.trim() === '') return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        try {
            const response = await fetch('ajax_ticket_handler.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                const newMessage = document.createElement('div');
                newMessage.classList.add('message', 'user-message');
                newMessage.innerHTML = `<p>${messageText.replace(/\n/g, '<br>')}</p><span class="timestamp">${data.sent_at}</span>`;

                messageHistory.appendChild(newMessage);
                messageHistory.scrollTop = messageHistory.scrollHeight;
                messageInput.value = '';
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('An unexpected error occurred. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Reply';
        }
    });
});
</script>
