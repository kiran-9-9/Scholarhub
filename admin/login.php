<?php
// Include necessary files
require_once '../includes/init.php'; 
require_once '../includes/Auth.php';
require_once '../includes/Settings.php';

// Debug - log session data
error_log('Admin Login Page - Starting Session Data: ' . print_r($_SESSION, true));

// Create auth instance
$auth = new Auth();

// Initialize settings
$db = Database::getInstance();
$pdo = $db->getConnection();
$settings = new Settings($pdo);
$maintenance_mode = $settings->get('maintenance_mode') === '1';

// Check if user is already logged in as admin
if ($auth->isLoggedIn() && $auth->isAdmin()) {
    error_log('Admin already logged in, redirecting to dashboard');
    header('Location: dashboard.php');
    exit();
}

// Clear any existing session data if not already logged in as admin
if (!($auth->isLoggedIn() && $auth->isAdmin())) {
    error_log('Not logged in as admin, clearing session');
    session_unset();
    session_regenerate_id(true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ScholarHub</title>
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

        .login-box h2 {
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
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 5px;
            transition: var(--transition);
        }

        .form-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .login-btn:hover {
            background: var(--primary-color);
        }

        .back-to-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-home a {
            color: var(--secondary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .back-to-home a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .security-notice {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .maintenance-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .maintenance-notice i {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div style="text-align: center;">
                <span class="admin-badge">Administrator Access</span>
            </div>
            <h2>Admin Login</h2>
            
            <?php if ($maintenance_mode): ?>
            <div class="maintenance-notice">
                <i class="fas fa-tools"></i>
                <strong>Maintenance Mode is Active</strong>
                <p>Only administrators can access the site.</p>
            </div>
            <?php endif; ?>

            <div class="error-message" id="error-message"></div>
            <form id="admin-login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="forgot-password-link" style="text-align: right; margin-bottom: 1rem;">
                    <a href="forgot-password.php" style="color: var(--secondary-color); text-decoration: none;">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
                <button type="submit" class="login-btn">Login to Admin Panel</button>
            </form>
            <div class="back-to-home">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Homepage
                </a>
            </div>
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <p>This is a secure area. Unauthorized access is prohibited.</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        document.getElementById('admin-login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('error-message');
            
            console.log('Attempting admin login for username:', username);
            
            // Admin login AJAX call
            fetch('process_admin_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(response => {
                console.log('Server response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Server response data:', data);
                if (data.success) {
                    console.log('Login successful, redirecting to:', data.redirect || 'dashboard.php');
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    errorMessage.style.display = 'block';
                    errorMessage.textContent = data.message || 'Invalid administrator credentials';
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                errorMessage.style.display = 'block';
                errorMessage.textContent = 'An error occurred. Please try again.';
            });
        });

        // Add subtle animation to the security notice
        const securityNotice = document.querySelector('.security-notice');
        securityNotice.style.opacity = '0';
        securityNotice.style.transform = 'translateY(10px)';
        securityNotice.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            securityNotice.style.opacity = '1';
            securityNotice.style.transform = 'translateY(0)';
        }, 500);
    </script>
</body>
</html> 