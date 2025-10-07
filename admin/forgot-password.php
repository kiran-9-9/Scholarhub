<?php
require_once '../includes/init.php';
require_once '../includes/Security.php';

$security = Security::getInstance();
$csrf_token = $security->generateCSRFToken();

// Clear any existing admin sessions for security
if (isset($_SESSION['admin_logged_in'])) {
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset - ScholarHub</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --primary-hover: #357abd;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --background-color: #f5f5f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --border-color: #dddddd;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --transition-speed: 0.3s;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 400px;
            width: 100%;
            background: var(--card-background);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow-color);
            transform: translateY(0);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
        }

        .container:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px var(--shadow-color);
        }

        h2 {
            margin: 0 0 1.5rem 0;
            color: var(--text-color);
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
            position: relative;
            padding-bottom: 0.5rem;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color var(--transition-speed);
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: all var(--transition-speed);
            background-color: #fff;
            color: var(--text-color);
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .button-group {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: all var(--transition-speed);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-reset {
            background-color: var(--primary-color);
            color: white;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .btn-reset:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .btn-reset:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back {
            color: var(--primary-color);
            background: transparent;
            padding: 0.75rem 1rem;
        }

        .btn-back:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            opacity: 0;
            transform: translateY(-10px);
            animation: slideDown 0.3s ease forwards;
        }

        @keyframes slideDown {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #fde8e8;
            border: 1px solid #f8d7d7;
            color: var(--danger-color);
        }

        .alert-success {
            background-color: #e8f8e8;
            border: 1px solid #d7f8d7;
            color: var(--success-color);
        }

        .alert-info {
            background-color: #e8f4fd;
            border: 1px solid #d7ebf8;
            color: var(--primary-color);
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            padding: 1rem;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-radius: 6px;
            display: none;
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        .debug-info.show {
            display: block;
            opacity: 1;
        }

        .show-debug {
            color: #666;
            text-decoration: underline;
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 1rem;
            display: none;
            text-align: center;
            padding: 0.5rem;
            transition: color var(--transition-speed);
        }

        .show-debug:hover {
            color: #333;
        }

        /* Loading animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Form transition */
        #forgotPasswordForm {
            transition: opacity var(--transition-speed), transform var(--transition-speed);
        }

        #forgotPasswordForm.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Shake animation for errors */
        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Password Reset</h2>
        <div id="messageContainer"></div>
        <form id="forgotPasswordForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-group">
                <label for="username">Admin Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-reset">
                    <span class="loading-spinner"></span>
                    <span class="button-text">Reset Password</span>
                </button>
                <a href="login.php" class="btn btn-back">Back to Login</a>
            </div>
        </form>
        <div id="debugInfo" class="debug-info"></div>
        <a id="showDebug" class="show-debug">Show Technical Details</a>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear any existing messages
            const messageContainer = document.getElementById('messageContainer');
            const debugInfo = document.getElementById('debugInfo');
            const showDebugLink = document.getElementById('showDebug');
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const loadingSpinner = submitButton.querySelector('.loading-spinner');
            const buttonText = submitButton.querySelector('.button-text');
            
            messageContainer.innerHTML = '';
            debugInfo.innerHTML = '';
            debugInfo.classList.remove('show');
            showDebugLink.style.display = 'none';
            
            // Show loading state
            form.classList.add('loading');
            submitButton.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            buttonText.textContent = 'Processing...';
            
            // Show loading message
            const loadingAlert = document.createElement('div');
            loadingAlert.className = 'alert alert-info';
            loadingAlert.textContent = 'Processing your request...';
            messageContainer.appendChild(loadingAlert);
            
            fetch('process_forgot_password.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                // Remove loading message
                messageContainer.innerHTML = '';
                
                // Create alert element
                const alert = document.createElement('div');
                alert.className = `alert alert-${data.success ? 'success' : 'danger'}`;
                alert.textContent = data.message;
                messageContainer.appendChild(alert);
                
                // Handle debug information
                if (data.debug && data.debug.length > 0) {
                    debugInfo.innerHTML = data.debug.join('\n');
                    showDebugLink.style.display = 'block';
                    
                    showDebugLink.onclick = function() {
                        debugInfo.classList.toggle('show');
                        this.textContent = debugInfo.classList.contains('show') ? 
                            'Hide Technical Details' : 'Show Technical Details';
                    };
                }
                
                if (data.success) {
                    // Add success state
                    form.style.opacity = '0.7';
                    submitButton.style.backgroundColor = 'var(--success-color)';
                    
                    // Redirect after delay if redirect URL is provided
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 3000);
                    }
                } else {
                    // Show error state
                    form.classList.add('shake');
                    setTimeout(() => form.classList.remove('shake'), 500);
                }
            })
            .catch(error => {
                // Remove loading message
                messageContainer.innerHTML = '';
                
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'An error occurred while processing your request. Please try again.';
                messageContainer.appendChild(alert);
                
                // Show technical error details
                debugInfo.innerHTML = 'Error: ' + error.message;
                showDebugLink.style.display = 'block';
                showDebugLink.onclick = function() {
                    debugInfo.classList.toggle('show');
                    this.textContent = debugInfo.classList.contains('show') ? 
                        'Hide Technical Details' : 'Show Technical Details';
                };
                
                // Show error state
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 500);
                
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset loading state
                form.classList.remove('loading');
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
                buttonText.textContent = 'Reset Password';
            });
        });
    </script>
</body>
</html> 