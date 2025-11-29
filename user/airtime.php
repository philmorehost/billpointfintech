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

$networks = [
    ['name' => 'MTN', 'logo' => '../assets/images/mtn.png', 'code' => 'MTN'],
    ['name' => 'Glo', 'logo' => '../assets/images/glo.png', 'code' => 'GLO'],
    ['name' => 'Airtel', 'logo' => '../assets/images/airtel.png', 'code' => 'AIRTEL'],
    ['name' => '9mobile', 'logo' => '../assets/images/9mobile.png', 'code' => '9MOBILE'],
];

include '../includes/header.php';
?>

<style>
    .app-view { background-color: #f5f5f5; min-height: 100vh; padding-bottom: 80px; }
    .airtime-header { display: flex; align-items: center; padding: 15px; background-color: #fff; border-bottom: 1px solid #eee; }
    .airtime-header .back-btn { font-size: 24px; color: #333; text-decoration: none; margin-right: 15px; }
    .airtime-header .title { font-size: 20px; font-weight: 600; color: #333; }
    .wallet-balance-card { background-color: #fff; border-radius: 12px; padding: 15px; margin: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .wallet-balance-card .label { display: flex; align-items: center; font-size: 16px; color: #555; }
    .wallet-balance-card .label i { margin-right: 10px; font-size: 22px; color: #4f46e5; }
    .wallet-balance-card .amount { font-size: 18px; font-weight: 700; color: #e54646; }
    .content-card { background-color: #fff; padding: 20px 15px; margin: 15px; border-radius: 12px; }
    .content-card .section-title { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 15px; }
    .network-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 25px; }
    .network-item { border: 2px solid #eee; border-radius: 10px; padding: 10px; cursor: pointer; transition: all 0.2s; display: flex; justify-content: center; align-items: center; }
    .network-item img { max-width: 100%; height: 35px; object-fit: contain; }
    .network-item.selected { border-color: #4f46e5; box-shadow: 0 0 10px rgba(79, 70, 229, 0.3); }
    .phone-input-group { position: relative; }
    .phone-input-group input { width: 100%; padding: 15px 50px 15px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 16px; background-color: #f5f5f5; }
    .phone-input-group .icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 22px; color: #888; }
    .amount-selection { margin-top: 25px; }
    .amount-buttons { display: flex; justify-content: space-between; margin-bottom: 15px; }
    .amount-btn { background-color: #f0f0f0; border: 1px solid #ddd; border-radius: 8px; padding: 10px 15px; cursor: pointer; font-size: 14px; }
    .amount-btn.active { background-color: #4f46e5; color: #fff; border-color: #4f46e5; }
    #amount { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 16px; background-color: #f5f5f5; }
    .buy-btn { background-color: #4f46e5; color: #fff; border: none; padding: 15px; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 25px; }
    .buy-btn:disabled { background-color: #d1d5db; cursor: not-allowed; }
    .server-message { margin-top: 15px; padding: 10px; border-radius: 8px; display: none; }
    .server-message.success { background-color: #dcfce7; color: #166534; }
    .server-message.error { background-color: #fee2e2; color: #991b1b; }
    /* Responsive styles */
    @media (max-width: 360px) {
        .network-item img { height: 30px; }
        .amount-btn { padding: 8px 10px; font-size: 12px; }
    }
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Airtime</span>
    </div>

    <div class="wallet-balance-card">
        <div class="label"><i class="fas fa-wallet"></i><span>Wallet Balance</span></div>
        <div class="amount">₦<?php echo number_format($wallet_balance, 2); ?></div>
    </div>

    <div class="content-card">
        <form id="airtime-form">
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
                <input type="tel" name="phone_number" id="phone-number" placeholder="Phone Number" autocomplete="off" maxlength="11">
                <i class="fas fa-address-book icon"></i>
            </div>

            <div class="amount-selection">
                <div class="section-title">Select Amount</div>
                <div class="amount-buttons">
                    <button type="button" class="amount-btn" data-amount="100">₦100</button>
                    <button type="button" class="amount-btn" data-amount="200">₦200</button>
                    <button type="button" class="amount-btn" data-amount="500">₦500</button>
                </div>
                <input type="number" name="amount" id="amount" placeholder="Or Enter Amount" min="50" required>
            </div>

            <button type="submit" id="buy-btn" class="buy-btn">Buy Now</button>
            <div id="server-message" class="server-message"></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const networkItems = document.querySelectorAll('.network-item');
    const selectedNetworkInput = document.getElementById('selected-network');
    const phoneNumberInput = document.getElementById('phone-number');
    const amountButtons = document.querySelectorAll('.amount-btn');
    const amountInput = document.getElementById('amount');
    const airtimeForm = document.getElementById('airtime-form');
    const buyBtn = document.getElementById('buy-btn');
    const serverMessage = document.getElementById('server-message');

    const networkPrefixes = {
        'MTN': ['0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906'],
        'GLO': ['0805', '0807', '0705', '0815', '0811', '0905'],
        'AIRTEL': ['0802', '0808', '0701', '0708', '0812', '0902', '0907'],
        '9MOBILE': ['0809', '0817', '0818', '0908', '0909']
    };

    function detectNetwork() {
        const phoneNumber = phoneNumberInput.value;
        if (phoneNumber.length >= 4) {
            const prefix = phoneNumber.substring(0, 4);
            let detectedNetwork = null;
            for (const network in networkPrefixes) {
                if (networkPrefixes[network].includes(prefix)) {
                    detectedNetwork = network;
                    break;
                }
            }
            networkItems.forEach(item => {
                if (item.dataset.network === detectedNetwork) {
                    item.classList.add('selected');
                    selectedNetworkInput.value = detectedNetwork;
                } else {
                    item.classList.remove('selected');
                }
            });
        } else {
             networkItems.forEach(item => item.classList.remove('selected'));
             selectedNetworkInput.value = '';
        }
    }

    phoneNumberInput.addEventListener('input', detectNetwork);

    amountButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            amountButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            amountInput.value = this.dataset.amount;
        });
    });

    amountInput.addEventListener('input', () => {
        amountButtons.forEach(b => b.classList.remove('active'));
    });

    airtimeForm.addEventListener('submit', function(e) {
        e.preventDefault();

        buyBtn.disabled = true;
        buyBtn.textContent = 'Processing...';
        serverMessage.style.display = 'none';

        const formData = new FormData(airtimeForm);
        fetch('ajax_airtime_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            serverMessage.textContent = data.message;
            if (data.status === 'success') {
                serverMessage.className = 'server-message success';
                airtimeForm.reset(); // Clear form on success
                networkItems.forEach(item => item.classList.remove('selected'));
            } else {
                serverMessage.className = 'server-message error';
            }
            serverMessage.style.display = 'block';
        })
        .catch(error => {
            serverMessage.textContent = 'An unexpected error occurred. Please try again.';
            serverMessage.className = 'server-message error';
            serverMessage.style.display = 'block';
        })
        .finally(() => {
            buyBtn.disabled = false;
            buyBtn.textContent = 'Buy Now';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
