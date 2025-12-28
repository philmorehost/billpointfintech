<?php
$page_title = 'Bulk VTU Services';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Bulk Airtime Top-Up</h1>
        <p>Upload a CSV file to send airtime to multiple numbers at once.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form action="bulk_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="process_bulk_airtime">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="vtu_type">Top-Up Type</label>
                <select name="vtu_type" required>
                    <option value="local">Local (Nigeria)</option>
                    <option value="international">International</option>
                </select>
            </div>

            <div class="form-group">
                <label for="csv_file">Upload CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
                <small class="form-text text-muted">
                    CSV format: `phone_number,amount`. For international, include country code in number (e.g., `11234567890`).
                </small>
            </div>

            <button type="submit" class="btn">Upload and Process Batch</button>
        </form>

        <div style="margin-top: 20px;">
            <h4>CSV File Instructions</h4>
            <ul>
                <li>The file must be in CSV format.</li>
                <li>Each row must contain exactly two columns: the recipient's phone number and the amount.</li>
                <li>Do not include a header row.</li>
                <li>For **local** top-ups, use Nigerian phone numbers (e.g., `08012345678`).</li>
                <li>For **international** top-ups, the phone number must start with the country code (e.g., `1281...` for USA, `447...` for UK).</li>
            </ul>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
