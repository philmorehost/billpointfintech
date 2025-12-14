<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $reason = trim($_POST['reason'] ?? '');

    if (empty($user_id) || empty($adjustment_type) || $amount <= 0 || empty($reason)) {
        $feedback = ['message' => 'All fields are required, and amount must be positive.', 'type' => 'errors'];
    } else {
        try {
            $pdo->beginTransaction();

            if ($adjustment_type === 'credit') {
                credit_wallet($user_id, $amount);
                $description = "Manual credit by admin: $reason";
            } else { // debit
                debit_wallet($user_id, $amount);
                $description = "Manual debit by admin: $reason";
            }

            // Log this adjustment as a transaction
            create_transaction($user_id, 'Manual Adjustment', $description, $amount, 'success', 'manual_adj_'.uniqid());

            $pdo->commit();
            $feedback = ['message' => 'Wallet adjustment was successful.', 'type' => 'success'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    }
}


$stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE status = 'active' ORDER BY full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manual Wallet Adjustment</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Adjust a User's Wallet Balance</h3>
    <p class="notice"><strong>Warning:</strong> This is a powerful tool. All adjustments are logged. Use with caution.</p>
    <form method="post">
        <div class="form-group">
            <label for="user_id">Select User</label>
            <select name="user_id" id="user_id" class="form-control" required>
                <option value="">-- Select a User --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>">
                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="adjustment_type">Adjustment Type</label>
            <select name="adjustment_type" id="adjustment_type" class="form-control" required>
                <option value="credit">Credit (Add Funds)</option>
                <option value="debit">Debit (Remove Funds)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Amount (₦)</label>
            <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="reason">Reason for Adjustment</label>
            <textarea name="reason" id="reason" class="form-control" required placeholder="e.g., Bonus payout, refund for failed transaction #123"></textarea>
        </div>
        <button type="submit" class="btn btn-warning" onsubmit="return confirm('Are you sure you want to perform this manual wallet adjustment?');">Perform Adjustment</button>
    </form>
</div>

<?php include 'footer.php'; ?>
