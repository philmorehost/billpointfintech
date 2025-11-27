<?php
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM services WHERE is_available = 1 ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
        <div class="wallet-balance">
            <p>Wallet Balance</p>
            <h3>&#8358;<?php echo htmlspecialchars(number_format($user['wallet_balance'], 2)); ?></h3>
        </div>
    </div>

    <div class="services-grid">
        <h3>Our Services</h3>
        <div class="grid-container">
            <?php foreach ($services as $service): ?>
                <a href="<?php echo htmlspecialchars($service['slug']); ?>.php" class="service-card">
                    <i class="fas <?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                    <p><?php echo htmlspecialchars($service['name']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
