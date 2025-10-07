<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';

// Create auth instance
$auth = new Auth();

// Log the logout action
error_log('Admin logout initiated');

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Log successful logout
error_log('Admin logout successful');

// Redirect to admin login page
header('Location: login.php');
exit(); 