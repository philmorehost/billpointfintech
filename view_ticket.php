<?php
$page_title = 'View Ticket';
require_once 'includes/header.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header("Location: support.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initial fetch of the ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND (user_id = ? OR EXISTS (SELECT 1 FROM admins WHERE id = ?))");
$stmt->execute([$ticket_id, $user_id, $_SESSION['admin_id'] ?? 0]);
$ticket = $stmt->fetch();

if (!$ticket) {
    // Ticket not found or doesn't belong to the user/admin
    header("Location: " . (is_admin() ? "admin/support.php" : "support.php"));
    exit();
}

// Initial fetch of messages
$msg_stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
$msg_stmt->execute([$ticket_id]);
$messages = $msg_stmt->fetchAll();

$chat_box_bg = is_admin() ? '#e0e7ff' : '#fff'; // Different background for admin view
?>

<div class="view-ticket-container" style="background: <?php echo $chat_box_bg; ?>; padding: 2rem; border-radius: 1rem;">
    <h2 class="d-flex justify-content-between align-items-center">
        <span><?php echo htmlspecialchars($ticket['subject']); ?></span>
        <span class="badge badge-info"><?php echo ucfirst($ticket['status']); ?></span>
    </h2>

    <div id="chat-box" class="chat-box" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; height: 400px; overflow-y: scroll; margin-bottom: 1.5rem; background: #f8fafc;">
        <!-- Messages will be loaded here by AJAX -->
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
    <form id="reply-form">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="reply_ticket_ajax">
        <input type="hidden" name="ticket_id" id="ticket_id" value="<?php echo $ticket_id; ?>">
        <div class="form-group">
            <textarea id="message-input" name="message" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Reply</button>
    </form>
    <?php else: ?>
    <p class="text-muted">This ticket is closed. You can no longer send replies.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const replyForm = document.getElementById('reply-form');
    const messageInput = document.getElementById('message-input');
    const ticketId = document.getElementById('ticket_id').value;

    function renderMessages(messages) {
        chatBox.innerHTML = ''; // Clear existing messages
        messages.forEach(message => {
            const isAdminReply = message.is_admin_reply == 1;
            const messageAlignClass = isAdminReply ? 'admin-reply' : 'user-message';
            const messageBgColor = isAdminReply ? '#dbeafe' : '#e5e7eb'; // Admin: blue-100, User: gray-200
            const textAlign = isAdminReply ? 'left' : 'right';


            const messageElement = `
                <div class="message ${messageAlignClass}" style="margin-bottom: 1rem; text-align: ${textAlign};">
                    <p style="background: ${messageBgColor}; padding: 0.75rem; border-radius: 0.5rem; display: inline-block; max-width: 80%; text-align: left;">
                        ${message.message.replace(/\n/g, '<br>')}
                    </p>
                    <small style="display: block; color: #6b7280; margin-top: 0.25rem;">
                        ${new Date(message.created_at).toLocaleString()}
                    </small>
                </div>
            `;
            chatBox.innerHTML += messageElement;
        });
        // Scroll to the bottom
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    async function fetchMessages() {
        try {
            const response = await fetch(`ajax_handler.php?action=get_ticket_messages&ticket_id=${ticketId}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            if (data.status === 'success') {
                renderMessages(data.messages);
            } else {
                console.error('Error fetching messages:', data.message);
            }
        } catch (error) {
            console.error('Fetch error:', error);
        }
    }

    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(replyForm);
            const submitButton = replyForm.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            try {
                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.status === 'success') {
                    messageInput.value = ''; // Clear input
                    fetchMessages(); // Refresh messages
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Submit error:', error);
                alert('An unexpected error occurred. Please try again.');
            } finally {
                 submitButton.disabled = false;
                 submitButton.textContent = 'Send Reply';
            }
        });
    }

    // Initial load and periodic refresh
    fetchMessages();
    setInterval(fetchMessages, 5000); // Refresh every 5 seconds
});
</script>

<?php require_once 'includes/footer.php'; ?>
