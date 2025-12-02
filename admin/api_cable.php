<?php
$page_title = 'Cable TV API Manager';
require_once 'includes/header.php';

// Fetch API providers and current cable pricing
$stmt = $pdo->query("SELECT * FROM api_providers");
$providers = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT cp.*, ap.name as provider_name
    FROM cable_pricing cp
    JOIN api_providers ap ON cp.provider_id = ap.id
    ORDER BY cp.provider_name, cp.cable_type
");
$cable_products = $stmt->fetchAll();

$datagifting_provider = array_values(array_filter($providers, fn($p) => $p['name'] === 'Datagifting'))[0] ?? null;
?>

<div class="container-fluid">
    <!-- API SETTING -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">API Setting</h3></div>
        <div class="card-body">
            <form action="api_cable_handler.php" method="POST">
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
        <div class="card-header"><h3 class="card-title">Install Cable Plans from Provider</h3></div>
        <div class="card-body">
            <!-- Placeholder for future implementation -->
            <p>Feature to fetch and install cable plans automatically is under development.</p>
        </div>
    </div>

    <!-- INSTALLED CABLE PLANS & PRICING -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Installed Cable Plans & Pricing</h3></div>
        <div class="card-body">
            <form action="api_cable_handler.php" method="POST">
                 <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="update_cable_prices">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cable TV</th>
                                <th>Package Name</th>
                                <th>Provider Price (₦)</th>
                                <th>Smart User (₦)</th>
                                <th>Agent (₦)</th>
                                <th>API (₦)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cable_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(strtoupper($product['cable_type'])); ?></td>
                                <td><?php echo htmlspecialchars($product['package_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['provider_price']); ?></td>

                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][smart_earner]" value="<?php echo htmlspecialchars($product['price_smart_earner']); ?>"></td>
                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][agent_vendor]" value="<?php echo htmlspecialchars($product['price_agent']); ?>"></td>
                                <td><input type="number" step="0.01" class="form-control" name="prices[<?php echo $product['id']; ?>][api_vendor]" value="<?php echo htmlspecialchars($product['price_api']); ?>"></td>

                                <td><span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $product['is_active'] ? 'Enabled' : 'Disabled'; ?></span></td>
                                <td>
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
