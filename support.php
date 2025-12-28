<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    if (validate_csrf_token()) {
        $subject = htmlspecialchars(trim($_POST['subject']));
        $message = htmlspecialchars(trim($_POST['message']));

        if (!empty($subject) && !empty($message)) {
            try {
                $pdo->beginTransaction();

                // Create the ticket
                $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)");
                $stmt->execute([$user_id, $subject]);
                $ticket_id = $pdo->lastInsertId();

                // Add the initial message
                $msg_stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
                $msg_stmt->execute([$ticket_id, $user_id, $message]);

                $pdo->commit();
                set_flash_message('success', 'Your support ticket has been created.');

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                set_flash_message('error', 'An error occurred. Please try again.');
            }
        } else {
            set_flash_message('error', 'Subject and message cannot be empty.');
        }
    } else {
        set_flash_message('error', 'CSRF validation failed.');
    }
    header('Location: support.php');
    exit();
}

// Fetch user's tickets from the database
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Support Center</h1>
        <p>View your support tickets or create a new one.</p>
    </div>

    <div class="content-box">
        <h2>Your Tickets</h2>
        <?php if (empty($tickets)): ?>
            <p>You have not created any support tickets yet.</p>
        <?php else: ?>
            <table class="support-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td><span class="status-<?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></td>
                            <td><a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="content-box" style="margin-top: 20px;">
        <h2>Create New Ticket</h2>
        <?php display_flash_message(); ?>
        <form action="support.php" method="POST">
            <input type="hidden" name="action" value="create_ticket">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn">Create Ticket</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
