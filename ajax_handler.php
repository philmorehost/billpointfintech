<?php
require_once 'includes/bootstrap.php';
require_once 'core/datagifting_api.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_iuc') {
    if (!validate_csrf_token()) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed.']);
        exit();
    }
    // AJAX IUC Verification
    header('Content-Type: application/json');
    $provider = $_POST['cable_provider'];
    $iuc = $_POST['iuc_number'];

    $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
    $api_key = $settings_stmt->fetchColumn();
    $datagifting = new DatagiftingAPI($api_key);
    $response = $datagifting->verify_cable_iuc($provider, $iuc);

    if ($response && $response['status'] === 'success') {
        echo json_encode(['status' => 'success', 'customer_name' => $response['desc']]);
    } else {
        $message = $response['desc'] ?? 'Verification failed.';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_cable_plans') {
    require_once 'includes/auth_check.php';
    // AJAX Get Cable Plans
    header('Content-Type: application/json');
    $provider = $_GET['provider'];
    $stmt = $pdo->prepare("SELECT * FROM cable_plans WHERE cable_provider = ? ORDER BY price");
    $stmt->execute([$provider]);
    echo json_encode($stmt->fetchAll());
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_meter') {
    if (!validate_csrf_token()) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed.']);
        exit();
    }
    // AJAX Meter Verification
    header('Content-Type: application/json');
    $provider = $_POST['provider'];
    $meter_number = $_POST['meter_number'];
    $type = $_POST['type'];

    $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
    $api_key = $settings_stmt->fetchColumn();
    $datagifting = new DatagiftingAPI($api_key);
    $response = $datagifting->verify_meter_number($provider, $meter_number, $type);

    if ($response && $response['status'] === 'success') {
        echo json_encode(['status' => 'success', 'customer_name' => $response['desc']]);
    } else {
        $message = $response['desc'] ?? 'Verification failed.';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
    exit();
}
