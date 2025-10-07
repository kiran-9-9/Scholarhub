<?php
// Use common init file to ensure we're clearing the right session
require_once 'includes/init.php';
require_once 'includes/Auth.php';

// Log the user out using Auth class
$auth = new Auth();
$auth->logout();

// Clear session data
$_SESSION = array();

// Clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 