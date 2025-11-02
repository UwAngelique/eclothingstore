<?php
// auth_check.php - Session authentication checker
// Include this at the top of every protected page

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$timeout_duration = 1800; // 30 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired due to inactivity
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 3600) {
    // Session started more than 1 hour ago
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Store user info for easy access (if available)
$logged_in_user_id = $_SESSION['login_id'] ?? null;
$logged_in_user_type = $_SESSION['login_type'] ?? 'admin';
$logged_in_username = $_SESSION['login_name'] ?? 'Admin';
?>