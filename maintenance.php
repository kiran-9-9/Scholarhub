<?php
// Ensure we have access to constants and settings
if (!defined('APP_NAME')) {
    require_once 'includes/init.php';
}

// Get the site's contact email from settings
$db = Database::getInstance();
$pdo = $db->getConnection();
$settings = new Settings($pdo);
$contact_email = $settings->get('contact_email', SMTP_FROM_EMAIL); // Fallback to SMTP email if not set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --text-color: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--text-color);
        }
        
        .maintenance-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 90%;
            animation: fadeIn 0.6s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .maintenance-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            animation: wrench 2s ease infinite;
        }
        
        @keyframes wrench {
            0% { transform: rotate(0deg); }
            20% { transform: rotate(-15deg); }
            40% { transform: rotate(15deg); }
            60% { transform: rotate(-15deg); }
            80% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .estimated-time {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            display: inline-block;
            animation: pulse 2s ease infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .contact-info {
            font-size: 0.9rem;
            color: #777;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .contact-info a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .contact-info a:hover {
            color: #357abd;
            text-decoration: underline;
        }

        .admin-link {
            margin-top: 2rem;
            font-size: 0.9rem;
        }

        .admin-link a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .admin-link a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <i class="fas fa-tools maintenance-icon"></i>
        <h1>Website Under Maintenance</h1>
        <p>We're currently performing some scheduled maintenance on our website to improve your experience. We apologize for any inconvenience this may cause.</p>
        
        <div class="estimated-time">
            <i class="fas fa-clock"></i>
            <strong>Estimated Completion Time:</strong><br>
            Please check back in a few hours
        </div>
        
        <p>Our team is working hard to complete the maintenance as quickly as possible. Thank you for your patience and understanding.</p>
        
        <div class="contact-info">
            <p>If you need immediate assistance, please contact us at:<br>
            <a href="mailto:scholarhub517@gmail.com"><i class="fas fa-envelope"></i> scholarhub517@gmail.com</a></p>
        </div>
    </div>
</body>
</html> 