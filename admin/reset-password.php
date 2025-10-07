<?php
require_once '../includes/init.php';
require_once '../includes/Security.php';
require_once '../includes/Database.php';
require_once '../includes/Logger.php';

$security = Security::getInstance();
$logger = Logger::getInstance();
$db = Database::getInstance();
$pdo = $db->getConnection();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$csrf_token = $security->generateCSRFToken();

// Clear any existing admin sessions for security
if (isset($_SESSION['admin_logged_in'])) {
    session_destroy();
}

try {
    if (empty($token)) {
        header('Location: login.php');
        exit;
    }

    // Verify token validity
    $stmt = $pdo->prepare("SELECT id, username FROM admin WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
        $logger->logSecurityEvent('Invalid admin reset token used: ' . substr($token, 0, 10) . '...');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
            $logger->logSecurityEvent('CSRF token validation failed on admin password reset');
        } else {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirm_password)) {
                $error = 'Please enter both password fields';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long';
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $admin['id']]);

                $logger->logSecurityEvent('Password reset completed for admin: ' . $admin['username']);
                $_SESSION['message'] = 'Your password has been reset successfully. You can now login with your new password.';
                $_SESSION['message_type'] = 'success';
                header('Location: login.php');
                exit;
            }
        }
    }
} catch (Exception $e) {
    $error = 'A system error occurred. Please try again later.';
    $logger->logError('Admin reset password error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password - ScholarHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2C3E50 0%, #3498DB 100%);
            padding: 2rem;
        }

        .login-box {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 1s ease;
        }

        .login-box h4 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 2rem;
        }

        .admin-badge {
            background: var(--secondary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-color);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 0.5rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .security-notice {
            font-size: 0.85rem;
            color: #666;
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .password-strength-weak {
            background: #dc3545;
            width: 33%;
        }

        .password-strength-medium {
            background: #ffc107;
            width: 66%;
        }

        .password-strength-strong {
            background: #28a745;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div style="text-align: center;">
                <span class="admin-badge">Administrator Access</span>
            </div>
            <h4>Reset Admin Password</h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$error): ?>
                <form method="POST" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required 
                               minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                               title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                        <div class="password-strength"></div>
                        <div class="password-requirements">
                            Password must contain:
                            <ul style="margin: 0.5rem 0 0 1.2rem; padding: 0;">
                                <li>At least 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one lowercase letter</li>
                                <li>At least one number</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                        <a href="login.php" class="btn btn-secondary">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <p>This is a secure password reset. Your new password will be encrypted.</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.querySelector('.password-strength');
            
            // Reset strength bar
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                return;
            }

            // Check password strength
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isLongEnough = password.length >= 8;

            const strength = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecialChar, isLongEnough]
                .filter(Boolean).length;

            // Update strength bar
            if (strength <= 2) {
                strengthBar.classList.add('password-strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('password-strength-medium');
            } else {
                strengthBar.classList.add('password-strength-strong');
            }
        });

        // Password match validation
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html> 