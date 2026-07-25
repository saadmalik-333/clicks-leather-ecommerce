<?php
/**
 * Clicks Leather — Database Connection
 * 
 * PDO-based MySQL connection with security best practices:
 * - Real prepared statements (emulation disabled)
 * - UTF-8 charset
 * - Exception error mode
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'clicks_leather');
define('DB_USER', 'root');
define('DB_PASS', '');       // Change in production!
define('DB_CHARSET', 'utf8mb4');

// Base path constants
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Site URL — adjust to your local setup
define('SITE_URL', 'http://localhost/Leather website');
define('PUBLIC_URL', SITE_URL . '/public');
define('ADMIN_URL', SITE_URL . '/admin');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,     // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // Return associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                      // Real prepared statements
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"         // Force UTF-8
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // In production, log the error instead of displaying it
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}
