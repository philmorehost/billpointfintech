<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = db_connect();

// Fetch user's wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$wallet_balance = $stmt->fetchColumn();

// The form processing logic will be handled via AJAX (to be implemented)
// For now, this page is primarily for UI display.

// Define network providers
$networks = [
    ['name' => '9mobile', 'logo' => '../assets/images/9mobile.png', 'code' => '9MOBILE'],
    ['name' => 'Airtel', 'logo' => '../assets/images/airtel.png', 'code' => 'AIRTEL'],
    ['name' => 'Glo', 'logo' => '../assets/images/glo.png', 'code' => 'GLO'],
    ['name' => 'MTN', 'logo' => '../assets/images/mtn.png', 'code' => 'MTN'],
];

include '../includes/header.php'; // Use the standard header
?>

<style>
    /* Page specific styles for the new design */
    .app-view {
        background-color: #f5f5f5;
        min-height: 100vh;
        padding-bottom: 80px; /* Space for footer nav */
    }

    .airtime-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background-color: #fff;
        border-bottom: 1px solid #eee;
    }

    .airtime-header .back-btn {
        font-size: 24px;
        color: #333;
        text-decoration: none;
    }

    .airtime-header .title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
    }

    .airtime-header .next-btn {
        background-color: #d1d5db;
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: not-allowed; /* Disabled by default */
    }
    .airtime-header .next-btn.active {
        background-color: #4f46e5; /* Active color */
        cursor: pointer;
    }

    .wallet-balance-card {
        background-color: #fff;
        border-radius: 12px;
        padding: 15px;
        margin: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .wallet-balance-card .label {
        display: flex;
        align-items: center;
        font-size: 16px;
        color: #555;
    }

    .wallet-balance-card .label i {
        margin-right: 10px;
        font-size: 22px;
        color: #4f46e5;
    }

    .wallet-balance-card .amount {
        font-size: 18px;
        font-weight: 700;
        color: #e54646; /* Reddish color for balance */
    }

    .content-card {
        background-color: #fff;
        padding: 20px 15px;
        margin: 15px;
        border-radius: 12px;
    }

    .content-card .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }

    .network-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    .network-item {
        border: 2px solid #eee;
        border-radius: 10px;
        padding: 10px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .network-item img {
        max-width: 100%;
        height: 40px;
        object-fit: contain;
    }

    .network-item.selected {
        border-color: #4f46e5;
        box-shadow: 0 0 10px rgba(79, 70, 229, 0.3);
    }

    .phone-input-group {
        position: relative;
    }

    .phone-input-group input {
        width: 100%;
        padding: 15px 50px 15px 15px;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 16px;
        background-color: #f5f5f5;
    }

    .phone-input-group .icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 22px;
        color: #888;
        cursor: pointer;
    }

</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Airtime</span>
        <button id="next-btn" class="next-btn" disabled>Next</button>
    </div>

    <div class="wallet-balance-card">
        <div class="label">
            <i class="fas fa-wallet"></i>
            <span>Wallet Balance</span>
        </div>
        <div class="amount">
            ₦<?php echo number_format($wallet_balance, 2); ?>
        </div>
    </div>

    <div class="content-card">
        <form id="airtime-form" method="POST" action="airtime-confirm.php">
            <input type="hidden" name="network" id="selected-network">

            <div class="section-title">Select Network</div>
            <div class="network-grid">
                <?php foreach ($networks as $network): ?>
                    <div class="network-item" data-network="<?php echo $network['code']; ?>">
                        <img src="<?php echo $network['logo']; ?>" alt="<?php echo $network['name']; ?>">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title">Phone Number</div>
            <div class="phone-input-group">
                <input type="tel" name="phone_number" id="phone-number" placeholder="Phone Number" autocomplete="off">
                <i class="fas fa-address-book icon"></i>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const networkItems = document.querySelectorAll('.network-item');
    const selectedNetworkInput = document.getElementById('selected-network');
    const phoneNumberInput = document.getElementById('phone-number');
    const nextBtn = document.getElementById('next-btn');
    const airtimeForm = document.getElementById('airtime-form');

    let selectedNetwork = null;

    function validateForm() {
        const phoneNumber = phoneNumberInput.value.trim();
        const isNetworkSelected = !!selectedNetwork;
        const isPhoneNumberValid = /^\d{11}$/.test(phoneNumber);

        if (isNetworkSelected && isPhoneNumberValid) {
            nextBtn.disabled = false;
            nextBtn.classList.add('active');
        } else {
            nextBtn.disabled = true;
            nextBtn.classList.remove('active');
        }
    }

    networkItems.forEach(item => {
        item.addEventListener('click', function() {
            networkItems.forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            selectedNetwork = this.getAttribute('data-network');
            selectedNetworkInput.value = selectedNetwork;
            validateForm();
        });
    });

    phoneNumberInput.addEventListener('input', validateForm);

    nextBtn.addEventListener('click', function() {
        if (!this.disabled) {
            airtimeForm.submit();
        }
    });
});
</script>

<?php
// We will not include the standard footer, as the new design implies a different navigation model (like a sticky footer menu)
include 'includes/footer.php';
?>
