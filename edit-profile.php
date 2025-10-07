<?php
// Initialize the application
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'config/database.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get first name and last name for display
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);

$error = '';
$success = '';

function logUserActivity($pdo, $userId, $type, $desc) {
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $type,
        $desc,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $data = [
        'full_name' => trim($first_name . ' ' . $last_name),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? '')
    ];

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($data['email'])) {
        $error = 'First name, last name, and email are required fields';
    } else {
        // Check if email is being changed and if it already exists
        if ($data['email'] !== $user['email'] && $auth->isEmailExists($data['email'], $user['id'])) {
            $error = 'Email already exists';
        }

        // Handle password change if provided
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!empty($current_password)) {
            if (empty($new_password) || empty($confirm_password)) {
                $error = 'New password and confirmation are required when changing password';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect';
            } else {
                $data['password'] = $new_password;
            }
        }

        if (empty($error)) {
            // Fetch old user data before update
            $oldUser = $auth->getUserData();
            $result = $auth->updateProfile($user['id'], $data);
            if ($result['success']) {
                $success = $result['message'];
                require_once 'config/database.php';
                // Log specific field changes
                if ($oldUser['address'] !== $data['address']) {
                    logUserActivity($pdo, $user['id'], 'Address Update', "User updated their address from '" . $oldUser['address'] . "' to '" . $data['address'] . "'.");
                }
                if ($oldUser['phone'] !== $data['phone']) {
                    logUserActivity($pdo, $user['id'], 'Phone Update', "User updated their phone from '" . $oldUser['phone'] . "' to '" . $data['phone'] . "'.");
                }
                if ($oldUser['email'] !== $data['email']) {
                    logUserActivity($pdo, $user['id'], 'Email Update', "User updated their email from '" . $oldUser['email'] . "' to '" . $data['email'] . "'.");
                }
                if ($oldUser['full_name'] !== $data['full_name']) {
                    logUserActivity($pdo, $user['id'], 'Name Update', "User updated their name from '" . $oldUser['full_name'] . "' to '" . $data['full_name'] . "'.");
                }
                if (!empty($data['password'])) {
                    logUserActivity($pdo, $user['id'], 'Password Change', 'User changed their password.');
                }
                // Log generic profile update activity
                logUserActivity($pdo, $user['id'], 'Profile Update', 'User updated their profile.');
                // Refresh user data
                $user = $auth->getUserData();
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 70px;
            background: #f8f9fa;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 auto;
        }
        .profile-card {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            animation: fadeIn 0.6s ease;
        }

        .profile-card h1 {
            color: var(--secondary-color);
            margin-bottom: 2rem;
            font-size: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-group {
            margin-bottom: 1.5rem;
        }

        .info-label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .password-section {
            grid-column: 1 / -1;
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }

        .password-section h2 {
            color: var(--secondary-color);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .password-strength-text {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-requirements {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(74, 144, 226, 0.05);
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }

        .password-requirements li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .password-requirements li i {
            font-size: 0.8rem;
        }

        .requirement-met {
            color: #00C851;
        }

        .requirement-not-met {
            color: #666;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-input-group {
            position: relative;
        }

        .password-input-group .form-control {
            padding-right: 2.5rem;
        }

        .password-input-group .password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
        }

        .text-muted {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
        }

        .alert-success {
            background: rgba(0, 200, 81, 0.1);
            color: #00C851;
            border-left: 4px solid #00C851;
        }

        .alert-danger {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border-left: 4px solid #ff4444;
        }

        .btn-group {
            grid-column: 1 / -1;
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: var(--text-color);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
            .profile-card {
                padding: 1.5rem;
            }
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <h1>ScholarHub</h1>
        </a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="contact.php">Contact Us</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="profile-card">
            <h1>
                <i class="fas fa-user-edit"></i>
                Edit Profile
            </h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="profile-info">
                <div class="info-group">
                    <label class="info-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>

                <div class="info-group">
                    <label class="info-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>

                <div class="info-group">
                    <label class="info-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="info-group">
                    <label class="info-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="info-group" style="grid-column: 1 / -1;">
                    <label class="info-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php 
                        echo htmlspecialchars($user['address'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="password-section">
                    <h2>
                        <i class="fas fa-lock"></i>
                        Change Password
                    </h2>
                    <p class="text-muted">Leave blank if you don't want to change your password</p>

                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters long</li>
                            <li id="req-uppercase"><i class="fas fa-circle"></i> Contains uppercase letter</li>
                            <li id="req-lowercase"><i class="fas fa-circle"></i> Contains lowercase letter</li>
                            <li id="req-number"><i class="fas fa-circle"></i> Contains number</li>
                            <li id="req-special"><i class="fas fa-circle"></i> Contains special character</li>
                        </ul>
                    </div>

                    <div class="info-group">
                        <label class="info-label" for="current_password">Current Password</label>
                        <div class="password-input-group">
                            <input type="password" id="current_password" name="current_password" class="form-control">
                            <button type="button" class="password-toggle" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label class="info-label" for="new_password">New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="new_password" name="new_password" class="form-control">
                                <button type="button" class="password-toggle" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar"></div>
                            </div>
                            <div class="password-strength-text">
                                <i class="fas fa-shield-alt"></i>
                                <span>Password Strength: <span id="strength-text">Not Set</span></span>
                            </div>
                        </div>

                        <div class="info-group">
                            <label class="info-label" for="confirm_password">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                <button type="button" class="password-toggle" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.querySelector('.password-strength-bar');
        const strengthText = document.getElementById('strength-text');
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };

        function checkPasswordStrength(password) {
            let strength = 0;
            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators
            Object.keys(checks).forEach(key => {
                const icon = requirements[key].querySelector('i');
                if (checks[key]) {
                    icon.className = 'fas fa-check requirement-met';
                    strength += 20;
                } else {
                    icon.className = 'fas fa-circle requirement-not-met';
                }
            });

            // Update strength bar
            strengthBar.style.width = strength + '%';
            if (strength <= 20) {
                strengthBar.style.backgroundColor = '#ff4444';
                strengthText.textContent = 'Very Weak';
            } else if (strength <= 40) {
                strengthBar.style.backgroundColor = '#ffa700';
                strengthText.textContent = 'Weak';
            } else if (strength <= 60) {
                strengthBar.style.backgroundColor = '#ffdb4a';
                strengthText.textContent = 'Medium';
            } else if (strength <= 80) {
                strengthBar.style.backgroundColor = '#99cc00';
                strengthText.textContent = 'Strong';
            } else {
                strengthBar.style.backgroundColor = '#00C851';
                strengthText.textContent = 'Very Strong';
            }

            if (password.length === 0) {
                strengthText.textContent = 'Not Set';
                strengthBar.style.width = '0';
            }
        }

        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Please enter your current password to change password');
                } else if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                } else if (newPassword.length > 0 && newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long');
                }
            }
        });
    </script>
</body>
</html> 