<?php
// Use the init file which properly sets up sessions
require_once 'includes/init.php';
// No need to start session or require config.php as they're in init.php
require_once 'includes/Auth.php';
require_once 'includes/Security.php';
require_once 'includes/Logger.php';
require_once 'includes/Settings.php';

$auth = new Auth();
$security = Security::getInstance();
$logger = Logger::getInstance();

// Initialize settings and check maintenance mode
$db = Database::getInstance();
$pdo = $db->getConnection();
$settings = new Settings($pdo);
$maintenance_mode = $settings->get('maintenance_mode') === '1';

// If in maintenance mode and not accessing admin login
if ($maintenance_mode && !strpos($_SERVER['REQUEST_URI'], 'admin/login.php')) {
    // Store the intended URL if it's not the maintenance page itself
    if (!strpos($_SERVER['REQUEST_URI'], 'maintenance.php')) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
    }
    
    // Show maintenance page
    require_once 'maintenance.php';
    exit();
}

$error = '';
$success = '';
$csrf_token = $security->generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
            $logger->logSecurityEvent('CSRF token validation failed');
            exit;
        }

        $username = $security->sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            if (!$security->checkLoginAttempts($username)) {
                $error = 'Account is temporarily locked. Please try again later.';
            } else {
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    $security->resetLoginAttempts($username);
                    $security->regenerateSession();
                    
                    if ($auth->isAdmin()) {
                        header('Location: ' . APP_URL . '/admin/dashboard.php');
                    } else if ($maintenance_mode) {
                        // If site is in maintenance mode and user is not admin, show maintenance page
                        require_once 'maintenance.php';
                        exit();
                    } else {
                        $_SESSION['just_logged_in'] = true;
                        
                        // Get user data and log the login activity
                        $user = $auth->getUserData();
                        if ($user) {
                            $auth->logActivity(
                                $user['id'],
                                'login',
                                'User logged in successfully'
                            );
                        }
                        
                        header('Location: ' . APP_URL . '/dashboard.php');
                    }
                    exit();
                } else {
                    $security->incrementLoginAttempts($username);
                    $error = $result['message'];
                    $logger->logSecurityEvent('Failed login attempt', ['username' => $username]);
                }
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred during login. Please try again.';
        $logger->logError('Login error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357abd;
            --text-color: #333;
            --error-color: #ff4444;
            --success-color: #00C851;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100vw;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 2rem 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }
        input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            margin-top: 0.5rem;
        }
        .login-btn:hover {
            background: var(--secondary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        .error-message {
            background: #ffebee;
            color: var(--error-color);
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #ffcdd2;
            text-align: center;
        }
        .success-message {
            background: #e8f5e9;
            color: var(--success-color);
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c8e6c9;
            text-align: center;
        }
        .forgot-password-link {
            text-align: right;
            margin-bottom: 1rem;
        }
        .forgot-password-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .forgot-password-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        .back-to-home {
            text-align: left;
            margin-bottom: 1rem;
        }
        .back-to-home a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 1rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .back-to-home a:hover {
            color: var(--secondary-color);
            transform: translateX(-5px);
        }
        @media (max-width: 600px) {
            .login-box {
                padding: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="back-to-home">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="forgot-password-link">
                    <a href="forgot-password.php"><i class="fas fa-key"></i> Forgot Password?</a>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html> 