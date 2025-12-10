<?php
$page_title = 'Admin - User Management';
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

$users = $pdo->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetchAll();

require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1>User Management</h1>
    <p>View and manage all registered users.</p>
</div>

<div class="content-box">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <form action="impersonate.php" method="POST">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="user_id_to_impersonate" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Impersonate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require_once '../includes/admin_footer.php'; ?>
