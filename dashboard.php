<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/database.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN'");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Balance Card -->
        <div class="balance-card">
            <h3>NGN Balance</h3>
            <p>₦<?php echo number_format($balance, 2); ?></p>
        </div>

        <!-- Services Grid -->
        <div class="services-grid">
            <a href="airtime.php" class="service-item" style="text-decoration: none; color: inherit;">Airtime</a>
            <a href="data.php" class="service-item" style="text-decoration: none; color: inherit;">Data</a>
            <a href="cable.php" class="service-item" style="text-decoration: none; color: inherit;">Cable TV</a>
            <div class="service-item">Transfer</div>
            <div class="service-item">Savings</div>
            <div class="service-item">Loan</div>
            <div class="service-item">Crypto</div>
            <div class="service-item more-btn" onclick="openModal()">More</div>
        </div>
    </div>

    <!-- Footer Navigation -->
    <nav class="footer-nav">
        <a href="dashboard.php">Home</a>
        <a href="wallet.php">Wallet</a>
        <a href="history.php">History</a>
        <a href="profile.php">Profile</a>
    </nav>

    <!-- "More" Services Modal -->
    <div id="more-services-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>All Services</h2>
            <!-- List all services here -->
        </div>
    </div>

    <script>
        const modal = document.getElementById('more-services-modal');
        function openModal() { modal.style.display = 'block'; }
        function closeModal() { modal.style.display = 'none'; }
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
