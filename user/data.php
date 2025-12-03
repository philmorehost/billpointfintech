<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
// Fetch data plans and group them by network
$stmt = $pdo->query("SELECT * FROM data_plans ORDER BY network, price");
$all_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$plans_by_network = [];
foreach ($all_plans as $plan) {
    $plans_by_network[$plan['network']][] = $plan;
}

$networks = [
    ['name' => 'MTN', 'logo' => '../assets/images/mtn.png', 'code' => 'MTN'],
    ['name' => 'Glo', 'logo' => '../assets/images/glo.png', 'code' => 'GLO'],
    ['name' => 'Airtel', 'logo' => '../assets/images/airtel.png', 'code' => 'AIRTEL'],
    ['name' => '9mobile', 'logo' => '../assets/images/9mobile.png', 'code' => '9MOBILE'],
];

include '../includes/header.php';
?>
<style>
/* Add styles for the tabbed interface */
.network-tabs { display: flex; justify-content: space-around; margin-bottom: 20px; }
.tab-btn { background: none; border: none; padding: 10px; cursor: pointer; opacity: 0.5; }
.tab-btn.active { opacity: 1; border-bottom: 2px solid #4f46e5; }
.tab-btn img { height: 30px; }

.plans-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
.plans-grid.active { display: grid; }
.plan-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; }
.plan-card.selected { border-color: #4f46e5; background-color: #eef2ff; }
.plan-card .quantity { font-weight: bold; }
.plan-card .price { color: #4f46e5; }
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Buy Data</span>
    </div>

    <div class="container">
        <div class="form-card">
            <form id="data-form">
                <input type="hidden" name="plan_id" id="plan_id">

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" id="phone-number" class="form-control" required placeholder="Enter phone number">
                </div>

                <div class="network-tabs">
                    <?php foreach ($networks as $network): ?>
                        <button type="button" class="tab-btn" data-network="<?php echo $network['code']; ?>">
                            <img src="<?php echo $network['logo']; ?>" alt="<?php echo $network['name']; ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($plans_by_network as $network_code => $plans): ?>
                    <div class="plans-grid" id="plans-<?php echo $network_code; ?>">
                        <?php foreach ($plans as $plan): ?>
                            <div class="plan-card" data-plan-id="<?php echo $plan['id']; ?>">
                                <div class="quantity"><?php echo htmlspecialchars($plan['quantity']); ?></div>
                                <div class="price">₦<?php echo htmlspecialchars($plan['price']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" id="buy-btn" class="btn-submit" style="margin-top: 20px;">Buy Data</button>
                <div id="server-message" class="server-message" style="margin-top: 15px;"></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // JS for network detection, tab switching, plan selection, and AJAX form submission
    const phoneInput = document.getElementById('phone-number');
    const tabs = document.querySelectorAll('.tab-btn');
    const plansGrids = document.querySelectorAll('.plans-grid');
    const planIdInput = document.getElementById('plan_id');
    const dataForm = document.getElementById('data-form');
    const buyBtn = document.getElementById('buy-btn');
    const serverMessage = document.getElementById('server-message');

    const networkPrefixes = {
        'MTN': ['0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906'],
        'GLO': ['0805', '0807', '0705', '0815', '0811', '0905'],
        'AIRTEL': ['0802', '0808', '0701', '0708', '0812', '0902', '0907'],
        '9MOBILE': ['0809', '0817', '0818', '0908', '0909']
    };

    function switchTab(networkCode) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.network === networkCode));
        plansGrids.forEach(g => g.classList.toggle('active', g.id === `plans-${networkCode}`));
        // Reset selected plan when switching tabs
        planIdInput.value = '';
        document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    }

    phoneInput.addEventListener('input', function() {
        const prefix = this.value.substring(0, 4);
        let detectedNetwork = null;
        for (const network in networkPrefixes) {
            if (networkPrefixes[network].includes(prefix)) {
                detectedNetwork = network;
                break;
            }
        }
        if (detectedNetwork) {
            switchTab(detectedNetwork);
        }
    });

    tabs.forEach(tab => tab.addEventListener('click', () => switchTab(tab.dataset.network)));

    document.querySelectorAll('.plan-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            planIdInput.value = this.dataset.planId;
        });
    });

    dataForm.addEventListener('submit', function(e) {
        e.preventDefault();
        buyBtn.disabled = true;
        buyBtn.textContent = 'Processing...';
        serverMessage.style.display = 'none';

        const formData = new FormData(dataForm);
        fetch('ajax_data_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                serverMessage.textContent = data.message;
                serverMessage.className = `server-message ${data.status}`;
                serverMessage.style.display = 'block';
                if (data.status === 'success') dataForm.reset();
            })
            .finally(() => {
                buyBtn.disabled = false;
                buyBtn.textContent = 'Buy Data';
            });
    });

    // Activate the first tab by default
    if(tabs.length > 0) {
        switchTab(tabs[0].dataset.network);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
