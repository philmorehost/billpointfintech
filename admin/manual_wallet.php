<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: manual_wallet.php');
        exit();
    }
    $email = $_POST['email'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $amount > 0) {
        $user_id = $user['id'];
        try {
            $pdo->beginTransaction();

            $currency = $_POST['currency'];
            $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wallet_stmt->execute([$user_id, $currency]);
            $balance = $wallet_stmt->fetchColumn();

            if ($balance === false) {
                // Wallet doesn't exist, create it
                $create_wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, 0)");
                $create_wallet_stmt->execute([$user_id, $currency]);
                $balance = 0;
            }

            if ($type === 'debit' && $balance < $amount) {
                throw new Exception('Insufficient funds for debit.');
            }

            $new_balance = ($type === 'credit') ? $balance + $amount : $balance - $amount;
            $update_stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = ?");
            $update_stmt->execute([$new_balance, $user_id, $currency]);

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, ?, 'completed', ?)");
            $log_stmt->execute([$user_id, 'manual_' . $type, $amount, $currency, $description]);

            $audit_log = $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_user_id, details) VALUES (?, ?, ?, ?)");
            $audit_log->execute([$_SESSION['admin_id'], 'manual_wallet_' . $type, $user_id, "Amount: $amount, Description: $description"]);

            $pdo->commit();
            set_flash_message('success', 'Wallet updated successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Failed to update wallet: ' . $e->getMessage());
        }
    } else {
        set_flash_message('error', 'Invalid user or amount.');
    }
    header("Location: manual_wallet.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Wallet Adjustment - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Manual Wallet Adjustment</h2>
        <form action="manual_wallet.php" method="post">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="email">User Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="currency">Currency</label>
                <select id="currency" name="currency" required>
                    <option value="NGN">NGN</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="USDT">USDT</option>
                    <option value="USDC">USDC</option>
                </select>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type" required>
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <button type="submit" class="btn">Submit</button>
        </form>
    </div>
</body>
</html>
