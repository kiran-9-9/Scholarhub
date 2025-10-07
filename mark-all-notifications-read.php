<?php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'includes/Notification.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $userId = $auth->getUserId();
    $notification = new Notification();
    
    $result = $notification->markAllAsRead($userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
    }
} catch (Exception $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
} 