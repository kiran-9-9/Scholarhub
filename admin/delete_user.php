<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    $_SESSION['error'] = "You must be logged in as admin to perform this action.";
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    // Validate user ID
    if ($user_id <= 0) {
        $_SESSION['error'] = "Invalid user ID.";
        header('Location: users.php');
        exit();
    }
    
    // Prevent admin from deleting themselves
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header('Location: users.php');
        exit();
    }
    
    // Check if user exists
    $user = $auth->getUserById($user_id);
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header('Location: users.php');
        exit();
    }
    
    // Delete user
    if ($auth->deleteUser($user_id)) {
        $_SESSION['success'] = "User deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete user. Please try again.";
        error_log("Failed to delete user ID: " . $user_id);
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header('Location: users.php');
exit(); 