<?php
require_once '../includes/bootstrap.php';
require_once '../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: fx_rates.php');
        exit();
    }
    if (isset($_POST['fx_rates'])) {
        foreach ($_POST['fx_rates'] as $pair => $markup) {
            save_setting($pdo, "fx_markup_{$pair}", (float)$markup);
        }
        set_flash_message('success', 'FX rate markups updated successfully.');
    }
    header("Location: fx_rates.php");
    exit();
}

$currency_pairs = ['USD_NGN', 'CAD_NGN', 'NGN_USD', 'NGN_CAD']; // Add more pairs as needed
$markups = [];
foreach ($currency_pairs as $pair) {
    $markups[$pair] = get_setting($pdo, "fx_markup_{$pair}") ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FX Rate Markups - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>FX Rate Markups</h2>
        <form action="fx_rates.php" method="post">
            <?php generate_csrf_token(); ?>
            <?php foreach ($currency_pairs as $pair): ?>
                <div class="form-group">
                    <label for="<?php echo $pair; ?>_markup"><?php echo str_replace('_', ' to ', $pair); ?> Markup (%)</label>
                    <input type="number" id="<?php echo $pair; ?>_markup" name="fx_rates[<?php echo $pair; ?>]" value="<?php echo htmlspecialchars($markups[$pair]); ?>" min="0" step="0.01">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn">Save Markups</button>
        </form>
    </div>
</body>
</html>
