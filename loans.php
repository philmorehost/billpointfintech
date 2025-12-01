<?php
$page_title = 'My Loans';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

$user_id = $_SESSION['user_id'];

// Handle new loan application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_for_loan') {
    if (validate_csrf_token()) {
        $amount = (float)$_POST['amount'];

        // Eligibility check (must be KYC verified and have no active/pending loans)
        $eligibility_stmt = $pdo->prepare("SELECT kyc_verified_at, (SELECT COUNT(*) FROM loans WHERE user_id = ? AND status IN ('pending', 'active', 'defaulted')) as active_loans FROM users WHERE id = ?");
        $eligibility_stmt->execute([$user_id, $user_id]);
        $user_data = $eligibility_stmt->fetch();

        if ($user_data['kyc_verified_at'] === null) {
            set_flash_message('error', 'You must be KYC verified to apply for a loan.');
        } elseif ($user_data['active_loans'] > 0) {
            set_flash_message('error', 'You already have an active or pending loan.');
        } elseif ($amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO loans (user_id, amount_requested) VALUES (?, ?)");
            $stmt->execute([$user_id, $amount]);
            set_flash_message('success', 'Your loan application has been submitted for review.');
        } else {
            set_flash_message('error', 'Invalid loan amount.');
        }
    } else {
        set_flash_message('error', 'CSRF validation failed.');
    }
    header('Location: loans.php');
    exit();
}

// Fetch user's loan history
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll();

// Check eligibility again for display
$eligibility_stmt = $pdo->prepare("SELECT kyc_verified_at, (SELECT COUNT(*) FROM loans WHERE user_id = ? AND status IN ('pending', 'active', 'defaulted')) as active_loans FROM users WHERE id = ?");
$eligibility_stmt->execute([$user_id, $user_id]);
$user_data = $eligibility_stmt->fetch();
$is_eligible = $user_data['kyc_verified_at'] !== null && $user_data['active_loans'] == 0;


include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>My Loans</h1>
        <p>Access quick loans based on your account activity.</p>
    </div>

    <?php if ($is_eligible): ?>
    <div class="content-box">
        <h2>Apply for a New Loan</h2>
        <?php display_flash_message(); ?>
        <form action="loans.php" method="POST">
            <input type="hidden" name="action" value="apply_for_loan">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="amount">Loan Amount (NGN)</label>
                <input type="number" name="amount" min="1000" placeholder="e.g., 10000" required>
            </div>
            <button type="submit" class="btn">Submit Application</button>
        </form>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <?php if ($user_data['kyc_verified_at'] === null): ?>
                You are not eligible for a loan. Please <a href="kyc.php">complete your KYC verification</a> to apply.
            <?php else: ?>
                You are not eligible for a new loan at this time. Please repay any active loans before applying for a new one.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="content-box" style="margin-top: 20px;">
        <h2>Loan History</h2>
        <?php if (empty($loans)): ?>
            <p>You have no loan history.</p>
        <?php else: ?>
            <table class="support-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></td>
                            <td>₦<?php echo number_format($loan['amount_requested'], 2); ?></td>
                            <td><span class="status-<?php echo strtolower($loan['status']); ?>"><?php echo ucfirst($loan['status']); ?></span></td>
                            <td><?php echo $loan['repayment_due_date'] ? date('M d, Y', strtotime($loan['repayment_due_date'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<style>
.status-active { background-color: #17a2b8; color: white; }
.status-repaid { background-color: #28a745; color: white; }
.status-defaulted { background-color: #343a40; color: white; }
</style>
