<?php
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit();
}
session_start();
$page_title = 'Welcome to Billpoint';
// We don't need a full bootstrap here, just the config for the phone number
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Landing page specific styles */
        body { padding-top: 80px; padding-bottom: 0; }
        .landing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }
        .hero {
            text-align: center;
            padding: 80px 20px;
        }
        .hero h1 { font-size: 48px; }
        .hero-visual { margin-top: 40px; max-width: 400px; }
        .trust-section {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
        }
        .trust-logos { display: flex; justify-content: center; align-items: center; gap: 40px; margin-top: 20px; }
        .trust-logos img { height: 40px; opacity: 0.7; }
        .landing-footer { text-align: center; padding: 20px; font-size: 14px; color: #888; }
        .whatsapp-float {
            position: fixed; bottom: 30px; right: 30px;
            background-color: #25D366; color: white;
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <header class="landing-header">
        <div class="logo">
            <a href="index.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold; font-size: 24px;">Billpoint</a>
        </div>
        <nav>
            <a href="login.php" class="btn btn-secondary">Login</a>
            <a href="signup.php" class="btn btn-primary">Sign Up</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Global Payments, Local Services, Total Security.</h1>
            <p>Your one-stop platform for seamless financial transactions, from local bill payments to international transfers.</p>
            <img src="https://via.placeholder.com/400x600.png?text=Mobile+App+UI" alt="Mobile App UI" class="hero-visual">
        </section>

        <section class="trust-section">
            <h2>Trusted By The Best</h2>
            <div class="trust-logos">
                <img src="https://via.placeholder.com/150x40.png?text=Monnify" alt="Monnify">
                <img src="https://via.placeholder.com/150x40.png?text=Flutterwave" alt="Flutterwave">
                <img src="https://via.placeholder.com/150x40.png?text=Paystack" alt="Paystack">
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <p>&copy; <?php echo date('Y'); ?> Billpoint. All rights reserved.</p>
        <p><a href="#">Terms of Service</a> | <a href="#">Privacy Policy</a></p>
    </footer>

    <a href="https://wa.me/<?php echo ADMIN_PHONE; ?>" class="whatsapp-float" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M19.05 4.94A9.99 9.99 0 0 0 12 2C6.477 2 2 6.477 2 12c0 1.742.446 3.383 1.238 4.805L2 22l5.195-1.238A9.956 9.956 0 0 0 12 22c5.523 0 10-4.477 10-10c0-2.764-1.125-5.267-2.95-7.06zM12 20.15a8.125 8.125 0 0 1-4.14-1.18l-.297-.177l-3.085.735l.75-3.02l-.194-.31a8.15 8.15 0 0 1-1.214-4.348C3.82 7.57 7.57 3.82 12 3.82c2.14 0 4.1.84 5.58 2.31c1.47 1.48 2.31 3.44 2.31 5.58c0 4.43-3.75 8.17-8.17 8.17zm3.83-5.33c-.153-.076-1.004-.496-1.16-.552c-.155-.056-.268-.076-.38.076c-.113.153-.438.552-.537.66c-.1.11-.198.12-.354.034c-.156-.086-.66-.243-1.256-.775c-.465-.415-.78-.925-.873-1.08c-.093-.155-.01-.238.066-.314c-.316-.757.156-1.4.312-1.89c.112-.34.198-.447.032-.552c-.168-.105-.354-.11-.47-.11s-.41.056-.624.28c-.214.223-.552.68-.472 1.354c.08.673.552 1.57.628 1.676c.076.105 1.044 1.74 2.53 2.38c.35.15.58.206.78.263c.4.11.685.093.94-.076c.297-.19.438-.52.494-.66c.056-.14.056-.263-.01-.34zm-9.01-1.28c-.153-.076-.847-.496-.957-.552c-.11-.056-.19-.076-.268.076c-.076.153-.354.552-.41.66c-.056.11-.113.12-.268.034c-.156-.086-.552-.243-.98-.775c-.465-.415-.624-.925-.717-1.08c-.093-.155.01-.238.076-.314c.066-.076.153-.19.23-.268c.076-.076.113-.153.153-.25c.038-.11.02-.19-.01-.268c-.03-.076-.268-.32-.354-.438c-.086-.113-.17-.11-.268-.11s-.19.01-.283.034c-.093.024-.268.153-.354.34c-.086.19-.23.496-.153.94c.076.447.41.925.47 1.02c.06.105.78 1.74 2.274 2.38c.35.15.58.206.78.263c.4.11.685.093.94-.076c.297-.19.354-.52.41-.66c.056-.14.056-.263-.01-.34z"/></svg>
    </a>
</body>
</html>
