<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_bulk_airtime') {
    if (!validate_csrf_token() || empty($_FILES['csv_file'])) {
        set_flash_message('error', 'Invalid request or no file uploaded.');
        header('Location: bulk_vtu.php');
        exit();
    }

    $vtu_type = $_POST['vtu_type'];
    $file = $_FILES['csv_file'];
    $user_id = $_SESSION['user_id'];

    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK || $file['type'] !== 'text/csv') {
        set_flash_message('error', 'File upload error or invalid file type. Please upload a CSV.');
        header('Location: bulk_vtu.php');
        exit();
    }

    $items = [];
    $total_cost = 0;
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) == 2) {
                $phone = trim($data[0]);
                $amount = (float)trim($data[1]);
                if (!empty($phone) && $amount > 0) {
                    $items[] = ['phone' => $phone, 'amount' => $amount];
                    $total_cost += $amount;
                }
            }
        }
        fclose($handle);
    }

    if (empty($items)) {
        set_flash_message('error', 'CSV file is empty or formatted incorrectly.');
        header('Location: bulk_vtu.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Check user balance
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN'");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetchColumn();

        if ($balance < $total_cost) {
            throw new Exception("Insufficient funds to process the entire batch. Required: ₦{$total_cost}, Available: ₦{$balance}");
        }

        // Create the bulk job
        $job_stmt = $pdo->prepare("INSERT INTO bulk_jobs (user_id, job_type, total_cost, status) VALUES (?, ?, ?, 'pending')");
        $job_stmt->execute([$user_id, "airtime_{$vtu_type}", $total_cost]);
        $job_id = $pdo->lastInsertId();

        // Add items to the job
        $item_stmt = $pdo->prepare("INSERT INTO bulk_job_items (job_id, target, amount) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $item_stmt->execute([$job_id, $item['phone'], $item['amount']]);
        }

        $pdo->commit();
        set_flash_message('success', 'Bulk job submitted successfully. It will be processed shortly.');
        header('Location: bulk_vtu.php'); // Redirect to a job status page in a real app
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash_message('error', 'Error submitting batch: ' . $e->getMessage());
        header('Location: bulk_vtu.php');
        exit();
    }
}

header('Location: bulk_vtu.php');
exit();
