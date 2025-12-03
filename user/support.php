<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $feedback = ['message' => 'Subject and message are required to create a ticket.', 'type' => 'errors'];
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject) VALUES (?, ?)");
            $stmt->execute([$user_id, $subject]);
            $ticket_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (?, ?, 'user', ?)");
            $stmt->execute([$ticket_id, $user_id, $message]);

            $pdo->commit();
            header("Location: view_ticket.php?id=" . $ticket_id); // Redirect to the new ticket
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $feedback = ['message' => 'An error occurred while creating your ticket.', 'type' => 'errors'];
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<style>
.support-container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
.form-card { background-color: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.form-card h3 { margin-top: 0; font-size: 22px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus { border-color: #4f46e5; outline: none; }
.btn-submit { background-color: #4f46e5; color: #fff; padding: 12px 25px; border-radius: 8px; font-size: 16px; }

.tickets-list-card { background-color: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.ticket-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
.ticket-item:last-child { border-bottom: none; }
.ticket-subject { font-weight: 600; color: #333; }
.ticket-date { font-size: 14px; color: #777; }
.badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; color: #fff; }
.badge.open, .badge.awaiting_reply { background-color: #ffc107; }
.badge.closed { background-color: #6c757d; }
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Support Center</span>
    </div>

    <div class="support-container">
        <?php if ($feedback['message']): ?>
            <div class="<?php echo htmlspecialchars($feedback['type']); ?> mb-3"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
        <?php endif; ?>

        <div class="form-card">
            <h3>Create New Ticket</h3>
            <form method="post">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" rows="6" required></textarea>
                </div>
                <button type="submit" class="btn btn-submit">Create Ticket</button>
            </form>
        </div>

        <div class="tickets-list-card">
             <h3>Your Tickets</h3>
             <?php if (empty($tickets)): ?>
                <p>You have not created any support tickets yet.</p>
             <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" style="text-decoration: none; color: inherit;">
                    <div class="ticket-item">
                        <div>
                            <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                            <div class="ticket-date">Last updated: <?php echo htmlspecialchars(date('M j, Y H:i', strtotime($ticket['updated_at']))); ?></div>
                        </div>
                        <span class="badge <?php echo htmlspecialchars($ticket['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['status']))); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
             <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
