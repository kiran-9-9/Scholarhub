<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Settings.php';
require_once '../includes/Database.php';

// Check admin authentication
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

// Initialize settings
$db = Database::getInstance();
$pdo = $db->getConnection();
$settings = new Settings($pdo);
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update settings
        $settings->set('site_name', filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING));
        $settings->set('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        $settings->set('max_applications', filter_input(INPUT_POST, 'max_applications', FILTER_VALIDATE_INT));
        
        $message = '<div class="alert success">Settings updated successfully!</div>';
    } catch (Exception $e) {
        $message = '<div class="alert error">Failed to update settings.</div>';
    }
}

// Get current settings
$current = $settings->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | ScholarHub Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        /* Additional styles specific to settings page */
        .settings-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .card-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .toggle-switch {
            display: flex;
            align-items: center;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            margin-right: 0.75rem;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="settings-card">
                <div class="card-header">
                    <h1 class="card-title">System Settings</h1>
                </div>

                <?php if ($message): ?>
                    <?php echo $message; ?>
                <?php endif; ?>

                <form method="POST" action="">
                    
                    <div class="form-group">
                        <label class="form-label" for="maintenance_mode">
                            <div class="toggle-switch">
                                <label class="switch">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode"
                                           <?php echo ($current['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                Maintenance Mode
                            </div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="max_applications">Maximum Applications per User</label>
                        <input type="number" id="max_applications" name="max_applications" class="form-control" 
                               value="<?php echo htmlspecialchars($current['max_applications'] ?? '3'); ?>" 
                               min="1" max="10" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html> 