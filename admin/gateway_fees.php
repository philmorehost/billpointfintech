<?php
$page_title = 'Gateway Fees';
require_once 'includes/header.php';

$fees = $pdo->query("SELECT * FROM gateway_fees")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="gateway-fees-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <form action="financial_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="update_gateway_fees">
        <?php foreach ($fees as $fee): ?>
        <div class="form-group">
            <label><?php echo ucfirst($fee['gateway']); ?> Fee (NGN)</label>
            <input type="text" name="fees[<?php echo $fee['id']; ?>]" value="<?php echo htmlspecialchars($fee['fee']); ?>">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary">Update Fees</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
