<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_plan' || $action === 'edit_plan') {
            $network = $_POST['network'];
            $type = $_POST['type'];
            $quantity = $_POST['quantity'];
            $price = $_POST['price'];

            if (empty($network) || empty($type) || empty($quantity) || empty($price)) {
                throw new Exception("All fields are required.");
            }

            if ($action === 'add_plan') {
                $stmt = $pdo->prepare("INSERT INTO data_plans (network, type, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$network, $type, $quantity, $price]);
                $feedback = ['message' => 'Data plan added successfully.', 'type' => 'success'];
            } else {
                $plan_id = $_POST['plan_id'];
                $stmt = $pdo->prepare("UPDATE data_plans SET network = ?, type = ?, quantity = ?, price = ? WHERE id = ?");
                $stmt->execute([$network, $type, $quantity, $price, $plan_id]);
                $feedback = ['message' => 'Data plan updated successfully.', 'type' => 'success'];
            }
        } elseif ($action === 'delete_plan') {
            $plan_id = $_POST['plan_id'];
            $stmt = $pdo->prepare("DELETE FROM data_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $feedback = ['message' => 'Data plan deleted successfully.', 'type' => 'success'];
        }
    } catch (Exception $e) {
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}


// Pagination logic
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of plans for pagination
$total_stmt = $pdo->query("SELECT COUNT(*) FROM data_plans");
$total_plans = $total_stmt->fetchColumn();
$total_pages = ceil($total_plans / $limit);

$stmt = $pdo->prepare("SELECT * FROM data_plans ORDER BY network, price LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Data Plans</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add/Edit Data Plan</h3>
        <form method="post">
            <input type="hidden" name="action" id="form-action" value="add_plan">
            <input type="hidden" name="plan_id" id="plan-id" value="">
            <div class="form-group">
                <label for="network">Network</label>
                <input type="text" name="network" id="network" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="type">Type (e.g., sme-data, cg-data)</label>
                <input type="text" name="type" id="type" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity (e.g., 1gb, 500mb)</label>
                <input type="text" name="quantity" id="quantity" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price">Price (₦)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary" id="form-submit-btn">Add Plan</button>
            <button type="button" class="btn btn-secondary" id="cancel-edit-btn" style="display:none;">Cancel Edit</button>
        </form>
    </div>
    <div class="widget">
        <h3>Existing Data Plans</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Network</th><th>Type</th><th>Quantity</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($data_plans as $plan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($plan['network']); ?></td>
                        <td><?php echo htmlspecialchars($plan['type']); ?></td>
                        <td><?php echo htmlspecialchars($plan['quantity']); ?></td>
                        <td>₦<?php echo htmlspecialchars(number_format($plan['price'], 2)); ?></td>
                        <td>
                            <button class="btn btn-sm btn-secondary edit-btn" data-plan='<?php echo json_encode($plan, JSON_HEX_APOS); ?>'>Edit</button>
                            <form method="post" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    const form = document.querySelector('form');
    const actionInput = document.getElementById('form-action');
    const planIdInput = document.getElementById('plan-id');
    const submitBtn = document.getElementById('form-submit-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const plan = JSON.parse(this.getAttribute('data-plan'));
            form.network.value = plan.network;
            form.type.value = plan.type;
            form.quantity.value = plan.quantity;
            form.price.value = plan.price;

            actionInput.value = 'edit_plan';
            planIdInput.value = plan.id;
            submitBtn.textContent = 'Update Plan';
            cancelBtn.style.display = 'inline-block';
            window.scrollTo(0, 0);
        });
    });

    cancelBtn.addEventListener('click', function() {
        form.reset();
        actionInput.value = 'add_plan';
        planIdInput.value = '';
        submitBtn.textContent = 'Add Plan';
        this.style.display = 'none';
    });
});
</script>

<?php include 'footer.php'; ?>
