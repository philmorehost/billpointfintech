<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$data_plans = $pdo->query("SELECT * FROM data_plans ORDER BY network, price")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
$total_cost = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = $_POST['plan_id'] ?? '';
    $phone_numbers_raw = $_POST['phone_numbers'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        $results[] = ['number' => 'N/A', 'status' => 'Failed', 'reason' => 'Invalid data plan selected.'];
    } else {
        $amount = $plan['price'];
        $numbers = array_unique(array_filter(array_map('trim', explode("\n", $phone_numbers_raw))));
        $total_cost = count($numbers) * $amount;

        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() < $total_cost) {
            $results[] = ['number' => 'N/A', 'status' => 'Failed', 'reason' => 'Total cost exceeds wallet balance.'];
        } else {
            foreach ($numbers as $number) {
                $current_result = ['number' => $number, 'status' => '', 'reason' => ''];

                $limit_check = check_transaction_limit($pdo, $user_id, 'data', $amount);
                $recipient_limit_check = check_recipient_limit($pdo, 'data', $number, $amount);
                $blacklist_check = is_blacklisted($pdo, 'phone', $number);

                if (!$limit_check['allowed']) {
                    $current_result['status'] = 'Failed'; $current_result['reason'] = $limit_check['message'];
                } elseif (!$recipient_limit_check['allowed']) {
                    $current_result['status'] = 'Failed'; $current_result['reason'] = $recipient_limit_check['message'];
                } elseif ($blacklist_check['blacklisted']) {
                    $current_result['status'] = 'Failed'; $current_result['reason'] = $blacklist_check['message'];
                } else {
                    if (debit_wallet($user_id, $amount)) {
                        $transaction_id = create_transaction($user_id, 'Bulk Data', "Data for $number", $amount, 'pending', null, null, $number);
                        $response = buy_data($plan['network'], $number, $plan['type'], $plan['quantity']);

                        if (isset($response['status']) && $response['status'] === 'success') {
                            update_transaction_status($transaction_id, 'success', $response['ref'], json_encode($response));
                            update_recipient_total($pdo, 'data', $number, $amount);
                            $current_result['status'] = 'Success';
                            $current_result['reason'] = $response['response_desc'];
                        } else {
                            credit_wallet($user_id, $amount);
                            update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                            $current_result['status'] = 'Failed';
                            $current_result['reason'] = $response['desc'] ?? 'API Error';
                        }
                    } else {
                        $current_result['status'] = 'Failed'; $current_result['reason'] = 'Insufficient balance.';
                    }
                }
                $results[] = $current_result;
            }
        }
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2>Bulk Data Purchase</h2>
    <!-- ... (form and results table from previous step) ... -->
</div>
<?php include '../includes/footer.php'; ?>
