<?php
$page_title = 'Admin - Bulk VTU Jobs';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

$jobs = $pdo->query("
    SELECT b.*, u.full_name
    FROM bulk_jobs b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - Bulk VTU Jobs</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Job History</h2>
        <table class="support-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Job Type</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?php echo date('M d, Y H:i', strtotime($job['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($job['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                    <td>₦<?php echo number_format($job['total_cost'], 2); ?></td>
                    <td><span class="status-<?php echo strtolower($job['status']); ?>"><?php echo ucfirst($job['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($job['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
