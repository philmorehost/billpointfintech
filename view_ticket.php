<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: support.php');
    exit();
}

// Fetch ticket details, ensuring it belongs to the logged-in user
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash_message('error', 'Ticket not found or you do not have permission to view it.');
    header('Location: support.php');
    exit();
}

// Fetch all messages for this ticket
$msg_stmt = $pdo->prepare("SELECT m.*, u.full_name FROM ticket_messages m JOIN users u ON m.sender_id = u.id WHERE m.ticket_id = ? ORDER BY m.created_at ASC");
$msg_stmt->execute([$ticket_id]);
$messages = $msg_stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Ticket: <?php echo htmlspecialchars($ticket['subject']); ?></h1>
        <p>Status: <span class="status-<?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span></p>
    </div>

    <div class="chat-container">
        <div id="chat-box" class="chat-box">
            <!-- Messages will be loaded here by AJAX -->
            <?php foreach ($messages as $message): ?>
                <div class="chat-message <?php echo $message['is_admin_reply'] ? 'admin' : 'user'; ?>">
                    <p class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <span class="message-meta">
                        <?php echo $message['is_admin_reply'] ? 'Admin' : 'You'; ?> on <?php echo date('M d, H:i', strtotime($message['created_at'])); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-reply">
            <form id="reply-form">
                <input type="hidden" name="action" value="add_reply">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <textarea id="reply-message" name="message" rows="3" placeholder="Type your reply here..." required></textarea>
                </div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const replyForm = document.getElementById('reply-form');
    const replyMessageInput = document.getElementById('reply-message');

    // Auto-scroll to the latest message
    chatBox.scrollTop = chatBox.scrollHeight;

    replyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = replyMessageInput.value.trim();
        if (message === '') return;

        const formData = new FormData(replyForm);

        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Add the new message to the chat box
                const newMessageDiv = document.createElement('div');
                newMessageDiv.classList.add('chat-message', 'user');
                newMessageDiv.innerHTML = `
                    <p class="message-content">${data.message.message.replace(/\\n/g, '<br>')}</p>
                    <span class="message-meta">You on ${data.message.created_at}</span>
                `;
                chatBox.appendChild(newMessageDiv);
                replyMessageInput.value = '';
                chatBox.scrollTop = chatBox.scrollHeight;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>

<?php include 'includes/footer.php'; ?>
