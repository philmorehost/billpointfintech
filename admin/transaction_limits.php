<?php
$page_title = 'Transaction Limits';
require_once 'includes/header.php';

$limits = $pdo->query("SELECT * FROM transaction_limits")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="transaction-limits-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <form action="financial_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="update_transaction_limits">
        <?php foreach ($limits as $limit): ?>
        <div class="form-group">
            <label>Max transactions for <?php echo $limit['target_id']; ?></label>
            <input type="text" name="limits[<?php echo $limit['id']; ?>][max_count]" value="<?php echo htmlspecialchars($limit['max_count']); ?>">
            <input type="checkbox" name="limits[<?php echo $limit['id']; ?>][is_whitelisted]" value="1" <?php echo $limit['is_whitelisted'] ? 'checked' : ''; ?>> Whitelisted
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary">Update Limits</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
