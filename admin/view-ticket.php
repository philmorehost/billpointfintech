<?php
include 'header.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: support-tickets.php');
    exit;
}

$pdo = db_connect();
$admin_id = $_SESSION['admin_id'];

$stmt = $pdo->prepare(
    "SELECT st.*, u.full_name
     FROM support_tickets st
     JOIN users u ON st.user_id = u.id
     WHERE st.id = ?"
);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: support-tickets.php');
    exit;
}

$stmt = $pdo->prepare("SELECT tm.*, a.username as admin_name FROM ticket_messages tm LEFT JOIN admins a ON tm.sender_id = a.id AND tm.sender_type = 'admin' WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container">
    <h2>Ticket: <?php echo htmlspecialchars($ticket['subject']); ?></h2>
    <p><strong>User:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
    <a href="support-tickets.php">&laquo; Back to All Tickets</a>

    <div class="chat-room">
        <div class="message-history" id="message-history">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender_type'] === 'user' ? 'user-message' : 'admin-message'; ?>">
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <span class="timestamp">
                        <?php echo htmlspecialchars($message['sender_type'] === 'admin' ? ($message['admin_name'] ?? 'Admin') : 'User'); ?> -
                        <?php echo htmlspecialchars(date('M j, Y H:i', strtotime($message['created_at']))); ?>
                    </span>
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
.chat-room { margin-top: 20px; border: 1px solid #ddd; border-radius: 5px; }
.message-history { height: 400px; overflow-y: auto; padding: 15px; background: #f9f9f9; }
.message { padding: 10px 15px; border-radius: 15px; margin-bottom: 10px; max-width: 80%; }
.user-message { background-color: #e9e9eb; color: #333; margin-right: auto; border-bottom-left-radius: 0; }
.admin-message { background-color: #007bff; color: white; margin-left: auto; border-bottom-right-radius: 0; }
.timestamp { font-size: 0.75rem; color: #aaa; display: block; margin-top: 5px; }
.admin-message .timestamp { color: #eee; }
#reply-form { display: flex; padding: 10px; }
#reply-form textarea { flex-grow: 1; border-radius: 5px; padding: 10px; border: 1px solid #ccc; }
#reply-form button { margin-left: 10px; }
</style>

<?php include 'footer.php'; ?>

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
                newMessage.classList.add('message', 'admin-message');
                newMessage.innerHTML = `<p>${messageText.replace(/\n/g, '<br>')}</p><span class="timestamp">${data.sender_name} - ${data.sent_at}</span>`;

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
