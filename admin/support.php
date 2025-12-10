<?php
$page_title = 'Admin - Support Tickets';
require_once '../includes/admin_header.php';

// Fetch all tickets with user information
$stmt = $pdo->query("
    SELECT t.*, u.full_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.updated_at DESC
");
$tickets = $stmt->fetchAll();
?>

<div class="admin-header">
    <h1>Support Tickets</h1>
    <p>View and manage all user support tickets.</p>
</div>

<div class="content-box">
    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">There are no support tickets.</div>
    <?php else: ?>
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
                        <td><span class="status-<?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                        <td><?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></td>
                        <td><a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
