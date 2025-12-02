<?php
$page_title = 'Transaction Limits';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM transaction_limits");
$limits = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaction Limit Control</h3>
        </div>
        <div class="card-body">
            <form action="financial_handler.php" method="POST" style="margin-bottom: 2rem;">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="add_limit">
                <h4>Add New Limit</h4>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="target_id">Target ID (e.g., phone number, account number)</label>
                        <input type="text" id="target_id" name="target_id" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="max_count">Max Count</label>
                        <input type="number" id="max_count" name="max_count" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="time_frame_seconds">Time Frame (seconds)</label>
                        <input type="number" id="time_frame_seconds" name="time_frame_seconds" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Add Limit</button>
                    </div>
                </div>
            </form>

            <hr>

            <h4>Existing Limits</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Target ID</th>
                        <th>Max Count</th>
                        <th>Time Frame (s)</th>
                        <th>Whitelisted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($limits as $limit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($limit['target_id']); ?></td>
                            <td><?php echo htmlspecialchars($limit['max_count']); ?></td>
                            <td><?php echo htmlspecialchars($limit['time_frame_seconds']); ?></td>
                            <td><?php echo $limit['is_whitelisted'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="edit_limit.php?id=<?php echo $limit['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                <form action="financial_handler.php" method="POST" style="display:inline;">
                                    <?php echo generate_csrf_token_input(); ?>
                                    <input type="hidden" name="action" value="delete_limit">
                                    <input type="hidden" name="limit_id" value="<?php echo $limit['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
