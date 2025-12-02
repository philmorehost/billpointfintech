<?php
$page_title = 'FX Rates';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM fx_rates");
$rates = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">FX Rate Control</h3>
        </div>
        <div class="card-body">
            <form action="financial_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_fx_rates">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Currency Pair</th>
                            <th>Base Rate</th>
                            <th>Admin Markup (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rates as $rate): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rate['currency_pair']); ?></td>
                                <td><?php echo htmlspecialchars($rate['rate']); ?></td>
                                <td>
                                    <input type="number" step="0.0001" name="rates[<?php echo $rate['id']; ?>][admin_markup]" value="<?php echo htmlspecialchars($rate['admin_markup']); ?>" class="form-control">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
