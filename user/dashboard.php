<?php
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM services WHERE is_available = 1");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
        <div class="wallet-balance">
            <p>Wallet Balance</p>
            <h3>&#8358;<?php echo number_format($user['wallet_balance'], 2); ?></h3>
        </div>
    </div>

    <div class="services-grid">
        <h3>Our Services</h3>
        <div class="grid-container">
            <?php foreach ($services as $service):
                $service_name_parts = explode(' ', $service['name']);
                $url = strtolower(end($service_name_parts)) . '.php';
                $icon = strtolower(end($service_name_parts));
            ?>
                <a href="<?php echo $url; ?>" class="service-card">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                    <p><?php echo $service['name']; ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
