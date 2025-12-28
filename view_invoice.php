<?php
$page_title = 'View Invoice';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header('Location: invoices.php');
    exit();
}

// Fetch invoice and its items
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $_SESSION['user_id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    set_flash_message('error', 'Invoice not found.');
    header('Location: invoices.php');
    exit();
}

$items_stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$items_stmt->execute([$invoice_id]);
$items = $items_stmt->fetchAll();
$csrf_token = generate_csrf_token();

include 'includes/header.php';
?>
<div class="container">
    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <h1>Invoice</h1>
                <p>Invoice #: <?php echo $invoice['id']; ?></p>
                <p>Date: <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                <p>Due Date: <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
            </div>
            <div class="invoice-status status-<?php echo strtolower($invoice['status']); ?>">
                <?php echo strtoupper($invoice['status']); ?>
            </div>
        </div>
        <hr>
        <div class="customer-info">
            <strong>Bill To:</strong><br>
            <?php echo htmlspecialchars($invoice['customer_name']); ?><br>
            <?php echo htmlspecialchars($invoice['customer_email']); ?>
        </div>
        <table class="invoice-items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₦<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>₦<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="total-label">Grand Total</td>
                    <td class="total-amount">₦<?php echo number_format($invoice['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
        <?php if ($invoice['status'] === 'unpaid'): ?>
        <div class="payment-section">
            <form action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="pay_invoice">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="btn">Pay Now with NGN Wallet</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<style>
.invoice-box { border: 1px solid #eee; padding: 20px; max-width: 800px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,.15); }
.invoice-header { display: flex; justify-content: space-between; align-items: flex-start; }
.invoice-status { font-size: 1.2em; font-weight: bold; padding: 5px 10px; border-radius: 5px; color: white; }
.customer-info { margin: 20px 0; }
.invoice-items { width: 100%; border-collapse: collapse; margin-top: 20px; }
.invoice-items th, .invoice-items td { border: 1px solid #eee; padding: 8px; text-align: left; }
.invoice-items th { background: #f9f9f9; }
.invoice-items tfoot .total-label { text-align: right; font-weight: bold; }
.invoice-items tfoot .total-amount { font-weight: bold; }
.payment-section { margin-top: 20px; text-align: right; }
</style>
<?php include 'includes/footer.php'; ?>
