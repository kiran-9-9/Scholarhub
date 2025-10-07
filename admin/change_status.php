<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if user is admin
$auth = new Auth($conn);
if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $allowed_statuses = ['active', 'inactive', 'suspended'];
    if (!in_array($status, $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status selected.";
        header('Location: users.php');
        exit();
    }
    
    // Update user status
    if ($auth->updateUserStatus($user_id, $status)) {
        $_SESSION['success'] = "User status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user status.";
    }
}

header('Location: users.php');
exit(); 