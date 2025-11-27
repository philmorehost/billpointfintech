<?php
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = db_connect();
$errors = [];
$success_message = '';

// Check if user already has a virtual account
$stmt = $pdo->prepare("SELECT * FROM virtual_accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$virtual_account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_account') {
    // 1. Get user details
    $stmt = $pdo->prepare("SELECT email, full_name, phone_number, paystack_customer_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $customer_code = $user['paystack_customer_code'];

    try {
        // 2. Create a Paystack Customer if one doesn't exist
        if (empty($customer_code)) {
            $customer_response = paystack_api_request('https://api.paystack.co/customer', [
                'email' => $user['email'],
                'first_name' => explode(' ', $user['full_name'])[0],
                'last_name' => explode(' ', $user['full_name'])[1] ?? '',
                'phone' => $user['phone_number']
            ]);

            if (!$customer_response['status']) {
                throw new Exception("Could not create customer profile: " . ($customer_response['message'] ?? 'Unknown error'));
            }
            $customer_code = $customer_response['data']['customer_code'];

            // Save customer code to user profile
            $stmt = $pdo->prepare("UPDATE users SET paystack_customer_code = ? WHERE id = ?");
            $stmt->execute([$customer_code, $user_id]);
        }

        // 3. Create Dedicated Virtual Account
        $account_response = paystack_api_request('https://api.paystack.co/dedicated_account', [
            'customer' => $customer_code,
            'preferred_bank' => 'wema-bank' // Or another supported bank
        ]);

        if (!$account_response['status']) {
            throw new Exception("Could not create virtual account: " . ($account_response['message'] ?? 'Unknown error'));
        }

        $account_data = $account_response['data'];

        // 4. Save account details to our database
        $stmt = $pdo->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, paystack_assignment_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $account_data['bank']['name'],
            $account_data['account_number'],
            $account_data['account_name'],
            $account_data['id']
        ]);

        $success_message = "Your dedicated account has been created successfully!";
        // Re-fetch the account to display it
        $stmt = $pdo->prepare("SELECT * FROM virtual_accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $virtual_account = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Helper function for Paystack API calls
function paystack_api_request($url, $payload = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $headers = ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY, 'Content-Type: application/json'];

    if ($payload) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['status' => false, 'message' => 'cURL Error: ' . $err];
    }

    return json_decode($response, true);
}


include '../includes/header.php';
?>

<div class="container">
    <h2>Your Virtual Account</h2>

    <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
    <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>

    <?php if ($virtual_account): ?>
        <div class="notice">
            <p>Fund your wallet by transferring to the account details below. Your wallet will be credited automatically.</p>
        </div>
        <div class="account-details">
            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($virtual_account['bank_name']); ?></p>
            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($virtual_account['account_number']); ?></p>
            <p><strong>Account Name:</strong> <?php echo htmlspecialchars($virtual_account['account_name']); ?></p>
        </div>
    <?php else: ?>
        <p>You do not have a dedicated virtual account yet.</p>
        <p>Click the button below to generate a unique bank account for easy and automatic wallet funding.</p>
        <form method="post">
            <input type="hidden" name="action" value="create_account">
            <button type="submit">Generate My Account</button>
        </form>
    <?php endif; ?>
</div>
<style>.account-details p { font-size: 1.2rem; margin: 10px 0; }</style>

<?php include '../includes/footer.php'; ?>
