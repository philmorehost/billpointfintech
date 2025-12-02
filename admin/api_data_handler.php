<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

if (!validate_csrf_token()) {
    $_SESSION['error_message'] = 'Invalid request. Please try again.';
    redirect('api_data.php');
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

    } elseif ($action === 'update_data_prices') {
        $prices = $_POST['prices'] ?? [];

        if (empty($prices)) {
            throw new Exception('No price data submitted.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE data_pricing
            SET price_smart_earner = ?, price_agent = ?, price_api = ?
            WHERE id = ?
        ");

        foreach ($prices as $product_id => $values) {
            $smart_earner = (float)($values['smart_earner'] ?? 0);
            $agent_vendor = (float)($values['agent_vendor'] ?? 0);
            $api_vendor = (float)($values['api_vendor'] ?? 0);

            $stmt->execute([$smart_earner, $agent_vendor, $api_vendor, $product_id]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Data plan prices updated successfully.';

    } elseif ($action === 'install_data_products') {
        // Placeholder for fetching from Datagifting API and populating the table.
        // This is a complex operation that will be implemented separately.
        // For now, we just show a success message.
        $_SESSION['success_message'] = 'Product installation feature is under development. No changes were made.';

    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

redirect('api_data.php');
