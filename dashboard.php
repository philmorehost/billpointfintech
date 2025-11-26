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
            <a href="fund_wallet.php" class="btn btn-sm">Fund Wallet</a>
        </div>

        <!-- Services Grid -->
        <div class="services-grid">
            <?php
            $services_stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
            $services = $services_stmt->fetchAll();
            $displayed_services = array_slice($services, 0, 11);
            foreach ($displayed_services as $service) {
                echo '<a href="' . htmlspecialchars($service['url']) . '" class="service-item" style="text-decoration: none; color: inherit;">' . htmlspecialchars($service['name']) . '</a>';
            }
            ?>
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
                <?php foreach ($services as $service): ?>
                    <a href="<?php echo htmlspecialchars($service['url']); ?>" class="service-item" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($service['name']); ?></a>
                <?php endforeach; ?>
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
