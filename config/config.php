<?php
// Application configuration
define('APP_NAME', 'ScholarHub');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/ScholarHub');
define('APP_ROOT', dirname(__DIR__));
define('ENVIRONMENT', 'development'); // Changed back to development for debugging

// Session configuration constants
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'scholarship_session');
define('SESSION_SECURE', false); // Keep false for localhost
define('SESSION_HTTP_ONLY', true);

// Security configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', false);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_UPPERCASE', false);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', APP_ROOT . '/uploads');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'scholarhub517@gmail.com');
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_FROM_EMAIL', 'scholarhub517@gmail.com');
define('SMTP_FROM_NAME', 'ScholarHub');

// Logging configuration
define('LOG_DIR', APP_ROOT . '/logs');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Maintenance Mode (Set to true to enable maintenance mode)
define('MAINTENANCE_MODE', false);

// Create required directories if they don't exist
$directories = [LOG_DIR, UPLOAD_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set error reporting based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('UTC');

// Remove the session cookie setting line since it's now handled in init.php 

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'scholarhub_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4'); 