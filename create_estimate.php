<?php
$page_title = 'Create Estimate';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Create New Estimate</h1>
    </div>

    <div class="content-box">
        <form action="invoice_handler.php" method="POST">
            <input type="hidden" name="action" value="create_estimate">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <h3>Customer Information</h3>
            <div class="form-group">
                <label for="customer_name">Customer Name</label>
                <input type="text" name="customer_name" required>
            </div>
            <div class="form-group">
                <label for="customer_email">Customer Email</label>
                <input type="email" name="customer_email" required>
            </div>

            <hr>
            <h3>Estimate Items</h3>
            <div id="items-container">
                <div class="item-row">
                    <input type="text" name="items[0][description]" placeholder="Item Description" required>
                    <input type="number" name="items[0][quantity]" placeholder="Quantity" min="1" required>
                    <input type="number" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" min="0.01" required>
                    <button type="button" class="remove-item-btn">Remove</button>
                </div>
            </div>
            <button type="button" id="add-item-btn" class="btn btn-secondary">Add Another Item</button>

            <hr>
            <button type="submit" class="btn">Save Estimate</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 1;
    const itemsContainer = document.getElementById('items-container');

    document.getElementById('add-item-btn').addEventListener('click', function() {
        const newItemRow = document.createElement('div');
        newItemRow.classList.add('item-row');
        newItemRow.innerHTML = `
            <input type="text" name="items[${itemIndex}][description]" placeholder="Item Description" required>
            <input type="number" name="items[${itemIndex}][quantity]" placeholder="Quantity" min="1" required>
            <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Unit Price" step="0.01" min="0.01" required>
            <button type="button" class="remove-item-btn">Remove</button>
        `;
        itemsContainer.appendChild(newItemRow);
        itemIndex++;
    });

    itemsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.item-row').remove();
        }
    });
});
</script>
<style>
.item-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}
.item-row input {
    flex: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
