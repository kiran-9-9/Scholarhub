<?php
// Use the init file which properly sets up sessions
require_once 'includes/init.php';
// No need to start session or require config.php as they're in init.php
require_once 'includes/Security.php';
require_once 'includes/Logger.php';

$error = '';
$success = '';
$csrf_token = '';
$token = $_GET['token'] ?? '';

try {
    require_once 'includes/Auth.php';
    $auth = new Auth();
    $security = Security::getInstance();
    $logger = Logger::getInstance();

    $csrf_token = $security->generateCSRFToken();

    if (empty($token)) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }

    $reset = $auth->validateResetToken($token);
    if (!$reset) {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
        $logger->logSecurityEvent('Invalid reset token used: ' . substr($token, 0, 10) . '...');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
            $logger->logSecurityEvent('CSRF token validation failed on password reset');
        } else {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirm_password)) {
                $error = 'Please enter both password fields';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                $result = $auth->resetPassword($token, $password);
                if ($result['success']) {
                    $success = $result['message'];
                    $logger->logSecurityEvent('Password reset completed for user ID: ' . $reset['user_id']);
                    // Redirect to the correct login page after successful password reset
                    header('Location: ' . APP_URL . '/login.php?message=' . urlencode('Your password has been reset successfully. You can now login with your new password.'));
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
} catch (Exception $e) {
    $error = 'A system error occurred. Please try again later.';
    error_log('Reset password error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
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
        
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        input[type="password"]:focus {
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
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
        }
        
        .password-toggle:focus {
            outline: none;
        }
        
        .password-strength {
            margin-top: 6px;
            height: 5px;
            border-radius: 3px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 13px;
            margin-top: 6px;
            display: block;
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
            
            <h2>Reset Your Password</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if (strpos($error, 'Invalid or expired') !== false): ?>
                        <div class="mt-3">
                            <a href="<?php echo APP_URL; ?>/forgot-password.php" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    </div>
                </div>
            <?php elseif (!$error || strpos($error, 'Invalid or expired') === false): ?>
                <form method="POST" action="" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required 
                                   minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                   class="password-input">
                            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <small class="text-muted">
                            Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long
                            <?php if (PASSWORD_REQUIRE_SPECIAL): ?>and contain at least one special character<?php endif; ?>
                            <?php if (PASSWORD_REQUIRE_NUMBERS): ?>, one number<?php endif; ?>
                            <?php if (PASSWORD_REQUIRE_UPPERCASE): ?>, and one uppercase letter<?php endif; ?>.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="password-match-status"></small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!$success && (strpos($error, 'Invalid or expired') === false)): ?>
                <div class="login-link">
                    <p>Remember your password? <a href="<?php echo APP_URL; ?>/login.php">Log in instead</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Toggle eye icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
        
        // Check password strength
        const passwordInput = document.getElementById('password');
        const strengthBar = document.querySelector('.password-strength-bar');
        
        if (passwordInput && strengthBar) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Length check
                if (password.length >= <?php echo PASSWORD_MIN_LENGTH; ?>) {
                    strength += 25;
                }
                
                // Uppercase check
                if (/[A-Z]/.test(password)) {
                    strength += 25;
                }
                
                // Numbers check
                if (/[0-9]/.test(password)) {
                    strength += 25;
                }
                
                // Special character check
                if (/[^A-Za-z0-9]/.test(password)) {
                    strength += 25;
                }
                
                // Update strength bar
                strengthBar.style.width = strength + '%';
                
                // Color based on strength
                if (strength < 25) {
                    strengthBar.style.backgroundColor = '#ff4d4d'; // Red
                } else if (strength < 50) {
                    strengthBar.style.backgroundColor = '#ffa64d'; // Orange
                } else if (strength < 75) {
                    strengthBar.style.backgroundColor = '#ffff4d'; // Yellow
                } else {
                    strengthBar.style.backgroundColor = '#4CAF50'; // Green
                }
            });
        }
        
        // Check password match
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchStatus = document.getElementById('password-match-status');
        
        if (confirmPasswordInput && passwordMatchStatus && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const confirmValue = this.value;
                const passwordValue = passwordInput.value;
                
                if (confirmValue === '') {
                    passwordMatchStatus.textContent = '';
                } else if (confirmValue === passwordValue) {
                    passwordMatchStatus.textContent = 'Passwords match ✓';
                    passwordMatchStatus.style.color = '#4CAF50';
                } else {
                    passwordMatchStatus.textContent = 'Passwords do not match ✗';
                    passwordMatchStatus.style.color = '#ff4d4d';
                }
            });
        }
    </script>
</body>
</html>
