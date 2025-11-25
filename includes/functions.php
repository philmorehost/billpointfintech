<?php
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Handle subdirectory installations
    $script_name = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script_name);
    // Ensure the directory has a trailing slash if it's not the root
    $dir = ($dir === '/' || $dir === '\\') ? '' : $dir;
    return "$protocol://$host$dir/";
}


function send_admin_alert($message, $subject) {
    global $config;
    $admin_phone = $config['settings']['admin_phone'] ?? null;
    if ($admin_phone) {
        // This is a placeholder. In a real application, you would integrate an SMS gateway API here.
        // For demonstration, we'll log it to the server's error log.
        $log_message = "--- ADMIN ALERT ---\nSubject: {$subject}\nTo: {$admin_phone}\nMessage: {$message}\n-------------------\n";
        error_log($log_message);
    }
}
