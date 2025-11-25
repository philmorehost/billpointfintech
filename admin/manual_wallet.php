<?php
$page_title = 'Admin - Manual Wallet Tool';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';
$csrf_token = generate_csrf_token();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validate_csrf_token()) {
        $action = $_POST['action'];
        $email = trim($_POST['email']);
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'];
        $description = trim($_POST['description']);

        if (!empty($email) && $amount > 0 && !empty($currency) && !empty($description)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user) {
                    throw new Exception("User not found.");
                }
                $user_id = $user['id'];

                if ($action === 'credit_wallet') {
                    $update_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?");
                    $log_type = 'manual_credit';
                } elseif ($action === 'debit_wallet') {
                    // Check for sufficient funds before debiting
                    $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                    $wallet_stmt->execute([$user_id, $currency]);
                    if ($wallet_stmt->fetchColumn() < $amount) {
                        throw new Exception("User has insufficient funds for this debit.");
                    }
                    $update_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = ?");
                    $log_type = 'manual_debit';
                } else {
                    throw new Exception("Invalid action.");
                }

                $update_stmt->execute([$amount, $user_id, $currency]);

                // Log to transactions table
                $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, ?, 'completed', ?)");
                $log_stmt->execute([$user_id, $log_type, $amount, $currency, "Admin action: " . $description]);

                // Log to audit table
                $audit_details = "Action: {$log_type}, Amount: {$amount} {$currency}, Reason: {$description}";
                $audit_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_user_id, details, ip_address) VALUES (?, ?, ?, ?, ?)");
                $audit_stmt->execute([$_SESSION['user_id'], 'manual_wallet_adjustment', $user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);

                $pdo->commit();
                set_flash_message('success', "Wallet {$action} successful.");

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash_message('error', 'Operation failed: ' . $e->getMessage());
            }
        } else {
            set_flash_message('error', 'All fields are required.');
        }
    }
    header('Location: manual_wallet.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - Manual Wallet Adjustment</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Credit or Debit a User's Wallet</h2>
        <p><strong>Warning:</strong> This is a powerful tool. All actions are logged.</p>
        <?php display_flash_message(); ?>
        <form action="manual_wallet.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="email">User's Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="currency">Currency</label>
                <select name="currency" required>
                    <?php foreach ($config['wallet_currencies'] as $currency): ?>
                        <option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Reason / Description</label>
                <input type="text" name="description" required>
            </div>
            <div class="form-group">
                <button type="submit" name="action" value="credit_wallet" class="btn btn-success">Credit User</button>
                <button type="submit" name="action" value="debit_wallet" class="btn btn-danger">Debit User</button>
            </div>
        </form>
    </div>
</body>
</html>
