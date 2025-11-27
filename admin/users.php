<?php
include 'header.php';

$pdo = db_connect();
$stmt = $pdo->query('SELECT id, full_name, email, phone_number, wallet_balance, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>

<h2>Manage Users</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Wallet Balance</th>
            <th>Registered At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                <td><?php echo $user['wallet_balance']; ?></td>
                <td><?php echo $user['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
