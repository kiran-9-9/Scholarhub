<?php
// Use the init file which properly sets up sessions
require_once 'includes/init.php';
// No need to start session or require config.php as they're in init.php
require_once 'includes/Auth.php';
require_once 'includes/Security.php';
require_once 'includes/Logger.php';

$error = '';
$success = '';
$csrf_token = '';

try {
    $auth = new Auth();
    $security = Security::getInstance();
    $logger = Logger::getInstance();

    $csrf_token = $security->generateCSRFToken();

    // Process the form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
            $logger->logSecurityEvent('CSRF token validation failed on forgot password');
        } else {
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $error = 'Please enter a valid email address';
            } else {
                $result = $auth->sendPasswordResetEmail($email);
                if ($result['success']) {
                    $success = $result['message'];
                    $logger->logSecurityEvent('Password reset requested for email: ' . $email);
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
} catch (Exception $e) {
    $error = 'A system error occurred. Please try again later.';
    error_log('Forgot password error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .form-box {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .form-icon {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-icon i {
            font-size: 48px;
            color: #4a90e2;
            background-color: #e7f3ff;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        h2 {
            color: #4a90e2;
            margin-bottom: 25px;
            text-align: center;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        input[type="email"]:focus {
            border-color: #4a90e2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        .btn {
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-weight: 600;
            width: 100%;
            text-align: center;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #4a90e2;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="form-icon">
                <i class="fas fa-key"></i>
            </div>
            
            <h2>Forgot Password</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">Please check your email for further instructions.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="Enter your email address"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-link">
                <p>Remember your password? <a href="login.php">Log in instead</a></p>
            </div>
        </div>
    </div>
</body>
</html> 