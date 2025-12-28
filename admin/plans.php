<?php
$page_title = 'Admin - Service Plans';
require_once '../includes/admin_header.php'; // Use the new admin header

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validate_csrf_token()) {
        $action = $_POST['action'];

        if ($action === 'add_data_plan') {
            $stmt = $pdo->prepare("INSERT INTO data_plans (network, type, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['network'], $_POST['type'], $_POST['quantity'], $_POST['price']]);
            set_flash_message('success', 'Data plan added.');
        } elseif ($action === 'add_cable_plan') {
            $stmt = $pdo->prepare("INSERT INTO cable_plans (cable_provider, package_name, package_code, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['cable_provider'], $_POST['package_name'], $_POST['package_code'], $_POST['price']]);
            set_flash_message('success', 'Cable plan added.');
        } elseif ($action === 'delete_plan') {
            $table = $_POST['table'];
            $id = (int)$_POST['id'];

            $allowed_tables = ['data_plans', 'cable_plans'];
            if (in_array($table, $allowed_tables)) {
                $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                set_flash_message('success', 'Plan deleted.');
            } else {
                set_flash_message('error', 'Invalid operation.');
            }
        }
    } else {
        set_flash_message('error', 'CSRF validation failed.');
    }
    redirect('plans.php');
}

$data_plans = $pdo->query("SELECT * FROM data_plans ORDER BY network, price")->fetchAll();
$cable_plans = $pdo->query("SELECT * FROM cable_plans ORDER BY cable_provider, price")->fetchAll();
?>

<div class="admin-header">
    <h1>Manage Service Plans</h1>
    <p>Add or remove data and cable TV subscription plans.</p>
</div>

<?php display_flash_message(); ?>

<div class="content-box">
    <h3>Data Plans</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Network</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data_plans as $plan): ?>
            <tr>
                <td><?php echo htmlspecialchars($plan['network']); ?></td>
                <td><?php echo htmlspecialchars($plan['type']); ?></td>
                <td><?php echo htmlspecialchars($plan['quantity']); ?></td>
                <td><?php echo htmlspecialchars($plan['price']); ?></td>
                <td>
                    <form action="plans.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this plan?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_plan">
                        <input type="hidden" name="table" value="data_plans">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="margin-top: 2rem;">Add Data Plan</h4>
    <form action="plans.php" method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="add_data_plan">
        <div class="form-row">
            <input type="text" name="network" placeholder="Network" required>
            <input type="text" name="type" placeholder="Type" required>
            <input type="text" name="quantity" placeholder="Quantity" required>
            <input type="number" name="price" placeholder="Price" required>
            <button type="submit" class="btn">Add Plan</button>
        </div>
    </form>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <h3>Cable TV Plans</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Provider</th>
                <th>Package Name</th>
                <th>Package Code</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cable_plans as $plan): ?>
            <tr>
                <td><?php echo htmlspecialchars($plan['cable_provider']); ?></td>
                <td><?php echo htmlspecialchars($plan['package_name']); ?></td>
                <td><?php echo htmlspecialchars($plan['package_code']); ?></td>
                <td><?php echo htmlspecialchars($plan['price']); ?></td>
                <td>
                    <form action="plans.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this plan?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_plan">
                        <input type="hidden" name="table" value="cable_plans">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="margin-top: 2rem;">Add Cable Plan</h4>
    <form action="plans.php" method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="add_cable_plan">
         <div class="form-row">
            <input type="text" name="cable_provider" placeholder="Provider" required>
            <input type="text" name="package_name" placeholder="Package Name" required>
            <input type="text" name="package_code" placeholder="Package Code" required>
            <input type="number" name="price" placeholder="Price" required>
            <button type="submit" class="btn">Add Plan</button>
        </div>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
