<?php
// session_start(); // REMOVE THIS LINE - init.php will handle session start
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

try {
    // Include necessary files
    require_once '../includes/init.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';

    error_log("process_admin_login.php: Included files.");

    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        $response['message'] = 'Invalid request format. No JSON data received or malformed JSON.';
        error_log("process_admin_login.php: Invalid JSON data. Input: " . $json);
        echo json_encode($response);
        exit;
    }
    error_log("process_admin_login.php: JSON data decoded: " . print_r($data, true));

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        $response['message'] = 'Please provide both username and password.';
        error_log("process_admin_login.php: Missing username or password.");
        echo json_encode($response);
        exit;
    }
    error_log("process_admin_login.php: Username and password provided for user: " . $username);

    // Create Database connection to directly check admin credentials
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // First, try to authenticate against the admin table directly
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Valid admin credentials - Set all required session variables
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_last_activity'] = time();
        
        error_log("Admin login successful. Session data: " . print_r($_SESSION, true));
        
        $response['success'] = true;
        $response['message'] = 'Admin login successful';
        $response['redirect'] = 'dashboard.php';
    } else {
        error_log("Admin login failed for username: $username");
        $response['message'] = 'Invalid administrator credentials';
    }

} catch (PDOException $e) {
    error_log("Database error during admin login: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("General error during admin login: " . $e->getMessage());
    $response['message'] = 'An error occurred during login';
}

// Always output the JSON response
echo json_encode($response);
exit; // Ensure script termination after sending response
?> 