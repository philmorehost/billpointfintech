<?php
// Check if the application is installed
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Billpoint - Your Trusted Payment Solution</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Billpoint</div>
        <div class="nav-links">
            <a href="#features">Services</a>
            <a href="#">About Us</a>
            <a href="#">Help</a>
        </div>
        <div class="nav-actions">
            <a href="user/login.php" class="btn btn-login">Login</a>
            <a href="user/register.php" class="btn btn-signup">Sign Up</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>Reliable Payments, Made Simple.</h1>
            <p>Join millions who use Billpoint for fast, secure, and easy payments. Pay bills, buy airtime, and manage your finances all in one place.</p>
            <a href="user/register.php" class="btn-main">Get Started for Free</a>
        </div>
    </header>

    <section id="features" class="features">
        <h2>All Your Payment Needs in One App</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-mobile-alt"></i>
                <h3>Airtime & Data</h3>
                <p>Instantly top up any mobile network with just a few clicks.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-tv"></i>
                <h3>Bill Payments</h3>
                <p>Pay for your cable TV, electricity, and other utilities without hassle.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-paper-plane"></i>
                <h3>Money Transfers</h3>
                <p>Send and receive money from friends and family securely and instantly.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Wallet</h3>
                <p>Your funds are protected with industry-leading security standards.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Billpoint. All rights reserved.</p>
        <p>A modern payment solution inspired by the best.</p>
    </footer>

</body>
</html>
