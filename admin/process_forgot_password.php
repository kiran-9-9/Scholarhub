<?php
// Start output buffering at the very beginning
ob_start();

// Set error handling to catch everything
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Required files
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Logger.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Function to log errors
function logError($message) {
    $logFile = __DIR__ . '/../logs/password_reset_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

try {
    $security = Security::getInstance();
    $logger = Logger::getInstance();
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $response = [
        'success' => false,
        'message' => '',
        'redirect' => '',
        'debug' => []
    ];

    // Log request data
    logError("Request received - POST data: " . print_r($_POST, true));

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$security->validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid request - CSRF validation failed');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        throw new Exception('Please provide both username and email');
    }

    // Verify admin exists with matching username and email
    $stmt = $pdo->prepare("SELECT id, email FROM admin WHERE username = ? AND email = ?");
    $stmt->execute([$username, $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        logError("Admin not found - Username: $username, Email: $email");
        throw new Exception('Invalid admin credentials');
    }

    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token in database
    $stmt = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
    $stmt->execute([$reset_token, $token_expiry, $admin['id']]);

    // Generate reset link
    $reset_link = APP_URL . '/admin/reset-password.php?token=' . $reset_token;

    // Load email configuration
    $emailConfig = require __DIR__ . '/../config/email.php';
    logError("Email config loaded: " . print_r($emailConfig, true));

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = function($str, $level) use (&$response) {
        $response['debug'][] = $str;
        logError("PHPMailer debug: $str");
    };

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp_host'];
        $mail->SMTPAuth = $emailConfig['smtp_auth'];
        $mail->Username = $emailConfig['smtp_username'];
        $mail->Password = $emailConfig['smtp_password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'];
        $mail->Port = $emailConfig['smtp_port'];
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($admin['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Admin Password Reset Request - ScholarHub';
        
        // HTML Message
        $htmlMessage = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2>Password Reset Request</h2>
                <p>Hello,</p>
                <p>A password reset was requested for your admin account at ScholarHub.</p>
                <p>Please click the following link to reset your password:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you did not request this reset, please ignore this email and ensure your account is secure.</p>
                <br>
                <p>Best regards,<br>ScholarHub Team</p>
            </body>
            </html>
        ";
        
        // Plain text alternative
        $textMessage = "Hello,\n\n"
            . "A password reset was requested for your admin account at ScholarHub.\n\n"
            . "Please click the following link to reset your password:\n"
            . $reset_link . "\n\n"
            . "This link will expire in 1 hour.\n\n"
            . "If you did not request this reset, please ignore this email and ensure your account is secure.\n\n"
            . "Best regards,\nScholarHub Team";

        $mail->Body = $htmlMessage;
        $mail->AltBody = $textMessage;

        // Send email
        if (!$mail->send()) {
            throw new Exception('Failed to send reset email: ' . $mail->ErrorInfo);
        }
        
        $logger->logSecurityEvent("Password reset email sent successfully for admin: $username");
        $response['success'] = true;
        $response['message'] = 'Password reset instructions have been sent to your email';
        $response['redirect'] = 'login.php';
        
    } catch (PHPMailerException $e) {
        logError("PHPMailer Error: " . $e->getMessage());
        throw new Exception('Failed to send reset email: ' . $e->getMessage());
    }

} catch (Exception $e) {
    logError("Error in process_forgot_password.php: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug'][] = "Error: " . $e->getMessage();
}

// Get any output that might have been generated
$output = ob_get_clean();
if (!empty($output)) {
    logError("Unexpected output before JSON: " . $output);
}

// Ensure we're sending JSON header
header('Content-Type: application/json');

// Log the response we're about to send
logError("Sending JSON response: " . json_encode($response));

// Send JSON response
echo json_encode($response);
exit; 