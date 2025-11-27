<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
// ... (fetch user and services)
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM services WHERE is_available = 1 ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for daily bonus eligibility
$stmt = $pdo->prepare("SELECT last_claimed_date FROM daily_rewards WHERE user_id = ?");
$stmt->execute([$user_id]);
$last_claimed = $stmt->fetchColumn();
$can_claim_bonus = ($last_claimed !== date('Y-m-d'));

$primary_services = ['airtime', 'data', 'electricity'];

include '../includes/header.php';
?>

<div class="top-card">
    <div class="balance-display">
        <p>Total Balance</p>
        <h1>&#8358;<?php echo htmlspecialchars(number_format($user['wallet_balance'], 2)); ?></h1>
    </div>
    <div class="top-actions">
        <!-- ... (top actions) ... -->
    </div>
</div>

<div class="container app-view">
    <?php if ($can_claim_bonus): ?>
        <div class="bonus-banner" id="bonus-banner">
            <p>You have a daily login bonus to claim!</p>
            <button id="claim-bonus-btn">Claim Now</button>
        </div>
    <?php endif; ?>

    <h3>Services</h3>
    <div class="services-grid-app">
        <!-- ... (services grid) ... -->
    </div>
</div>

<!-- ... (modal and footer) ... -->

<?php include '../includes/footer.php'; ?>
<style>.bonus-banner { background: var(--app-secondary-color); color: white; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; }</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (modal script)

    const claimBtn = document.getElementById('claim-bonus-btn');
    if (claimBtn) {
        claimBtn.addEventListener('click', async function() {
            this.disabled = true;
            this.textContent = 'Claiming...';

            const formData = new FormData();
            formData.append('action', 'claim_daily_bonus');

            try {
                const response = await fetch('ajax_gamification_handler.php', { method: 'POST', body: formData });
                const data = await response.json();

                alert(data.message); // Simple alert for now
                if (data.status === 'success') {
                    document.getElementById('bonus-banner').style.display = 'none';
                    // You might want to update the balance on the page too
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                this.disabled = false;
                this.textContent = 'Claim Now';
            }
        });
    }
});
</script>
