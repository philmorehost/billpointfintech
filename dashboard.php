<?php
$page_title = 'Dashboard';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN'");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();

// Fetch user's first name for a personalized greeting
$name_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$name_stmt->execute([$user_id]);
$full_name = $name_stmt->fetchColumn();
$first_name = explode(' ', $full_name)[0];

include 'includes/header.php'; // Will be updated later
?>

<div class="container dashboard-container">

    <!-- Dashboard Header -->
    <div class="page-header">
        <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h1>
        <p>What would you like to do today?</p>
    </div>

    <!-- Top Balance Card -->
    <div class="balance-card">
        <span class="balance-label">NGN Wallet Balance</span>
        <span class="balance-amount">₦<?php echo number_format($balance, 2); ?></span>
        <div class="balance-actions">
            <a href="fund_wallet.php" class="btn">Fund Wallet</a>
            <a href="history.php" class="btn">View History</a>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="services-grid">
        <a href="airtime.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
            <span>Airtime</span>
        </a>
        <a href="data.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.042A5.25 5.25 0 0112 12B5.25 5.25 0 0115.712 8.958m-7.424 6.084a5.25 5.25 0 007.424 0M12 6a5.25 5.25 0 00-5.25 5.25v1.5a5.25 5.25 0 005.25 5.25h0a5.25 5.25 0 005.25-5.25v-1.5a5.25 5.25 0 00-5.25-5.25h0z" /></svg>
            <span>Data</span>
        </a>
        <a href="p2p_transfer.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
            <span>Transfer</span>
        </a>
        <a href="cable.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3.75v3.75m-3.75-3.75v3.75m-3.75-3.75V15m11.25-3.75V15m0 0V8.25m0 0H8.25m11.25 0v-1.5m0 1.5H12m-3.75 0H8.25m9-3.75H12m0 0H8.25" /></svg>
            <span>Cable TV</span>
        </a>
        <a href="electricity.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h.5a.75.75 0 00.75-.75c0-.231-.035-.454-.1-.664M6.75 8.25l.223.447A23.937 23.937 0 0112 12.75c1.16-1.522 2.274-3.088 3.027-4.053l.223-.447m1.5 0A17.93 17.93 0 0112 12.75c-3.1 0-5.873.348-8.25 1.002m16.5 0a17.93 17.93 0 00-8.25-1.002c-3.1 0-5.873.348-8.25 1.002m0 0A23.94 23.94 0 0112 21c4.346 0 8.24-1.63 10.5-4.248m-18.75 0a23.94 23.94 0 001.344 1.252A23.938 23.938 0 0012 21c1.16-1.522 2.274-3.088 3.027-4.053m0 0a17.93 17.93 0 013.75 0" /></svg>
            <span>Electricity</span>
        </a>
        <a href="savings.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 3a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 12m15 0a2.25 2.25 0 012.25 2.25m0 0a2.25 2.25 0 01-2.25 2.25m-15 0a2.25 2.25 0 01-2.25-2.25m0 0A2.25 2.25 0 015.25 12" /></svg>
            <span>Savings</span>
        </a>
        <a href="loans.php" class="service-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.75A.75.75 0 013 4.5h.75m0 0h.75A.75.75 0 015.25 6v.75m0 0v.75A.75.75 0 014.5 8.25h-.75m0 0H3.75A.75.75 0 013 7.5v-.75m1.5 0v.75A.75.75 0 013.75 9h-.75m0 0h-.75A.75.75 0 012.25 8.25v-.75m1.5 0v.75a.75.75 0 01-.75.75h-.75m0 0H2.25m12.75 0h.75a.75.75 0 01.75.75v.75m0 0v.75a.75.75 0 01-.75.75h-.75m0 0h-.75a.75.75 0 01-.75-.75v-.75m.75 .75v-.75a.75.75 0 01.75-.75h.75m0 0h.75a.75.75 0 01.75.75v.75m0 0v.75a.75.75 0 01-.75.75h-.75m0 0h-.75a.75.75 0 01-.75-.75v-.75" /></svg>
            <span>Loans</span>
        </a>
        <button type="button" class="service-item more-btn" onclick="openServicesModal()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            <span>More</span>
        </button>
    </div>
</div>

<!-- "More" Services Modal -->
<div id="more-services-modal" class="services-modal" style="display:none; /* Managed by JS */">
    <div class="modal-content">
        <div class="modal-header">
            <h2>All Services</h2>
            <button type="button" class="close-btn" onclick="closeServicesModal()">&times;</button>
        </div>
        <div class="modal-body services-grid">
            <!-- Icons included here as well for consistency -->
            <a href="airtime.php" class="service-item"><span>Airtime</span></a>
            <a href="international_airtime.php" class="service-item"><span>Int'l Airtime</span></a>
            <a href="bulk_vtu.php" class="service-item"><span>Bulk VTU</span></a>
            <a href="data.php" class="service-item"><span>Data</span></a>
            <a href="cable.php" class="service-item"><span>Cable TV</span></a>
            <a href="electricity.php" class="service-item"><span>Electricity</span></a>
            <a href="p2p_transfer.php" class="service-item"><span>Transfer</span></a>
            <a href="savings.php" class="service-item"><span>Savings</span></a>
            <a href="invoices.php" class="service-item"><span>Invoicing</span></a>
            <a href="loans.php" class="service-item"><span>Loans</span></a>
            <a href="exam.php" class="service-item"><span>Exam PINs</span></a>
            <a href="exchange.php" class="service-item"><span>Exchange</span></a>
            <a href="global_transfer.php" class="service-item"><span>Global Transfer</span></a>
            <a href="api_access.php" class="service-item"><span>API Access</span></a>
        </div>
    </div>
</div>

<script>
    // Simple modal logic
    const modal = document.getElementById('more-services-modal');
    function openServicesModal() { modal.style.display = 'flex'; }
    function closeServicesModal() { modal.style.display = 'none'; }
    // Close modal if clicking outside the content
    window.onclick = function(event) {
        if (event.target == modal) {
            closeServicesModal();
        }
    }
</script>

<?php include 'includes/footer.php'; // Will contain the new footer nav ?>
