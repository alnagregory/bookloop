<?php
/**
 * BOOKLOOP - Online Book Exchange & Marketplace
 * Database Configuration & Connection File
 */

// Ensure output buffering is active globally
if (!ob_get_level()) {
    ob_start();
}

// Base URL definition - dynamically resolved or default
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . $host . '/bookloop/');
}

// Database Credentials (Default XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookloop');
define('DB_CHARSET', 'utf8mb4');

// Upload Paths
define('SITE_ROOT', dirname(__DIR__) . '/');
define('UPLOAD_DIR_BOOKS', SITE_ROOT . 'uploads/books/');
define('UPLOAD_DIR_PROFILES', SITE_ROOT . 'uploads/profiles/');

// Establish PDO Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Graceful error display without exposing sensitive server details
    die('<div style="font-family:Segoe UI, sans-serif; text-align:center; padding:50px 20px; color:#1e293b;">
        <h2 style="color:#dc2626;">Database Connection Error</h2>
        <p>Could not connect to the MySQL database <strong>bookloop</strong>.</p>
        <p>Please ensure MySQL is running in XAMPP and the database <code>bookloop</code> has been imported.</p>
        <p style="font-size:13px; color:#64748b;">Technical error: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
