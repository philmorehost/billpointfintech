<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$networks = $pdo->query("SELECT * FROM networks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
$total_cost = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $network_code = $_POST['network'] ?? '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $phone_numbers_raw = $_POST['phone_numbers'] ?? '';

    // 1. Parse and clean numbers
    $numbers = array_unique(array_filter(array_map('trim', explode("\n", $phone_numbers_raw))));
    $total_numbers = count($numbers);
    $total_cost = $total_numbers * $amount;

    // --- Pre-checks ---
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() < $total_cost) {
        $results[] = ['number' => 'N/A', 'status' => 'Failed', 'reason' => 'Total cost exceeds wallet balance. Please fund your wallet.'];
    } else {
        // 2. Process each number sequentially
        foreach ($numbers as $number) {
            $current_result = ['number' => $number, 'status' => '', 'reason' => ''];

            // 3. Perform security checks for each number
            $limit_check = check_transaction_limit($pdo, $user_id, 'airtime', $amount);
            $recipient_limit_check = check_recipient_limit($pdo, 'airtime', $number, $amount);
            $blacklist_check = is_blacklisted($pdo, 'phone', $number);

            if (!$limit_check['allowed']) {
                $current_result['status'] = 'Failed';
                $current_result['reason'] = $limit_check['message'];
            } elseif (!$recipient_limit_check['allowed']) {
                $current_result['status'] = 'Failed';
                $current_result['reason'] = $recipient_limit_check['message'];
            } elseif ($blacklist_check['blacklisted']) {
                $current_result['status'] = 'Failed';
                $current_result['reason'] = $blacklist_check['message'];
            } else {
                // 4. If checks pass, process the transaction
                if (debit_wallet($user_id, $amount)) {
                    $transaction_id = create_transaction($user_id, 'Bulk Airtime', "Airtime for $number", $amount, 'pending', null, null, $number);
                    $response = buy_airtime($network_code, $number, $amount);

                    if (isset($response['status']) && $response['status'] === 'success') {
                        update_transaction_status($transaction_id, 'success', $response['ref'], json_encode($response));
                        update_recipient_total($pdo, 'airtime', $number, $amount);
                        $current_result['status'] = 'Success';
                        $current_result['reason'] = $response['response_desc'];
                    } else {
                        credit_wallet($user_id, $amount); // Refund
                        update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                        $current_result['status'] = 'Failed';
                        $current_result['reason'] = $response['desc'] ?? 'API Error';
                    }
                } else {
                    $current_result['status'] = 'Failed';
                    $current_result['reason'] = 'Insufficient balance for this transaction.';
                }
            }
            $results[] = $current_result;
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2>Bulk Airtime Purchase</h2>
    <p>Enter multiple phone numbers, one per line. Duplicates will be removed automatically.</p>

    <?php if (!empty($results)): ?>
        <div class="widget">
            <h3>Processing Results</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Number</th><th>Status</th><th>Reason</th></tr></thead>
                    <tbody>
                        <?php foreach($results as $result): ?>
                        <tr class="<?php echo $result['status'] === 'Success' ? 'table-success' : 'table-danger'; ?>">
                            <td><?php echo htmlspecialchars($result['number']); ?></td>
                            <td><?php echo htmlspecialchars($result['status']); ?></td>
                            <td><?php echo htmlspecialchars($result['reason']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="widget">
        <form method="post" onsubmit="return confirm('You are about to process multiple transactions. This action cannot be undone. Do you want to continue?');">
            <!-- ... (form fields from previous step) ... -->
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
