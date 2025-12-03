<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$exam_products = $pdo->query("SELECT * FROM exam_products ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Exam Pins</span>
    </div>

    <div class="container">
        <div class="form-card">
            <div id="server-message"></div>

            <form id="exam-form">
                <div class="form-group">
                    <label for="exam_code">Exam Type</label>
                    <select name="exam_code" id="exam_code" class="form-control" required>
                        <option value="">-- Select Exam --</option>
                        <?php foreach($exam_products as $product): ?>
                            <option value="<?php echo htmlspecialchars($product['code']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (₦<?php echo htmlspecialchars(number_format($product['price'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                </div>
                <p><strong>Total:</strong> <span id="total-price">₦0.00</span></p>
                <button type="submit" id="buy-btn" class="btn-submit">Purchase</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const examForm = document.getElementById('exam-form');
    const examSelect = document.getElementById('exam_code');
    const quantityInput = document.getElementById('quantity');
    const totalPriceDisplay = document.getElementById('total-price');

    function updatePrice() {
        const selectedOption = examSelect.options[examSelect.selectedIndex];
        const price = parseFloat(selectedOption.dataset.price) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        totalPriceDisplay.textContent = `₦${(price * quantity).toFixed(2)}`;
    }

    examSelect.addEventListener('change', updatePrice);
    quantityInput.addEventListener('input', updatePrice);
    updatePrice();

    examForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('buy-btn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        const formData = new FormData(examForm);
        fetch('ajax_exam_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                document.getElementById('server-message').innerHTML = `<div class="${data.status}"><p>${data.message}</p></div>`;
                if(data.status === 'success') examForm.reset();
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Purchase';
                updatePrice();
            });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
