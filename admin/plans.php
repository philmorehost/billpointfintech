<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: plans.php');
        exit();
    }

    if ($_POST['action'] === 'add_data_plan') {
        $stmt = $pdo->prepare("INSERT INTO data_plans (network, type, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['network'], $_POST['type'], $_POST['quantity'], $_POST['price']]);
        set_flash_message('success', 'Data plan added.');
    }

    if ($_POST['action'] === 'add_cable_plan') {
        $stmt = $pdo->prepare("INSERT INTO cable_plans (cable_provider, package_name, package_code, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['cable_provider'], $_POST['package_name'], $_POST['package_code'], $_POST['price']]);
        set_flash_message('success', 'Cable plan added.');
    }

    if ($_POST['action'] === 'delete_plan') {
        $table = $_POST['table'];
        $id = $_POST['id'];

        $allowed_tables = ['data_plans', 'cable_plans'];
        if (in_array($table, $allowed_tables)) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Plan deleted.');
        } else {
            set_flash_message('error', 'Invalid operation.');
        }
    }

    header('Location: plans.php');
    exit();
}

$data_plans = $pdo->query("SELECT * FROM data_plans ORDER BY network, price")->fetchAll();
$cable_plans = $pdo->query("SELECT * FROM cable_plans ORDER BY cable_provider, price")->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Service Plans</title>
</head>
<body>
    <h1>Manage Service Plans</h1>
    <a href="index.php">Back to Dashboard</a>
    <?php display_flash_message(); ?>

    <h2>Data Plans</h2>
    <table>
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
                    <form action="plans.php" method="POST">
                        <input type="hidden" name="action" value="delete_plan">
                        <input type="hidden" name="table" value="data_plans">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Add Data Plan</h3>
    <form action="plans.php" method="POST">
        <input type="hidden" name="action" value="add_data_plan">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="text" name="network" placeholder="Network" required>
        <input type="text" name="type" placeholder="Type" required>
        <input type="text" name="quantity" placeholder="Quantity" required>
        <input type="number" name="price" placeholder="Price" required>
        <button type="submit">Add</button>
    </form>

    <h2>Cable TV Plans</h2>
    <table>
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
                    <form action="plans.php" method="POST">
                        <input type="hidden" name="action" value="delete_plan">
                        <input type="hidden" name="table" value="cable_plans">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Add Cable Plan</h3>
    <form action="plans.php" method="POST">
        <input type="hidden" name="action" value="add_cable_plan">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="text" name="cable_provider" placeholder="Provider (e.g., dstv)" required>
        <input type="text" name="package_name" placeholder="Package Name" required>
        <input type="text" name="package_code" placeholder="Package Code" required>
        <input type="number" name="price" placeholder="Price" required>
        <button type="submit">Add</button>
    </form>
</body>
</html>
