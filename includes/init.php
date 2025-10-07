<?php
// Load Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load configuration first
require_once dirname(__DIR__) . '/config/config.php';

// Configure session before starting it
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', SESSION_HTTP_ONLY);
ini_set('session.cookie_secure', SESSION_SECURE);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.name', SESSION_NAME);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // IMPORTANT: These settings MUST come before session_start
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => SESSION_SECURE,
        'httponly' => SESSION_HTTP_ONLY,
        'samesite' => 'Lax'
    ]);

    // Set session name 
    session_name(SESSION_NAME);
    
    // Start session
    session_start();
}

// Define base path constant if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Set error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once BASE_PATH . '/config/database.php';

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Function to autoload classes
spl_autoload_register(function ($class_name) {
    $file = BASE_PATH . '/includes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Check for maintenance mode
if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    // Get the current script filename and path
    $current_script = $_SERVER['SCRIPT_NAME'];
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // List of allowed paths during maintenance
    $allowed_paths = [
        '/admin/login.php',
        '/admin/dashboard.php',
        '/admin/settings.php',
        '/maintenance.php',
        '/admin/css/admin-style.css',
        '/css/style.css'
    ];
    
    // Check if the current user is an admin
    $is_admin = false;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $is_admin = true;
    }
    
    // If not an admin and not accessing an allowed path
    if (!$is_admin && !in_array($current_path, $allowed_paths)) {
        // Store the intended URL if it's not the maintenance page itself
        if ($current_path !== '/maintenance.php') {
            $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        }
        
        // Show maintenance message
        require_once BASE_PATH . '/maintenance.php';
        exit();
    }
}

// Initialize Security and Logger instances if needed globally
$security = Security::getInstance();
$logger = Logger::getInstance();

// For example, basic error handling setup (can be expanded)
set_exception_handler(function ($e) use ($logger) {
    $logger->logException($e);
    // Optionally display a generic error page in production
    if (!defined('DEBUG') || !DEBUG) {
        // http_response_code(500);
        // echo "<h1>An error occurred. Please try again later.</h1>";
    }
});

set_error_handler(function ($severity, $message, $file, $line) use ($logger) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return false;
    }
    $logger->error("PHP Error: $message in $file on line $line", ['severity' => $severity]);
    // Don't execute PHP's internal error handler
    return true;
});