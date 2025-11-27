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
            $feedback = ['message' => 'Your support ticket has been created successfully.', 'type' => 'success'];
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

<div class="container">
    <h2>Support Center</h2>

    <?php if ($feedback['message']): ?>
        <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
    <?php endif; ?>

    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Create New Ticket</h3>
            <form method="post">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create Ticket</button>
            </form>
        </div>
        <div class="widget">
            <h3>Your Tickets</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Status</th><th>Last Updated</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($ticket['status']); ?>"><?php echo htmlspecialchars(ucfirst($ticket['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($ticket['updated_at']))); ?></td>
                            <td><a href="view-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<style>.badge-open, .badge-awaiting_reply { background-color: #ffc107; } .badge-closed { background-color: #6c757d; color: white; }</style>
