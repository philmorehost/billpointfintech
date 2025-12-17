<?php
$page_title = 'Admin - Bulk VTU Jobs';
require_once '../includes/admin_header.php';

$jobs = $pdo->query("
    SELECT b.*, u.full_name
    FROM bulk_jobs b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
")->fetchAll();

include '../includes/header.php';
?>
<div class="container mt-4">
    <h1>Bulk VTU Job History</h1>
    <p>View the status and history of all bulk processing jobs.</p>

    <div class="content-box">
        <table class="table table-bordered">
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
                    <td><span class="status-<?php echo strtolower($job['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?></span></td>
                    <td><?php echo htmlspecialchars($job['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
