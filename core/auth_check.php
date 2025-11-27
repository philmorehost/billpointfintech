<?php
// core/auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. If user is already logged in via session, update activity and continue
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    // Re-check user status to ensure they haven't been suspended during their session
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetchColumn() !== 'active') {
        // Log user out if their account is no longer active
        session_destroy();
        setcookie('remember_me', '', time() - 3600, "/");
        header('Location: login.php?message=account_suspended');
        exit;
    }
    return;
}

// 2. If not logged in, check for a "Remember Me" cookie
if (isset($_COOKIE['remember_me'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_me'], 2);

    if (!empty($user_id) && !empty($token)) {
        $pdo = db_connect();
        $stmt = $pdo->prepare(
            "SELECT id, remember_token, remember_token_expiry FROM users
             WHERE id = ? AND status = 'active' AND remember_token IS NOT NULL"
        );
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && hash_equals($user['remember_token'], hash('sha256', $token)) && strtotime($user['remember_token_expiry']) > time()) {
            // Token is valid, log the user in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();

            // Optional: Refresh the token for better security
            // For simplicity, we will not do this now.
        } else {
            // Invalid token, clear the cookie
            setcookie('remember_me', '', time() - 3600, "/");
        }
    }
}

// 3. Final check: if user is still not logged in, redirect them
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
