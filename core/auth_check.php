<?php
// core/auth_check.php

// This file is included on almost every user-facing page,
// so it's the perfect place to centralize our core includes.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';


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
    // --- PIN VERIFICATION LOGIC ---
    // Get the current script name to avoid redirect loops
    $current_page = basename($_SERVER['PHP_SELF']);
    $allowed_pages = ['enter-pin.php', 'set-pin.php', 'logout.php'];

    if (!in_array($current_page, $allowed_pages)) {
        // If on any protected page other than the pin pages, check for pin verification
        if (empty($_SESSION['pin_verified_at']) || (time() - $_SESSION['pin_verified_at']) > 1800) { // 30 min timeout
            header('Location: enter-pin.php');
            exit;
        }
    }
    // --- END PIN LOGIC ---
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
            // Token is valid, log the user in but redirect to PIN entry
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();

            // Unset any previous pin verification to force re-entry
            unset($_SESSION['pin_verified_at']);

            header('Location: enter-pin.php');
            exit;
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
