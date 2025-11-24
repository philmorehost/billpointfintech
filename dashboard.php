<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

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
            <a href="electricity.php" class="service-item" style="text-decoration: none; color: inherit;">Electricity</a>
            <a href="exam.php" class="service-item" style="text-decoration: none; color: inherit;">Exam PINs</a>
            <div class="service-item">Loan</div>
            <div class="service-item">Crypto</div>
            <div class="service-item more-btn" onclick="openModal()">More</div>
        </div>
    </div>

    <?php include 'includes/footer_nav.php'; ?>

    <!-- "More" Services Modal -->
    <div id="more-services-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>All Services</h2>
            <div class="services-grid">
                <div class="service-item">Airtime</div>
                <div class="service-item">Data</div>
                <div class="service-item">Cable TV</div>
                <div class="service-item">Electricity</div>
                <div class="service-item">Transfer</div>
                <div class="service-item">Savings</div>
                <div class="service-item">Loan</div>
                <div class="service-item">Crypto</div>
                <div class="service-item">Exam PINs</div>
                <div class="service-item">Recharge Card</div>
                <div class="service-item">Bulk SMS</div>
            </div>
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
