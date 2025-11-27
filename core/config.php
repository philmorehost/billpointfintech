<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'billpoint');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Billpoint');
define('SITE_URL', 'http://localhost/billpoint');

// API Key - It's recommended to use an environment variable for this in a production environment
define('VTU_API_KEY', getenv('VTU_API_KEY') ?: 'YOUR_API_KEY_HERE');

// Start the session
session_start();
