<?php
$page_title = 'Buy Data';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT * FROM data_plans ORDER BY network, price");
$plans = $stmt->fetchAll();
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Buy Data</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="buy_data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>

            <div class="form-group">
                <label for="data_plan">Select Plan</label>
                <select id="data_plan" name="data_plan_id" required>
                    <option value="">-- Select a plan --</option>
                    <?php
                    $grouped_plans = [];
                    foreach ($plans as $plan) {
                        $grouped_plans[strtoupper($plan['network'])][] = $plan;
                    }
                    ?>
                    <?php foreach ($grouped_plans as $network => $network_plans): ?>
                        <optgroup label="<?php echo $network; ?>">
                            <?php foreach ($network_plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo $plan['quantity'] . ' (' . $plan['type'] . ') - ₦' . $plan['price']; ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn">Buy Now</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
