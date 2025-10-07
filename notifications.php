<?php
// Ensure consistent session handling with init.php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'includes/Notification.php';

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

// Initialize notification system
$notification = new Notification();

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $notificationId = $_POST['notification_id'];
    $notification->markAsRead($notificationId, $user['id']);
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $notification->markAllAsRead($user['id']);
}

// Get notifications
$notifications = $notification->getAll($user['id']);
$unreadCount = $notification->getUnreadCount($user['id']);

// Format user name
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            display: flex;
            margin-top: 90px;
            min-height: calc(100vh - 90px);
            background: #f8f9fa;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            background: #f8f9fa;
            min-height: calc(100vh - 90px);
            width: calc(100% - 260px);
        }

        .notifications-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 25px;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .notification-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .notification-count {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .notification-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .notification-item.unread {
            border-left: 4px solid #3498db;
            background: #f8f9fa;
        }

        .notification-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .notification-message {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #888;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .notification-type {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .type-application { background: #e3f2fd; color: #1976d2; }
        .type-approval { background: #e8f5e9; color: #2e7d32; }
        .type-duplicate { background: #fff3e0; color: #f57c00; }
        .type-system { background: #f5f5f5; color: #616161; }

        .mark-read-btn {
            background: none;
            border: 1px solid #3498db;
            color: #3498db;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .mark-read-btn:hover {
            background: #3498db;
            color: white;
        }

        .mark-all-read-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .mark-all-read-btn:hover {
            background: #2980b9;
        }

        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin: 20px 0;
        }

        .no-notifications i {
            font-size: 3.5rem;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }

        .no-notifications p {
            color: #666;
            font-size: 1.1rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .notifications-container {
                padding: 15px;
            }

            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .mark-read-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <h1>ScholarHub</h1>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="contact.php">Contact Us</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Dashboard</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="scholarships.php" class="menu-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Scholarships</span>
                </a>
                <a href="applications.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications</span>
                </a>
                <a href="notifications.php" class="menu-item active notification-badge" data-count="<?php echo $unreadCount ?: ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="notifications-container">
                <div class="notification-header">
                    <h1>Notifications</h1>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="mark-all-read-btn">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications to display</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                            <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notification-meta">
                                <div>
                                    <span class="notification-type type-<?php echo htmlspecialchars($notif['type']); ?>">
                                        <?php 
                                        switch($notif['type']) {
                                            case 'application':
                                                echo '<i class="fas fa-file-alt"></i> Application';
                                                break;
                                            case 'success':
                                                echo '<i class="fas fa-check-circle"></i> Success';
                                                break;
                                            case 'warning':
                                                echo '<i class="fas fa-exclamation-triangle"></i> Warning';
                                                break;
                                            case 'error':
                                                echo '<i class="fas fa-times-circle"></i> Error';
                                                break;
                                            case 'info':
                                                echo '<i class="fas fa-info-circle"></i> Info';
                                                break;
                                            case 'scholarship':
                                                echo '<i class="fas fa-graduation-cap"></i> Scholarship';
                                                break;
                                            case 'system':
                                                echo '<i class="fas fa-cog"></i> System';
                                                break;
                                            default:
                                                echo '<i class="fas fa-bell"></i> ' . ucfirst($notif['type']);
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" name="mark_read" class="mark-read-btn">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/main.js"></script>
</body>
</html> 