<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/Security.php';
require_once 'includes/Logger.php';

$auth = new Auth();
$security = Security::getInstance();
$logger = Logger::getInstance();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$result = $auth->verifyEmail($token);
if ($result['success']) {
    $success = 'Your email has been verified successfully. You can now login.';
    $logger->logUserActivity($result['user_id'], 'Email verified');
} else {
    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Email Verification</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2"><a href="login.php">Click here to login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 