<?php
$page_title = 'FX Rates';
require_once 'includes/header.php';

$rates = $pdo->query("SELECT * FROM fx_rates")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="fx-rates-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <form action="financial_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="update_fx_rates">
        <?php foreach ($rates as $rate): ?>
        <div class="form-group">
            <label><?php echo $rate['rate_pair']; ?> Markup (%)</label>
            <input type="text" name="rates[<?php echo $rate['id']; ?>]" value="<?php echo htmlspecialchars($rate['markup_percentage']); ?>">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary">Update Rates</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
