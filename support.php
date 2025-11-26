<?php
$page_title = 'Support';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<div class="support-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Support Tickets</h2>
        <button class="btn-primary" onclick="document.getElementById('new-ticket-form').style.display='block'">New Ticket</button>
    </div>

    <form id="new-ticket-form" action="support_handler.php" method="POST" style="display:none; margin-bottom: 2rem;">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="create_ticket">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn-primary">Create Ticket</button>
    </form>

    <div class="tickets-list">
        <?php foreach ($tickets as $ticket): ?>
            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" style="display: block; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between;">
                    <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                    <span><?php echo ucfirst($ticket['status']); ?></span>
                </div>
                <small>Last updated: <?php echo date('M d, Y', strtotime($ticket['updated_at'])); ?></small>
            </a>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
            <p>You have no open support tickets.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
