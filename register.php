<?php
session_start();
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = trim($first_name . ' ' . $last_name);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $result = $auth->register($username, $email, $password, $full_name, $phone, $address);
            if ($result['success']) {
                $success = 'Registration successful! Redirecting to login page...';
                header("refresh:2;url=login.php");
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "An error occurred during registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Scholarship Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357abd;
            --text-color: #333;
            --error-color: #ff4444;
            --success-color: #00C851;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 10px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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

        .container {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 800px;
            animation: fadeInUp 0.6s ease;
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

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 600;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            position: relative;
            gap: 20px;
        }

        label {
            flex: 0 0 150px;
            margin-bottom: 0;
            color: var(--text-color);
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.95rem;
            text-align: right;
        }

        /* Input field styling */
        input, textarea {
            flex: 1 1 auto;
            width: auto;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            min-width: 250px;
        }

        /* Form layout for two columns */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 1.25rem;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .form-row .form-group label {
            flex: 0 0 120px;
        }

        .form-row .form-group input {
            min-width: 200px;
        }

        /* Full width form groups */
        .form-group.full-width {
            flex: 1 1 100%;
        }

        .form-group.full-width input,
        .form-group.full-width textarea {
            width: 100%;
        }

        input:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: #aaa;
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            color: var(--error-color);
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--error-color);
            animation: shake 0.5s ease;
            font-size: 0.9rem;
        }

        .success {
            color: var(--success-color);
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(0, 200, 81, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
            animation: fadeIn 0.5s ease;
            font-size: 0.9rem;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .form-footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Fixed icon positioning */
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: var(--transition);
            pointer-events: none;
        }

        /* For textarea icon */
        textarea + .input-icon {
            top: 50%;
        }

        input:focus + .input-icon,
        textarea:focus + .input-icon {
            color: var(--primary-color);
        }

        /* Required field indicator */
        label:after {
            content: " *";
            color: var(--error-color);
        }

        label[for="phone"]:after,
        label[for="address"]:after {
            content: "";
        }

        @media (max-width: 700px) {
            .container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            label {
                text-align: left;
                margin-bottom: 0.5rem;
                flex: none;
            }

            input, textarea {
                min-width: 100%;
            }

            .input-icon {
                top: 38px;
                right: 15px;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    <i class="fas fa-phone input-icon"></i>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                <i class="fas fa-map-marker-alt input-icon"></i>
            </div>

            <button type="submit">
                <i class="fas fa-user-plus"></i>
                Register
            </button>
        </form>
        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html> 