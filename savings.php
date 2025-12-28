<?php
$page_title = 'My Savings';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

// Handle new savings goal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_goal') {
    if (validate_csrf_token()) {
        $goal_name = htmlspecialchars(trim($_POST['goal_name']));
        $target_amount = (float)$_POST['target_amount'];
        $saving_frequency = $_POST['saving_frequency'];
        $saving_amount = (float)$_POST['saving_amount'];

        // Set the first deduction to be 24 hours or 7 days from now
        $next_deduction_at = $saving_frequency === 'daily' ? date('Y-m-d H:i:s', time() + 86400) : date('Y-m-d H:i:s', time() + 604800);

        if (!empty($goal_name) && $target_amount > 0 && $saving_amount > 0 && in_array($saving_frequency, ['daily', 'weekly'])) {
            $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, saving_frequency, saving_amount, next_deduction_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $goal_name, $target_amount, $saving_frequency, $saving_amount, $next_deduction_at]);
            set_flash_message('success', 'New savings goal created successfully.');
        } else {
            set_flash_message('error', 'Invalid input for savings goal.');
        }
    } else {
        set_flash_message('error', 'CSRF validation failed.');
    }
    header('Location: savings.php');
    exit();
}

// Fetch user's savings goals
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$goals = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>My Savings Goals</h1>
        <p>Automate your savings and reach your goals faster.</p>
    </div>

    <div class="content-box">
        <h2>Your Goals</h2>
        <?php if (empty($goals)): ?>
            <p>You have no savings goals yet. Create one below!</p>
        <?php else: ?>
            <?php foreach ($goals as $goal): ?>
                <div class="savings-goal">
                    <h4><?php echo htmlspecialchars($goal['goal_name']); ?></h4>
                    <progress value="<?php echo $goal['current_amount']; ?>" max="<?php echo $goal['target_amount']; ?>"></progress>
                    <p>
                        Saved: <strong>₦<?php echo number_format($goal['current_amount'], 2); ?></strong> of
                        <strong>₦<?php echo number_format($goal['target_amount'], 2); ?></strong>
                    </p>
                    <p>
                        Plan: ₦<?php echo number_format($goal['saving_amount'], 2); ?> every <?php echo $goal['saving_frequency']; ?>.
                        Next deduction: <?php echo date('M d, Y', strtotime($goal['next_deduction_at'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="content-box" style="margin-top: 20px;">
        <h2>Create New Savings Goal</h2>
        <?php display_flash_message(); ?>
        <form action="savings.php" method="POST">
            <input type="hidden" name="action" value="create_goal">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="goal_name">Goal Name</label>
                <input type="text" name="goal_name" required>
            </div>
            <div class="form-group">
                <label for="target_amount">Target Amount (NGN)</label>
                <input type="number" name="target_amount" min="100" required>
            </div>
            <div class="form-group">
                <label for="saving_frequency">Saving Frequency</label>
                <select name="saving_frequency" required>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                </select>
            </div>
             <div class="form-group">
                <label for="saving_amount">Amount to Save per Interval</label>
                <input type="number" name="saving_amount" min="50" required>
            </div>
            <button type="submit" class="btn">Create Goal</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<style>
.savings-goal {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}
.savings-goal progress {
    width: 100%;
}
</style>
