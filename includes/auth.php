<?php
/**
 * BOOKLOOP - Authentication and Authorization Guards
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access this page.');
        // Save intended URL
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }

    // Verify user is not suspended or inactive
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();

    if ($status === 'suspended') {
        session_unset();
        session_destroy();
        session_start();
        setFlash('error', 'Your account has been suspended by an administrator. Please contact support.');
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Administrator privileges required.');
        redirect('login.php');
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            redirect('admin/dashboard.php');
        } else {
            redirect('dashboard.php');
        }
    }
}
