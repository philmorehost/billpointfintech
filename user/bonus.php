<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'bonus_conversion_rate'");
$conversion_rate = $stmt->fetchColumn() ?: 100; // Default to 100 points = 1 NGN

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'convert') {
    $stmt = $pdo->prepare("SELECT bonus_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $bonus_balance = $stmt->fetchColumn();

    if ($bonus_balance >= $conversion_rate) {
        $amount_to_convert = floor($bonus_balance / $conversion_rate) * $conversion_rate;
        $wallet_credit = $amount_to_convert / $conversion_rate;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET bonus_balance = bonus_balance - ? WHERE id = ?");
            $stmt->execute([$amount_to_convert, $user_id]);

            credit_wallet($user_id, $wallet_credit);

            $stmt = $pdo->prepare("INSERT INTO bonus_transactions (user_id, type, amount, description) VALUES (?, 'convert', ?, ?)");
            $stmt->execute([$user_id, $amount_to_convert, "Converted {$amount_to_convert} points to ₦{$wallet_credit}"]);

            $pdo->commit();
            $feedback = ['message' => "Successfully converted {$amount_to_convert} bonus points to ₦{$wallet_credit} in your main wallet.", 'type' => 'success'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $feedback = ['message' => 'An error occurred during conversion.', 'type' => 'errors'];
        }
    } else {
        $feedback = ['message' => "You need at least {$conversion_rate} points to convert.", 'type' => 'errors'];
    }
}

$stmt = $pdo->prepare("SELECT bonus_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$bonus_balance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM bonus_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$bonus_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <h2>Bonus Center</h2>

    <?php if ($feedback['message']): ?>
        <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
    <?php endif; ?>

    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Your Bonus Points</h3>
            <h1><?php echo htmlspecialchars(number_format($bonus_balance, 2)); ?> Points</h1>
            <p>Conversion Rate: <?php echo htmlspecialchars($conversion_rate); ?> points = ₦1.00</p>
            <form method="post">
                <input type="hidden" name="action" value="convert">
                <button type="submit" <?php if ($bonus_balance < $conversion_rate) echo 'disabled'; ?>>Convert to Wallet Balance</button>
            </form>
        </div>
        <div class="widget">
            <h3>Recent Bonus Activity</h3>
            <ul>
                <?php foreach($bonus_history as $item): ?>
                    <li><?php echo htmlspecialchars($item['description']); ?> on <?php echo date('M j, Y', strtotime($item['created_at'])); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
