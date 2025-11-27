<?php
session_start();

// Prevent re-installation
if (file_exists('installed.lock')) {
    header("Content-Type: text/html; charset=utf-8");
    die("<h3>Already Installed</h3><p>This application is already installed. To reinstall, please delete the <code>installed.lock</code> file from the application's root directory.</p>");
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Prevent jumping ahead in steps
if ($step > 1 && (!isset($_SESSION['install_step']) || $step > $_SESSION['install_step'] + 1)) {
    $valid_step = $_SESSION['install_step'] ?? 1;
    header('Location: install.php?step=' . $valid_step);
    exit;
}

$page_title = "Billpoint Installation - Step $step";

function check_php_version() { return version_compare(PHP_VERSION, '8.0.0', '>='); }
function check_extension($name) { return extension_loaded($name); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .container { max-width: 600px; margin-top: 50px; }
        .req-list { padding: 0; margin: 20px 0; }
        .req-list li { list-style: none; padding: 10px; border-bottom: 1px solid #eee; }
        .req-list .fas { margin-right: 10px; }
        .fa-check-circle { color: green; }
        .fa-times-circle { color: red; }
        .notice { border-left: 4px solid #17a2b8; padding: 10px; background-color: #f1f1f1; margin-bottom: 20px;}
    </style>
</head>
<body>
<div class="container">
    <h1>Installation Wizard</h1>

    <?php if ($step == 1):
        $php_version_ok = check_php_version();
        $pdo_mysql_ok = check_extension('pdo_mysql');
        $curl_ok = check_extension('curl');
        $all_ok = $php_version_ok && $pdo_mysql_ok && $curl_ok;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $all_ok) {
             $_SESSION['install_step'] = 1;
             header('Location: install.php?step=2');
             exit;
        }
    ?>
        <h2>Step 1: System Requirements</h2>
        <ul class="req-list">
            <li><i class="fas <?php echo $php_version_ok ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> PHP Version 8.0+ (Your version: <?php echo PHP_VERSION; ?>)</li>
            <li><i class="fas <?php echo $pdo_mysql_ok ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> PDO MySQL Extension</li>
            <li><i class="fas <?php echo $curl_ok ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> cURL Extension</li>
        </ul>
        <?php if ($all_ok): ?>
            <p class="success">Your server meets all the requirements.</p>
            <form method="post"><button type="submit">Next Step &raquo;</button></form>
        <?php else: ?>
            <div class="errors"><p>Please fix the highlighted issues before proceeding.</p></div>
        <?php endif; ?>

    <?php elseif ($step == 2):
        $db_error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['post_data'] = $_POST;
            $db_host = $_POST['db_host'] ?? '';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';

            try {
                $dsn = "mysql:host=$db_host;charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
                $_SESSION['db_details'] = compact('db_host', 'db_name', 'db_user', 'db_pass');
                unset($_SESSION['post_data']);
                $_SESSION['install_step'] = 2;
                header('Location: install.php?step=3');
                exit;
            } catch (PDOException $e) {
                $db_error = "Database operation failed: " . $e->getMessage();
            }
        }
        $post = $_SESSION['post_data'] ?? [];
    ?>
        <h2>Step 2: Database Configuration</h2>
        <?php if ($db_error): ?> <div class="errors"><p><?php echo htmlspecialchars($db_error); ?></p></div> <?php endif; ?>
        <form method="post">
            <p class="notice">Enter your database details. The installer will create the database if it does not exist.</p>
            <div class="form-group"><label for="db_host">Host</label><input type="text" name="db_host" id="db_host" value="<?php echo htmlspecialchars($post['db_host'] ?? 'localhost'); ?>" required></div>
            <div class="form-group"><label for="db_name">Database Name</label><input type="text" name="db_name" id="db_name" value="<?php echo htmlspecialchars($post['db_name'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="db_user">Username</label><input type="text" name="db_user" id="db_user" value="<?php echo htmlspecialchars($post['db_user'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="db_pass">Password</label><input type="password" name="db_pass" id="db_pass"></div>
            <button type="submit">Test Connection &amp; Next &raquo;</button>
        </form>

    <?php elseif ($step == 3):
        $admin_error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['post_data'] = $_POST;
            $admin_user = trim($_POST['admin_user'] ?? '');
            $admin_pass = $_POST['admin_pass'] ?? '';
            $admin_pass_confirm = $_POST['admin_pass_confirm'] ?? '';

            if (empty($admin_user) || empty($admin_pass)) {
                $admin_error = "All fields are required.";
            } elseif (strlen($admin_pass) < 8) {
                $admin_error = "Password must be at least 8 characters long.";
            } elseif ($admin_pass !== $admin_pass_confirm) {
                $admin_error = "Passwords do not match.";
            } else {
                $_SESSION['admin_details'] = compact('admin_user', 'admin_pass');
                unset($_SESSION['post_data']);
                $_SESSION['install_step'] = 3;
                header('Location: install.php?step=4');
                exit;
            }
        }
        $post = $_SESSION['post_data'] ?? [];
    ?>
        <h2>Step 3: Create Admin User</h2>
        <?php if ($admin_error): ?> <div class="errors"><p><?php echo htmlspecialchars($admin_error); ?></p></div> <?php endif; ?>
        <form method="post">
            <div class="form-group"><label for="admin_user">Admin Username</label><input type="text" name="admin_user" id="admin_user" value="<?php echo htmlspecialchars($post['admin_user'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="admin_pass">Password</label><input type="password" name="admin_pass" id="admin_pass" required></div>
            <div class="form-group"><label for="admin_pass_confirm">Confirm Password</label><input type="password" name="admin_pass_confirm" id="admin_pass_confirm" required></div>
            <button type="submit">Create Admin &amp; Next &raquo;</button>
        </form>

    <?php elseif ($step == 4):
        $install_error = '';
        if (!isset($_SESSION['db_details'], $_SESSION['admin_details'])) {
            $install_error = "Session data is missing. Please start over.";
        } else {
            try {
                $db = $_SESSION['db_details'];
                $config_content = "<?php\n\n"
                    . "define('DB_HOST', '" . addslashes($db['db_host']) . "');\n"
                    . "define('DB_NAME', '" . addslashes($db['db_name']) . "');\n"
                    . "define('DB_USER', '" . addslashes($db['db_user']) . "');\n"
                    . "define('DB_PASS', '" . addslashes($db['db_pass']) . "');\n\n"
                    . "define('SITE_NAME', 'Billpoint');\n"
                    . "define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . str_replace('install.php', '', \$_SERVER['PHP_SELF']));\n\n"
                    . "define('VTU_API_KEY', getenv('VTU_API_KEY') ?: '');\n\n"
                    . "session_start();\n";
                file_put_contents('core/config.php', $config_content);

                $dsn = "mysql:host={$db['db_host']};dbname={$db['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $pdo->exec(file_get_contents('database.sql'));
                if (file_exists('seeds.sql')) $pdo->exec(file_get_contents('seeds.sql'));

                $admin = $_SESSION['admin_details'];
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->execute([$admin['admin_user'], password_hash($admin['admin_pass'], PASSWORD_DEFAULT)]);

                file_put_contents('installed.lock', date('c'));
                session_destroy();

            } catch (Exception $e) {
                $install_error = "An error occurred: " . $e->getMessage();
                if (file_exists('core/config.php')) unlink('core/config.php');
                if (file_exists('installed.lock')) unlink('installed.lock');
            }
        }
    ?>
        <h2>Step 4: Finalizing Installation</h2>
        <?php if ($install_error): ?>
            <div class="errors">
                <h4>Installation Failed</h4>
                <p><?php echo htmlspecialchars($install_error); ?></p>
                <a href="install.php?step=1"><button>&laquo; Try Again</button></a>
            </div>
        <?php else: ?>
            <div class="success"><h3>Congratulations!</h3><p>Billpoint has been installed successfully.</p></div>
            <div class="notice"><h4>Important:</h4><p>For security reasons, please <strong>delete the <code>install.php</code> file</strong> from your server.</p></div>
            <a href="user/login.php"><button>User Login</button></a>
            <a href="admin/login.php"><button>Admin Login</button></a>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
