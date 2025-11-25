<?php
$page_title = 'Buy Airtime';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Buy Airtime</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="buy_airtime">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="network">Network</label>
                <select id="network" name="network" required>
                    <option value="">-- Select Network --</option>
                    <?php foreach ($config['services']['airtime_networks'] as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="50" placeholder="e.g., 100" required>
            </div>

            <button type="submit" class="btn">Buy Now</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
