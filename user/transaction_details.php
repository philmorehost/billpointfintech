<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id) {
    header("Location: transactions.php");
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$transaction_id, $_SESSION['user_id']]);
$transaction = $stmt->fetch();

if (!$transaction) {
    // Transaction not found or doesn't belong to the user
    $_SESSION['flash_message'] = "Transaction not found.";
    header("Location: transactions.php");
    exit;
}

include '../includes/header.php';
?>
<style>
    .receipt-container {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background-color: #fff;
        font-family: Arial, sans-serif;
    }
    .receipt-header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }
    .receipt-header h1 {
        margin: 0;
        font-size: 28px;
        color: #333;
    }
    .receipt-details {
        margin-bottom: 20px;
    }
    .receipt-details p {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        font-size: 16px;
    }
    .receipt-details p strong {
        color: #555;
    }
    .receipt-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 10px;
        border-top: 2px solid #eee;
    }
    .print-btn {
        background-color: #4f46e5;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    @media print {
        body * { visibility: hidden; }
        .receipt-container, .receipt-container * { visibility: visible; }
        .receipt-container { position: absolute; left: 0; top: 0; width: 100%; }
        .print-btn, .footer-nav, .airtime-header { display: none; }
        @page { size: auto; margin: 0; }
    }
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="transactions.php" class="back-btn">&#8592;</a>
        <span class="title">Transaction Details</span>
    </div>
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <h1>Transaction Receipt</h1>
        </div>
        <div class="receipt-details">
            <p><strong>Transaction ID:</strong> <span><?php echo htmlspecialchars($transaction['reference']); ?></span></p>
            <p><strong>Date:</strong> <span><?php echo date("d M, Y g:ia", strtotime($transaction['created_at'])); ?></span></p>
            <p><strong>Service:</strong> <span><?php echo htmlspecialchars(ucfirst($transaction['service'])); ?></span></p>
            <p><strong>Description:</strong> <span><?php echo htmlspecialchars($transaction['description']); ?></span></p>
            <p><strong>Amount:</strong> <span>&#8358;<?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?></span></p>
            <p><strong>Status:</strong> <span style="font-weight: bold; color: <?php echo $transaction['status'] === 'success' ? 'green' : 'red'; ?>;"><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></span></p>
        </div>
        <div class="receipt-footer">
            <p>Thank you for your patronage!</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="print-btn">Print Receipt</button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
