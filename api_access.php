<?php
$page_title = 'API Access';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

// Placeholder logic - this would come from the database
$api_key = null;
$request_status = 'not_requested';

try {
    // Check for an existing API key
    $stmt = $pdo->prepare("SELECT api_key FROM api_keys WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result) {
        $api_key = $result['api_key'];
    } else {
        // Check for a pending request
        $req_stmt = $pdo->prepare("SELECT status FROM api_key_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $req_stmt->execute([$_SESSION['user_id']]);
        $req_result = $req_stmt->fetch();
        if ($req_result) {
            $request_status = $req_result['status'];
        }
    }
} catch (PDOException $e) {
    // Gracefully handle missing tables
    set_flash_message('error', 'The API Access system is not yet available. Please check back later.');
    $request_status = 'unavailable';
}


include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="page-header">
        <h1>API Access Management</h1>
        <p>Integrate your applications with our services.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="content-box">
        <?php if ($request_status === 'unavailable'): ?>
            <div class="alert alert-info">The API system is currently undergoing maintenance.</div>
        <?php elseif ($api_key): ?>
            <h2>Your API Key</h2>
            <p>Use the key below to access our API. Keep it confidential!</p>
            <div class="form-group">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($api_key); ?>" readonly>
            </div>
            <form action="transaction_handler.php" method="POST" onsubmit="return confirm('Are you sure you want to generate a new key? Your old key will be invalidated immediately.');">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="regenerate_api_key">
                <button type="submit" class="btn btn-warning">Regenerate Key</button>
            </form>
            <hr>
            <h3>API Documentation</h3>
            <p>Base URL: <code><?php echo get_base_url(); ?>api.php</code></p>
            <p>Please refer to our full API documentation for endpoints and usage examples.</p>

        <?php elseif ($request_status === 'pending'): ?>
            <h2>Request Pending</h2>
            <p>Your request for API access is currently under review by our team. You will be notified once it has been processed.</p>

        <?php elseif ($request_status === 'rejected'): ?>
             <h2>Request Rejected</h2>
            <p>Unfortunately, your previous request for API access was not approved. If you believe this was in error, please contact support.</p>
             <a href="support.php" class="btn btn-secondary">Contact Support</a>

        <?php else: // 'not_requested' ?>
            <h2>Request API Access</h2>
            <p>To get your API key, please submit a request explaining how you intend to use our API.</p>
            <form action="transaction_handler.php" method="POST">
                 <?php csrf_field(); ?>
                <input type="hidden" name="action" value="request_api_key">
                <div class="form-group">
                    <label for="reason">Reason for Request</label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
