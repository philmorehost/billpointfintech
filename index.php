<?php
// index.php - Main entry point

// Check for installation lock file
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit();
}

// Include configuration
require_once 'includes/config.php';

// --- Public Landing Page ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billpoint - Global Payments, Local Services</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Billpoint</div>
            <nav class="nav">
                <a href="#">Login</a>
                <a href="#" class="btn btn-primary">Sign Up</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Global Payments, Local Services</h1>
            <p>Secure, fast, and reliable. Your all-in-one fintech solution.</p>
            <div class="hero-image">
                <!-- Placeholder for Mobile-App UI image -->
                <img src="https://via.placeholder.com/600x400" alt="Mobile App UI">
            </div>
        </div>
    </section>

    <!-- Trust Indicators -->
    <section class="trust-indicators">
        <div class="container">
            <h3>Trusted by the best</h3>
            <!-- Placeholder logos -->
            <img src="https://via.placeholder.com/100x40" alt="Monnify">
            <img src="https://via.placeholder.com/100x40" alt="Flutterwave">
            <img src="https://via.placeholder.com/100x40" alt="Paystack">
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Billpoint. All rights reserved.</p>
            <a href="#">Terms of Service</a> | <a href="#">Privacy Policy</a>
        </div>
    </footer>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/<?php echo ADMIN_PHONE; ?>" class="whatsapp-float" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.371-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.177-.002-.372-.002-.57 0-.198 0-.521.074-.792.372-.272.296-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
        </svg>
    </a>

</body>
</html>
