<?php
// Use the init file which properly sets up sessions and loads config
require_once 'includes/init.php';
require_once 'includes/Security.php';

// Security check - only allow in development mode
if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
    die('This tool is only available in development mode.');
}

// Initialize variables
$logDir = APP_ROOT . '/logs/emails';
$logFiles = [];
$selectedLog = '';
$logContent = '';

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get all log files
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'txt') {
            $logFiles[] = $file;
        }
    }
    // Sort in reverse chronological order (newest first)
    rsort($logFiles);
}

// Get the selected log file
if (isset($_GET['log']) && in_array($_GET['log'], $logFiles)) {
    $selectedLog = $_GET['log'];
    $logContent = file_get_contents($logDir . '/' . $selectedLog);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: #4a90e2;
            margin-bottom: 20px;
        }
        
        .email-logs {
            display: flex;
            gap: 20px;
        }
        
        .log-list {
            width: 300px;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .log-list h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }
        
        .log-files {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .log-files li {
            margin-bottom: 8px;
        }
        
        .log-files a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
            transition: background-color 0.2s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .log-files a:hover {
            background-color: #f0f0f0;
        }
        
        .log-files a.active {
            background-color: #e7f3ff;
            color: #4a90e2;
            font-weight: 500;
        }
        
        .log-content {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .log-content h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }
        
        .log-display {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.5;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .no-logs {
            color: #6c757d;
            font-style: italic;
            padding: 20px;
            text-align: center;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4a90e2;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .tools {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .tool-btn {
            background-color: #4a90e2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .tool-btn:hover {
            background-color: #357abd;
        }

        @media (max-width: 768px) {
            .email-logs {
                flex-direction: column;
            }
            
            .log-list {
                width: 100%;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <h1>Email Logs</h1>
        
        <div class="tools">
            <a href="simple-mail-test.php" class="tool-btn">
                <i class="fas fa-envelope"></i> Send Test Email
            </a>
            <?php if (!empty($logFiles)): ?>
            <a href="forgot-password.php" class="tool-btn">
                <i class="fas fa-key"></i> Test Password Reset
            </a>
            <?php endif; ?>
        </div>
        
        <div class="email-logs">
            <div class="log-list">
                <h2>Available Logs</h2>
                <?php if (empty($logFiles)): ?>
                    <p class="no-logs">No email logs found.</p>
                <?php else: ?>
                    <ul class="log-files">
                        <?php foreach ($logFiles as $log): ?>
                            <li>
                                <a href="?log=<?php echo urlencode($log); ?>" 
                                   class="<?php echo $selectedLog === $log ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($log); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="log-content">
                <h2>Email Content</h2>
                <?php if (empty($selectedLog)): ?>
                    <p class="no-logs">Select a log file to view its content.</p>
                <?php else: ?>
                    <div class="log-display"><?php echo htmlspecialchars($logContent); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; color: #6c757d; font-size: 14px; text-align: center;">
            <p>In development mode, emails are logged instead of being sent. This page allows you to view those logs.</p>
            <p>To send actual emails, you would need to configure a mail server or switch to using an SMTP service like SendGrid or Mailgun.</p>
        </div>
    </div>
</body>
</html> 