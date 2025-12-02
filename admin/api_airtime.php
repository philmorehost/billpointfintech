<?php
$page_title = 'Airtime API Manager';
require_once 'includes/header.php';

// Fetch API providers and current airtime pricing
$stmt = $pdo->query("SELECT * FROM api_providers");
$providers = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, ap.name as provider_name FROM airtime_pricing p JOIN api_providers ap ON p.provider_id = ap.id");
$airtime_products = $stmt->fetchAll();

$datagifting_provider = array_values(array_filter($providers, fn($p) => $p['name'] === 'Datagifting'))[0] ?? null;

?>

<div class="container-fluid">
    <!-- API SETTING -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">API Setting</h3>
        </div>
        <div class="card-body">
            <form action="api_airtime_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_api_key">
                <div class="form-group">
                    <label>Choose API</label>
                    <select class="form-control" name="provider_id">
                        <?php foreach($providers as $provider): ?>
                            <option value="<?php echo $provider['id']; ?>" <?php if($provider['name'] === 'Datagifting') echo 'selected'; ?>><?php echo htmlspecialchars($provider['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>API Key</label>
                    <input type="password" name="api_key" class="form-control" value="<?php echo htmlspecialchars($datagifting_provider['api_key'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update Key</button>
            </form>
        </div>
    </div>

    <!-- PRODUCT INSTALLATION -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Product Installation</h3></div>
        <div class="card-body">
             <!-- Simplified for now, will add full logic later -->
             <p>Product installation section placeholder.</p>
        </div>
    </div>


    <!-- INSTALLED AIRTIME STATUS -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Installed Airtime Status</h3></div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>API Route</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($airtime_products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(strtoupper($product['network'])); ?></td>
                        <td><?php echo htmlspecialchars($product['provider_name']); ?></td>
                        <td><span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $product['is_active'] ? 'Enabled' : 'Disabled'; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning">Disable</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- AIRTIME DISCOUNT -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Airtime Discount</h3></div>
        <div class="card-body">
            <form action="api_airtime_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_discounts">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Smart Earner (%)</th>
                            <th>Agent Vendor (%)</th>
                            <th>API Vendor (%)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($airtime_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(strtoupper($product['network'])); ?></td>
                            <td><input type="number" step="0.01" class="form-control" name="discounts[<?php echo $product['id']; ?>][smart_earner_discount]" value="<?php echo htmlspecialchars($product['smart_earner_discount']); ?>"></td>
                            <td><input type="number" step="0.01" class="form-control" name="discounts[<?php echo $product['id']; ?>][agent_vendor_discount]" value="<?php echo htmlspecialchars($product['agent_vendor_discount']); ?>"></td>
                            <td><input type="number" step="0.01" class="form-control" name="discounts[<?php echo $product['id']; ?>][api_vendor_discount]" value="<?php echo htmlspecialchars($product['api_vendor_discount']); ?>"></td>
                            <td><span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $product['is_active'] ? 'Enabled' : 'Disabled'; ?></span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning">Disable</button>
                                <button type="button" class="btn btn-sm btn-info">Enable</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Update Prices</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
