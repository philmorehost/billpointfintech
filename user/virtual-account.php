<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/beewave_api.php';

$user_id = $_SESSION['user_id'];
$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

// Fetch the current user's details, including any existing virtual account info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$virtual_account = null;
if (!empty($user['beewave_va_details'])) {
    $virtual_account = json_decode($user['beewave_va_details'], true);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_account'])) {
    if (!$virtual_account) {
        $user_details = [
            'name'  => $user['full_name'],
            'phone' => $user['phone_number'],
            'email' => $user['email'],
        ];

        $response = generate_virtual_account($user_details);

        if (isset($response['status']) && $response['status'] === true && !empty($response['virtual_accounts'])) {
            $new_account_details = $response['virtual_accounts'][0];

            try {
                // Save the new account details to the users table
                $stmt = $pdo->prepare("UPDATE users SET beewave_va_details = ? WHERE id = ?");
                $stmt->execute([json_encode($new_account_details), $user_id]);

                $feedback = ['message' => 'Your virtual account has been generated successfully!', 'type' => 'success'];
                $virtual_account = $new_account_details; // Update the variable for immediate display

            } catch (Exception $e) {
                $feedback = ['message' => 'Database error: Could not save your new account details.', 'type' => 'errors'];
            }
        } else {
            $error_message = $response['message'] ?? 'An unknown error occurred while generating your account.';
            $feedback = ['message' => $error_message, 'type' => 'errors'];
        }
    }
}

include 'includes/header.php';
?>
<style>
.account-details-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 500px; margin: 20px auto; }
.account-details-card h3 { margin-top: 0; }
.account-details-card p { font-size: 16px; margin: 12px 0; display: flex; justify-content: space-between; }
.btn-generate { background-color: #4f46e5; color: #fff; padding: 12px 25px; border-radius: 8px; font-size: 16px; }
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">My Virtual Account</span>
    </div>

    <div class="container">
        <?php if ($feedback['message']): ?>
            <div class="<?php echo htmlspecialchars($feedback['type']); ?> mb-3" style="max-width: 500px; margin: 15px auto;"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
        <?php endif; ?>

        <div class="account-details-card">
            <?php if ($virtual_account): ?>
                <h3>Your Account Details</h3>
                <p><strong>Bank Name:</strong> <span><?php echo htmlspecialchars($virtual_account['bank_name']); ?></span></p>
                <p><strong>Account Name:</strong> <span><?php echo htmlspecialchars($virtual_account['account_name']); ?></span></p>
                <p><strong>Account Number:</strong> <span><?php echo htmlspecialchars($virtual_account['account_number']); ?></span></p>
                <hr>
                <p style="text-align: center; color: #555; font-size: 14px;">Fund your wallet by transferring to this account. Your wallet will be credited automatically.</p>
            <?php else: ?>
                <h3>Generate a Permanent Account</h3>
                <p>Click the button below to generate a unique bank account for easy and automatic wallet funding.</p>
                <form method="post">
                    <button type="submit" name="generate_account" class="btn-generate">Generate My Account</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
