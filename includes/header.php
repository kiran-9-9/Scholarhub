<?php
require_once 'Auth.php';
require_once 'Notifications.php';

$auth = new Auth();
$notifications = Notifications::getInstance();
$user = null;
$unread_messages = 0;
$unread_notifications = 0;

if ($auth->isLoggedIn()) {
    $user = $auth->getUserData();
    $unread_messages = $notifications->getUnreadMessagesCount($user['id']);
    $unread_notifications = $notifications->getUnreadNotificationsCount($user['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/main.js" defer></script>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --error-color: #dc3545;
            --background-color: #f8f9fa;
            --text-color: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
        }

        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: var(--background-color);
        }

        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }

        .user-menu-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1000;
        }

        .user-menu-content a {
            color: var(--text-color);
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: block;
        }

        .user-menu-content a:hover {
            background-color: var(--background-color);
        }

        .user-menu:hover .user-menu-content {
            display: block;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
                
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="nav-links">
                    <a href="index.php" <?php echo ($current_page === 'home') ? 'class="active"' : ''; ?>>Home</a>
                    <a href="scholarships.php" <?php echo ($current_page === 'scholarships') ? 'class="active"' : ''; ?>>Scholarships</a>
                    <a href="about.php" <?php echo ($current_page === 'about') ? 'class="active"' : ''; ?>>About</a>
                    <a href="contact.php" <?php echo ($current_page === 'contact') ? 'class="active"' : ''; ?>>Contact</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="messages.php" class="notification-badge" data-count="<?php echo $unread_messages ?: ''; ?>">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="notifications.php" class="notification-badge" data-count="<?php echo $unread_notifications ?: ''; ?>">
                            <i class="fas fa-bell"></i>
                        </a>
                        <a href="dashboard.php" <?php echo ($current_page === 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a>
                        <a href="logout.php" class="btn btn-primary">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <div class="container" style="padding-top: 2rem;"> 