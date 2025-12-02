<?php
$page_title = 'Dashboard';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN'");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();
?>

<div class="dashboard-container">
    <!-- Top Balance Card -->
    <div class="balance-card-container">
        <div class="balance-card">
            <span class="balance-label">NGN Wallet Balance</span>
            <span class="balance-amount">₦<?php echo number_format($balance, 2); ?></span>
            <div class="balance-actions">
                <a href="fund_wallet.php" class="btn btn-sm btn-light">Fund Wallet</a>
                <a href="history.php" class="btn btn-sm btn-light">History</a>
            </div>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="services-grid">
        <a href="airtime.php" class="service-item">Airtime</a>
        <a href="data.php" class="service-item">Data</a>
        <a href="cable.php" class="service-item">Cable TV</a>
        <a href="electricity.php" class="service-item">Electricity</a>
        <a href="p2p_transfer.php" class="service-item">Transfer</a>
        <a href="savings.php" class="service-item">Savings</a>
        <a href="loans.php" class="service-item">Loans</a>
        <button type="button" class="service-item more-btn" onclick="openServicesModal()">More</button>
    </div>
</div>

<!-- "More" Services Modal -->
<div id="more-services-modal" class="services-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>All Services</h2>
            <button type="button" class="close-btn" onclick="closeServicesModal()">&times;</button>
        </div>
        <div class="modal-body">
            <a href="airtime.php" class="service-item">Airtime</a>
            <a href="international_airtime.php" class="service-item">Int'l Airtime</a>
            <a href="bulk_vtu.php" class="service-item">Bulk VTU</a>
            <a href="data.php" class="service-item">Data</a>
            <a href="cable.php" class="service-item">Cable TV</a>
            <a href="electricity.php" class="service-item">Electricity</a>
            <a href="p2p_transfer.php" class="service-item">Transfer</a>
            <a href="savings.php" class="service-item">Savings</a>
            <a href="invoices.php" class="service-item">Invoicing</a>
            <a href="loans.php" class="service-item">Loans</a>
            <a href="exam.php" class="service-item">Exam PINs</a>
            <a href="exchange.php" class="service-item">Exchange</a>
            <a href="global_transfer.php" class="service-item">Global Transfer</a>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('more-services-modal');
    function openServicesModal() { modal.style.display = 'flex'; }
    function closeServicesModal() { modal.style.display = 'none'; }
</script>

<?php include 'includes/footer.php'; ?>
