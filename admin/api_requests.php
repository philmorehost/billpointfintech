<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

$requests = [];
try {
    // Fetch pending requests along with user details
    $stmt = $pdo->query("
        SELECT r.id, r.reason, r.created_at, u.full_name, u.email
        FROM api_key_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.status = 'pending'
        ORDER BY r.created_at ASC
    ");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash_message('error', 'Could not retrieve API requests. The database table might be missing.');
}

$page_title = 'API Access Requests';
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>API Access Requests</h1>
    <p>Review and approve or deny user requests for API access.</p>

    <?php display_flash_message(); ?>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">There are no pending API access requests.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Reason</th>
                    <th>Requested On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($request['full_name']); ?><br>
                            <small><?php echo htmlspecialchars($request['email']); ?></small>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                        <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                        <td>
                            <form action="admin_handler.php" method="POST" style="display: inline-block;">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="approve_api_request">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="admin_handler.php" method="POST" style="display: inline-block;">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="reject_api_request">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
