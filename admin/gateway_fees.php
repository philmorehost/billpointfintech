<?php
$page_title = 'Gateway Fees';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM gateway_fees");
$gateways = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Gateway Fee Control</h3>
        </div>
        <div class="card-body">
            <form action="financial_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_gateway_fees">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Gateway</th>
                            <th>Fee (%)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gateways as $gateway): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gateway['gateway_name']); ?></td>
                                <td>
                                    <input type="number" step="0.01" name="fees[<?php echo $gateway['id']; ?>][fee_percentage]" value="<?php echo htmlspecialchars($gateway['fee_percentage']); ?>" class="form-control">
                                </td>
                                <td>
                                    <select name="fees[<?php echo $gateway['id']; ?>][is_active]" class="form-control">
                                        <option value="1" <?php if ($gateway['is_active']) echo 'selected'; ?>>Active</option>
                                        <option value="0" <?php if (!$gateway['is_active']) echo 'selected'; ?>>Inactive</option>
                                    </select>
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
