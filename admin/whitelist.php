<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id_to_delete = $_POST['whitelist_id'] ?? null;
        if ($id_to_delete) {
            $stmt = $pdo->prepare("DELETE FROM whitelist WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $feedback = ['message' => 'Recipient removed from whitelist.', 'type' => 'success'];
        }
    } else {
        $recipient = trim($_POST['recipient'] ?? '');
        $service = $_POST['service'] ?? '';
        $reason = $_POST['reason'] ?? '';

        if (!empty($recipient) && !empty($service)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO whitelist (recipient, service, reason) VALUES (?, ?, ?)");
                $stmt->execute([$recipient, $service, $reason]);
                $feedback = ['message' => 'Recipient has been whitelisted for this service.', 'type' => 'success'];
            } catch (Exception $e) {
                if ($e->getCode() == 23000) {
                    $feedback = ['message' => 'This recipient is already whitelisted for this service.', 'type' => 'errors'];
                } else {
                    $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
                }
            }
        } else {
            $feedback = ['message' => 'Recipient and service cannot be empty.', 'type' => 'errors'];
        }
    }
}

$stmt = $pdo->query("SELECT * FROM whitelist ORDER BY created_at DESC");
$whitelisted_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT slug, name FROM services");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Whitelist</h2>
<p>Whitelisted recipients can bypass the per-number daily transaction limits.</p>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add to Whitelist</h3>
        <form method="post">
            <div class="form-group">
                <label for="recipient">Recipient Identifier</label>
                <input type="text" name="recipient" id="recipient" class="form-control" required placeholder="e.g., phone number, meter number">
            </div>
            <div class="form-group">
                <label for="service">Service</label>
                <select name="service" id="service" class="form-control" required>
                    <option value="*">All Services</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo htmlspecialchars($service['slug']); ?>">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Reason (Optional)</label>
                <textarea name="reason" id="reason" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Whitelist Recipient</button>
        </form>
    </div>
    <div class="widget">
        <h3>Currently Whitelisted</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Recipient</th><th>Service</th><th>Reason</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($whitelisted_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['recipient']); ?></td>
                        <td><?php echo htmlspecialchars($item['service'] === '*' ? 'All' : ucfirst($item['service'])); ?></td>
                        <td><?php echo htmlspecialchars($item['reason']); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="whitelist_id" value="<?php echo $item['id']; ?>">
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
