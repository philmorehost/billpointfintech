<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

if (!validate_csrf_token()) {
    $_SESSION['error_message'] = 'Invalid request. Please try again.';
    redirect('api_electricity.php');
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_api_key') {
        $provider_id = (int) ($_POST['provider_id'] ?? 0);
        $api_key = trim($_POST['api_key'] ?? '');

        if (empty($provider_id)) {
            throw new Exception('Please select an API provider.');
        }

        $stmt = $pdo->prepare("UPDATE api_providers SET api_key = ? WHERE id = ?");
        $stmt->execute([$api_key, $provider_id]);

        $_SESSION['success_message'] = 'API Key updated successfully.';

    } elseif ($action === 'update_electricity_discounts') {
        $discounts = $_POST['discounts'] ?? [];

        if (empty($discounts)) {
            throw new Exception('No discount data submitted.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE electricity_pricing
            SET discount_smart_earner = ?, discount_agent = ?, discount_api = ?
            WHERE id = ?
        ");

        foreach ($discounts as $product_id => $values) {
            $smart_earner = (float)($values['smart_earner'] ?? 0);
            $agent_vendor = (float)($values['agent_vendor'] ?? 0);
            $api_vendor = (float)($values['api_vendor'] ?? 0);

            $stmt->execute([$smart_earner, $agent_vendor, $api_vendor, $product_id]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Electricity provider discounts updated successfully.';

    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

redirect('api_electricity.php');
