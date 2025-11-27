<?php
include 'header.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: users.php');
    exit;
}

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $wallet_balance = $_POST['wallet_balance'] ?? '';

    // Basic validation
    if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = ['message' => 'Please fill in all required fields with valid data.', 'type' => 'errors'];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, wallet_balance = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone_number, $wallet_balance, $user_id]);
            $feedback = ['message' => 'User details have been updated successfully.', 'type' => 'success'];
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    }
}

// Fetch user data to populate the form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit;
}
?>

<h2>Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    </div>
    <div class="form-group">
        <label for="phone_number">Phone Number</label>
        <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
    </div>
    <div class="form-group">
        <label for="wallet_balance">Wallet Balance (₦)</label>
        <input type="number" name="wallet_balance" id="wallet_balance" value="<?php echo htmlspecialchars($user['wallet_balance']); ?>" step="0.01" required>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="users.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include 'footer.php'; ?>
