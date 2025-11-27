<?php
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$user_id = $_SESSION['user_id'];
$pdo = db_connect();
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_email = $stmt->fetchColumn();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (empty($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid amount.';
    } else {
        // Amount is in kobo for Paystack
        $amount_in_kobo = $amount * 100;

        // Generate a unique reference for this transaction
        $reference = 'blp_' . uniqid();

        // Initialize transaction with Paystack
        $url = 'https://api.paystack.co/transaction/initialize';
        $fields = [
            'email' => $user_email,
            'amount' => $amount_in_kobo,
            'reference' => $reference,
            'callback_url' => SITE_URL . 'user/payment-callback.php'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $errors[] = 'An error occurred. Please try again.';
        } else {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] == true) {
                // Redirect to Paystack checkout page
                header('Location: ' . $result['data']['authorization_url']);
                exit;
            } else {
                $errors[] = 'Could not initiate payment. ' . ($result['message'] ?? '');
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2>Fund Wallet via Card</h2>
    <div class="notice"><p>You will be redirected to our secure payment partner, Paystack, to complete this transaction.</p></div>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="amount">Amount (₦)</label>
            <input type="number" name="amount" id="amount" required min="100" step="0.01">
        </div>
        <button type="submit">Proceed to Payment</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
