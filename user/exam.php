<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
// ... (rest of initial setup)
$exam_types = [
    'waec' => ['name' => 'WAEC Result Checker', 'price' => 3800],
    'neco' => ['name' => 'NECO Result Checker', 'price' => 1400],
    'nabteb' => ['name' => 'NABTEB Result Checker', 'price' => 950]
];
$errors = [];
$success_message = '';
$limit_error = null;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);

    if (empty($type) || !array_key_exists($type, $exam_types) || $quantity <= 0) {
        $errors[] = "Invalid selection or quantity.";
    } else {
        $exam_details = $exam_types[$type];
        $amount = $exam_details['price'] * $quantity;

        // --- Security & Limit Checks ---
        $limit_check = check_transaction_limit($pdo, $user_id, 'exam', $amount);
        if (!$limit_check['allowed']) {
            $limit_error = $limit_check['message'];
        }
        // --- End of Checks ---

        if (empty($errors) && !$limit_error) {
            $description = "Exam Pin purchase: $quantity x {$exam_details['name']}";
            $transaction_id = create_transaction($user_id, 'Exam Pin', $description, $amount);

            if (!$transaction_id) {
                $errors[] = 'Failed to create transaction record.';
            } else {
                if (debit_wallet($user_id, $amount)) {
                    $response = purchase_exam_pin($type, $quantity);
                    if (isset($response['status']) && $response['status'] === 'success') {
                        update_transaction_status($transaction_id, 'success', $response['ref'] ?? 'N/A', json_encode($response));
                        $success_message = $response['response_desc'];
                    } else {
                        credit_wallet($user_id, $amount);
                        update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                        $errors[] = $response['desc'] ?? 'An unknown error occurred.';
                    }
                } else {
                    update_transaction_status($transaction_id, 'failed', null, 'Insufficient funds');
                    $errors[] = 'Insufficient wallet balance.';
                }
            }
        }
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2>Purchase Exam Pin</h2>
    <div id="response-message">
        <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
        <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>
    </div>
    <form id="exam-form" action="exam.php" method="post">
        <div class="form-group">
            <label for="type">Select Exam Type</label>
            <select name="type" id="type" required>
                <option value="">-- Select Type --</option>
                <?php foreach($exam_types as $code => $details): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>" data-price="<?php echo htmlspecialchars($details['price']); ?>">
                        <?php echo htmlspecialchars($details['name']); ?> - ₦<?php echo htmlspecialchars($details['price']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" required min="1" value="1">
        </div>
         <p><strong>Total Price:</strong> <span id="price-display">₦0.00</span></p>
        <button type="submit">Purchase Pin</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
<?php if ($limit_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showModal('Transaction Limit Exceeded', <?php echo json_encode($limit_error); ?>);
    });
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const quantityInput = document.getElementById('quantity');
    const priceDisplay = document.getElementById('price-display');

    function updatePrice() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const quantity = quantityInput.value || 1;
        const totalPrice = (price * quantity).toFixed(2);
        priceDisplay.textContent = `₦${totalPrice}`;
    }

    typeSelect.addEventListener('change', updatePrice);
    quantityInput.addEventListener('input', updatePrice);
    updatePrice();
});
</script>
