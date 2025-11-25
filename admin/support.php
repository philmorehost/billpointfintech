<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

// Fetch all tickets with user information
$stmt = $pdo->query("
    SELECT t.*, u.full_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.updated_at DESC
");
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Support Tickets</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - Support Tickets</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>All Support Tickets</h2>
        <?php if (empty($tickets)): ?>
            <p>There are no support tickets.</p>
        <?php else: ?>
            <table class="support-table">
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
                            <td><span class="status-<?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></td>
                            <td><a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
