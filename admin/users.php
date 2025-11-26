<?php
$page_title = 'Manage Users';
require_once 'includes/header.php';

$users = $pdo->query("SELECT id, full_name, email, phone FROM users ORDER BY id DESC LIMIT 20")->fetchAll();
?>
<div class="manage-users-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <table style="width: 100%;">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                <td>
                    <form action="impersonate.php" method="POST" style="display: inline;">
                        <?php echo generate_csrf_token_input(); ?>
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="link-button">Impersonate</button>
                    </form>
                     |
                    <a href="manual_wallet.php?user_id=<?php echo $user['id']; ?>">Adjust Wallet</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.link-button {
    background: none;
    border: none;
    color: #6366f1;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
    font-size: inherit;
}
</style>

<?php require_once 'includes/footer.php'; ?>
