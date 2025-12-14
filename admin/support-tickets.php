<?php
include 'header.php';

$pdo = db_connect();

// Fetch all support tickets, joining with user's name
$stmt = $pdo->query(
    "SELECT st.*, u.full_name
     FROM support_tickets st
     JOIN users u ON st.user_id = u.id
     ORDER BY st.updated_at DESC"
);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Support Tickets</h2>

<div class="widget">
    <h3>All Tickets</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($ticket['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['status']))); ?></span></td>
                    <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($ticket['updated_at']))); ?></td>
                    <td>
                        <a href="view-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">View & Reply</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<style>.badge-open, .badge-awaiting_reply { background-color: #ffc107; } .badge-closed { background-color: #6c757d; color: white; }</style>

<?php include 'footer.php'; ?>
