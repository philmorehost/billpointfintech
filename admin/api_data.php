<?php
$page_title = 'Data API Manager';
require_once 'includes/header.php';

// Fetch API providers and current data pricing
$stmt = $pdo->query("SELECT * FROM api_providers");
$providers = $stmt->fetchAll();

// Fetch all data plans with their provider information
$stmt = $pdo->query("
    SELECT dp.*, ap.name as provider_name
    FROM data_pricing dp
    JOIN api_providers ap ON dp.provider_id = ap.id
    ORDER BY dp.network, dp.data_type, dp.price_agent
");
$data_products = $stmt->fetchAll();

$datagifting_provider = array_values(array_filter($providers, fn($p) => $p['name'] === 'Datagifting'))[0] ?? null;

?>

<div class="container-fluid">
    <!-- API SETTING -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">API Setting</h3>
        </div>
        <div class="card-body">
            <form action="api_data_handler.php" method="POST">
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

    <!-- PRODUCT INSTALLATION (From Datagifting API) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Install Data Plans from Provider</h3>
        </div>
        <div class="card-body">
            <form action="api_data_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="install_data_products">
                <div class="form-group">
                    <label>Select Provider to Install From</label>
                    <select class="form-control" name="provider_id">
                        <?php foreach($providers as $provider): ?>
                            <option value="<?php echo $provider['id']; ?>"><?php echo htmlspecialchars($provider['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p>This will fetch the latest data plans from the selected provider and install them into your database. Existing plans with the same network and plan ID will be updated.</p>
                <button type="submit" class="btn btn-info" onclick="return confirm('Are you sure you want to install/update data plans? This may overwrite existing settings.');">Fetch and Install Data Plans</button>
            </form>
        </div>
    </div>


    <!-- INSTALLED DATA STATUS & PRICING -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Installed Data Plans & Pricing</h3></div>
        <div class="card-body">
            <form action="api_data_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_data_prices">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Network</th>
                                <th>Plan Name / Size</th>
                                <th>Type</th>
                                <th>Provider Price (₦)</th>
                                <th>Smart User (₦)</th>
                                <th>Agent (₦)</th>
                                <th>API (₦)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(strtoupper($product['network'])); ?></td>
                                <td><?php echo htmlspecialchars($product['plan_name']); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($product['data_type'])); ?></td>
                                <td><?php echo htmlspecialchars($product['provider_price']); ?></td>

                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][smart_earner]" value="<?php echo htmlspecialchars($product['price_smart_earner']); ?>"></td>
                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][agent_vendor]" value="<?php echo htmlspecialchars($product['price_agent']); ?>"></td>
                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][api_vendor]" value="<?php echo htmlspecialchars($product['price_api']); ?>"></td>

                                <td><span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $product['is_active'] ? 'Enabled' : 'Disabled'; ?></span></td>
                                <td>
                                    <!-- Add enable/disable/delete buttons here -->
                                    <button type="button" class="btn btn-sm btn-warning">Off</button>
                                     <button type="button" class="btn btn-sm btn-info">On</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">Update All Prices</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
