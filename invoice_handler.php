<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
        exit();
    }

    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    if ($action === 'create_estimate') {
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $items = $_POST['items'];

        if (empty($customer_name) || empty($customer_email) || empty($items)) {
            set_flash_message('error', 'Customer details and at least one item are required.');
            header('Location: create_estimate.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += (int)$item['quantity'] * (float)$item['unit_price'];
            }

            // Create the estimate
            $stmt = $pdo->prepare("INSERT INTO estimates (user_id, customer_name, customer_email, total_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $customer_name, $customer_email, $total_amount]);
            $estimate_id = $pdo->lastInsertId();

            // Add estimate items
            $item_stmt = $pdo->prepare("INSERT INTO estimate_items (estimate_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $item_stmt->execute([$estimate_id, $item['description'], (int)$item['quantity'], (float)$item['unit_price']]);
            }

            $pdo->commit();
            set_flash_message('success', 'Estimate created successfully.');
            header('Location: estimates.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash_message('error', 'Error creating estimate: ' . $e->getMessage());
            header('Location: create_estimate.php');
            exit();
        }
    }

    if ($action === 'convert_to_invoice') {
        $estimate_id = (int)$_POST['estimate_id'];

        try {
            $pdo->beginTransaction();

            // 1. Fetch the estimate and its items
            $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND user_id = ?");
            $stmt->execute([$estimate_id, $user_id]);
            $estimate = $stmt->fetch();

            $items_stmt = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id = ?");
            $items_stmt->execute([$estimate_id]);
            $items = $items_stmt->fetchAll();

            if (!$estimate || empty($items)) {
                throw new Exception("Estimate not found or has no items.");
            }

            // 2. Create the new invoice
            $inv_stmt = $pdo->prepare("INSERT INTO invoices (user_id, estimate_id, customer_name, customer_email, total_amount, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $due_date = date('Y-m-d', strtotime('+30 days')); // Example: Due in 30 days
            $inv_stmt->execute([$user_id, $estimate_id, $estimate['customer_name'], $estimate['customer_email'], $estimate['total_amount'], $due_date]);
            $invoice_id = $pdo->lastInsertId();

            // 3. Copy items to the new invoice
            $inv_item_stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $inv_item_stmt->execute([$invoice_id, $item['description'], $item['quantity'], $item['unit_price']]);
            }

            // 4. Update estimate status
            $update_est_stmt = $pdo->prepare("UPDATE estimates SET status = 'accepted' WHERE id = ?");
            $update_est_stmt->execute([$estimate_id]);

            $pdo->commit();
            set_flash_message('success', 'Estimate successfully converted to an invoice.');
            header('Location: invoices.php'); // Redirect to invoices list
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash_message('error', 'Error converting to invoice: ' . $e->getMessage());
            header('Location: estimates.php');
            exit();
        }
    }
}

header('Location: dashboard.php');
exit();
