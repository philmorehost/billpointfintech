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

/**
 * Detects the Nigerian mobile network from a phone number.
 *
 * @param string $phone The phone number.
 * @return string|null The network code (mtn, airtel, glo, 9mobile) or null if not found.
 */
function detect_network($phone) {
    // Normalize the phone number by removing country code, leading +, or 0
    if (substr($phone, 0, 4) === '+234') {
        $phone = '0' . substr($phone, 4);
    }
    if (substr($phone, 0, 3) === '234') {
        $phone = '0' . substr($phone, 3);
    }

    if (strlen($phone) < 11) {
        return null; // Invalid length
    }

    $prefix = substr($phone, 1, 3); // Get the 3 digits after the leading '0'

    $prefixes = [
        'mtn' => ['803', '806', '810', '813', '814', '816', '703', '706', '903', '906'],
        'airtel' => ['802', '808', '812', '701', '708', '902', '907'],
        'glo' => ['805', '807', '811', '815', '705', '905'],
        '9mobile' => ['809', '817', '818', '908', '909']
    ];

    foreach ($prefixes as $network => $network_prefixes) {
        if (in_array($prefix, $network_prefixes)) {
            return $network;
        }
    }

    return null; // No match found
}
