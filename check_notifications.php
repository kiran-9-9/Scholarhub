<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'includes/Notification.php';

try {
    $auth = new Auth();
    $notification = new Notification();
    
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        echo "Not logged in!\n";
        exit;
    }
    
    // Get current user info
    $user = $auth->getUserData();
    echo "Current user: " . $user['username'] . " (ID: " . $user['id'] . ")\n\n";
    
    // Get database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check notifications table structure
    echo "Checking notifications table structure:\n";
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "\n";
    
    // Check notifications for current user
    echo "Checking notifications for current user:\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "No notifications found for current user.\n";
    } else {
        echo "Found " . count($notifications) . " notifications:\n";
        foreach ($notifications as $notif) {
            echo "\nID: " . $notif['id'] . "\n";
            echo "Title: " . $notif['title'] . "\n";
            echo "Type: " . $notif['type'] . "\n";
            echo "Is Read: " . ($notif['is_read'] ? 'Yes' : 'No') . "\n";
            echo "Created: " . $notif['created_at'] . "\n";
            echo "-------------------\n";
        }
    }
    
    // Check if notifications component is working
    echo "\nChecking notifications component:\n";
    $componentNotifications = $notification->getUserNotifications($user['id']);
    echo "Component found " . count($componentNotifications) . " notifications\n";
    
    // Check unread count
    $unreadCount = $notification->getUnreadCount($user['id']);
    echo "Unread count: " . $unreadCount . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 