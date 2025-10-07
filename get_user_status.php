<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in.']);
    exit();
}

$user = $auth->getUserData();
$userId = $user['id'];

$db = Database::getInstance();
$pdo = $db->getConnection();

$response = [
    'status' => 'error',
    'message' => 'Could not fetch status',
    'userStatus' => null,
    'statusNotification' => null
];

try {
    // Get user status
    $stmt = $pdo->prepare("
        SELECT status FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $userStatus = $stmt->fetchColumn();

    // Get status-related notifications
    $stmt = $pdo->prepare("
        SELECT message, created_at FROM notifications 
        WHERE user_id = ? AND type = 'status_update'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $statusNotification = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['message'] = 'Status fetched successfully';
    $response['userStatus'] = $userStatus;
    $response['statusNotification'] = $statusNotification;

} catch (Exception $e) {
    error_log("Error fetching user status via AJAX: " . $e->getMessage());
    $response['message'] = 'Server error';
}

echo json_encode($response); 