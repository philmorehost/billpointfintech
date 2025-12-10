<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

$rates = [];
$table_exists = false;
try {
    // Check if table exists and fetch rates
    $pdo->query("SELECT 1 FROM `fx_rates` LIMIT 1");
    $table_exists = true;
    $stmt = $pdo->query("SELECT * FROM `fx_rates` ORDER BY currency_pair ASC");
    $rates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gracefully handle the "table not found" error
    if (strpos($e->getMessage(), "Table 'billpoint.fx_rates' doesn't exist") !== false) {
        set_flash_message('warning', 'The `fx_rates` table does not exist. Please run the database migrations.');
    } else {
        set_flash_message('error', 'A database error occurred: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table_exists) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        redirect('/admin/fx_rates.php');
    }

    if (isset($_POST['add_rate'])) {
        $pair = strtoupper(trim($_POST['new_pair']));
        $markup = trim($_POST['new_markup']);
        if (!empty($pair) && is_numeric($markup)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `fx_rates` (currency_pair, markup_percentage) VALUES (?, ?)");
                $stmt->execute([$pair, $markup]);
                set_flash_message('success', 'New FX rate added successfully.');
            } catch (PDOException $e) {
                set_flash_message('error', 'Could not add rate. It might already exist.');
            }
        } else {
            set_flash_message('error', 'Invalid input for new rate.');
        }
    } elseif (isset($_POST['update_rate'])) {
        $id = (int)$_POST['rate_id'];
        $markup = trim($_POST['markup']);
        if (is_numeric($markup)) {
            try {
                $stmt = $pdo->prepare("UPDATE `fx_rates` SET markup_percentage = ? WHERE id = ?");
                $stmt->execute([$markup, $id]);
                set_flash_message('success', 'FX rate updated successfully.');
            } catch (PDOException $e) {
                set_flash_message('error', 'Could not update rate.');
            }
        }
    } elseif (isset($_POST['delete_rate'])) {
        $id = (int)$_POST['rate_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM `fx_rates` WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'FX rate deleted successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Could not delete rate.');
        }
    }
    redirect('/admin/fx_rates.php');
}

$page_title = 'Manage FX Rates';
require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1>Manage FX Rate Markups</h1>
    <p>Set the percentage markup for currency exchange rates.</p>
</div>

<?php display_flash_message(); ?>

    <?php if (!$table_exists): ?>
        <div class="alert alert-danger">
            <strong>Database Table Missing!</strong> Before you can manage FX rates, you must run the pending database migrations.
            <a href="migrations.php" class="btn btn-primary mt-2">Go to Migrations Page</a>
        </div>
    <?php else: ?>
        <div class="content-box">
            <h3>Add New Rate</h3>
            <form action="fx_rates.php" method="POST">
                <?php csrf_field(); ?>
                <div class="form-row">
                    <div class="col">
                        <input type="text" name="new_pair" class="form-control" placeholder="e.g., NGN_USD" required>
                    </div>
                    <div class="col">
                        <input type="number" step="0.01" name="new_markup" class="form-control" placeholder="e.g., 1.50 for 1.5%" required>
                    </div>
                    <div class="col">
                        <button type="submit" name="add_rate" class="btn btn-success">Add Rate</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-box mt-4">
            <h3>Existing Rates</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Currency Pair</th>
                        <th>Markup (%)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $rate): ?>
                    <tr>
                        <form action="fx_rates.php" method="POST">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="rate_id" value="<?php echo $rate['id']; ?>">
                            <td><?php echo htmlspecialchars($rate['currency_pair']); ?></td>
                            <td>
                                <input type="number" step="0.01" name="markup" class="form-control" value="<?php echo htmlspecialchars($rate['markup_percentage']); ?>" required>
                            </td>
                            <td>
                                <button type="submit" name="update_rate" class="btn btn-sm btn-primary">Update</button>
                                <button type="submit" name="delete_rate" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php require_once '../includes/admin_footer.php'; ?>
