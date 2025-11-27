<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id_to_delete = $_POST['blacklist_id'] ?? null;
        if ($id_to_delete) {
            $stmt = $pdo->prepare("DELETE FROM blacklist WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $feedback = ['message' => 'Identifier removed from blacklist.', 'type' => 'success'];
        }
    } else {
        $identifier_type = $_POST['identifier_type'] ?? '';
        $identifier_value = trim($_POST['identifier_value'] ?? '');
        $reason = $_POST['reason'] ?? '';

        if (!empty($identifier_type) && !empty($identifier_value)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO blacklist (identifier_type, identifier_value, reason) VALUES (?, ?, ?)");
                $stmt->execute([$identifier_type, $identifier_value, $reason]);
                $feedback = ['message' => 'Identifier has been added to the blacklist.', 'type' => 'success'];
            } catch (Exception $e) {
                // Handle unique constraint violation
                if ($e->getCode() == 23000) {
                    $feedback = ['message' => 'This identifier is already on the blacklist.', 'type' => 'errors'];
                } else {
                    $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
                }
            }
        } else {
            $feedback = ['message' => 'Identifier type and value cannot be empty.', 'type' => 'errors'];
        }
    }
}

$stmt = $pdo->query("SELECT * FROM blacklist ORDER BY created_at DESC");
$blacklisted_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Manage Blacklist</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add to Blacklist</h3>
        <form method="post">
            <div class="form-group">
                <label for="identifier_type">Identifier Type</label>
                <select name="identifier_type" id="identifier_type" class="form-control" required>
                    <option value="phone">Phone Number</option>
                    <option value="meter_number">Meter Number</option>
                    <option value="smartcard">Smartcard/IUC</option>
                    <option value="account_number">Bank Account</option>
                </select>
            </div>
            <div class="form-group">
                <label for="identifier_value">Identifier Value</label>
                <input type="text" name="identifier_value" id="identifier_value" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="reason">Reason (Optional)</label>
                <textarea name="reason" id="reason" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Blacklist</button>
        </form>
    </div>
    <div class="widget">
        <h3>Currently Blacklisted</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Type</th><th>Value</th><th>Reason</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($blacklisted_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['identifier_type']); ?></td>
                        <td><?php echo htmlspecialchars($item['identifier_value']); ?></td>
                        <td><?php echo htmlspecialchars($item['reason']); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="blacklist_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
