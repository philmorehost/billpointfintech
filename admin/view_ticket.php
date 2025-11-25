<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: support.php');
    exit();
}

// Fetch ticket details
$stmt = $pdo->prepare("SELECT t.*, u.full_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash_message('error', 'Ticket not found.');
    header('Location: support.php');
    exit();
}

// Handle ticket status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (validate_csrf_token()) {
        $new_status = $_POST['status'];
        if (in_array($new_status, ['open', 'closed'])) {
            $update_stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $ticket_id]);
            set_flash_message('success', 'Ticket status has been updated.');
            header("Location: view_ticket.php?id=$ticket_id");
            exit();
        }
    }
}


// Fetch all messages for this ticket
$msg_stmt = $pdo->prepare("SELECT m.*, u.full_name FROM ticket_messages m JOIN users u ON m.sender_id = u.id WHERE m.ticket_id = ? ORDER BY m.created_at ASC");
$msg_stmt->execute([$ticket_id]);
$messages = $msg_stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Ticket</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Ticket: <?php echo htmlspecialchars($ticket['subject']); ?></h1>
            <p>User: <?php echo htmlspecialchars($ticket['full_name']); ?> | Status: <span class="status-<?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span></p>
        </div>

        <div class="chat-container">
            <div id="chat-box" class="chat-box">
                <?php foreach ($messages as $message): ?>
                    <div class="chat-message <?php echo $message['is_admin_reply'] ? 'admin' : 'user'; ?>">
                        <p class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <span class="message-meta">
                            <?php echo $message['is_admin_reply'] ? 'You (Admin)' : htmlspecialchars($ticket['full_name']); ?> on <?php echo date('M d, H:i', strtotime($message['created_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="chat-reply">
                <form id="reply-form">
                    <input type="hidden" name="action" value="add_admin_reply">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <textarea id="reply-message" name="message" rows="3" placeholder="Type your reply..." required></textarea>
                    </div>
                    <button type="submit" class="btn">Send Reply</button>
                </form>
            </div>
        </div>

        <div class="content-box" style="margin-top: 20px;">
            <h2>Ticket Actions</h2>
             <?php display_flash_message(); ?>
            <form action="view_ticket.php?id=<?php echo $ticket_id; ?>" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="status">Change Status</label>
                    <select name="status" id="status">
                        <option value="open" <?php if ($ticket['status'] === 'open') echo 'selected'; ?>>Open</option>
                        <option value="closed" <?php if ($ticket['status'] === 'closed') echo 'selected'; ?>>Closed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Update Status</button>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const replyForm = document.getElementById('reply-form');
    const replyMessageInput = document.getElementById('reply-message');

    chatBox.scrollTop = chatBox.scrollHeight;

    replyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = replyMessageInput.value.trim();
        if (message === '') return;

        const formData = new FormData(replyForm);

        fetch('../ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const newMessageDiv = document.createElement('div');
                newMessageDiv.classList.add('chat-message', 'admin');
                newMessageDiv.innerHTML = `
                    <p class="message-content">${data.message.message.replace(/\\n/g, '<br>')}</p>
                    <span class="message-meta">You (Admin) on ${data.message.created_at}</span>
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
</body>
</html>
