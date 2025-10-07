<?php
// Prevent direct access to this file
if (!defined('BASE_PATH')) {
    die('Direct access to this file is not allowed');
}

// Get user data if logged in
$fullName = isset($user['full_name']) ? $user['full_name'] : '';
$email = isset($user['email']) ? $user['email'] : '';

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread notifications count
require_once 'includes/NotificationManager.php';
$notificationManager = new NotificationManager();
$unreadCount = $notificationManager->getUnreadCount($user['id']);
?>

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
        <a href="dashboard.php" class="menu-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="profile.php" class="menu-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="scholarships.php" class="menu-item <?php echo $current_page === 'scholarships.php' ? 'active' : ''; ?>">
            <i class="fas fa-graduation-cap"></i>
            <span>Scholarships</span>
        </a>
        <a href="applications.php" class="menu-item <?php echo $current_page === 'applications.php' || $current_page === 'view-application-details.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Applications</span>
        </a>
        <a href="notifications.php" class="menu-item <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<style>
.sidebar {
    position: fixed;
        top: 90px;
    left: 0;
        width: 250px;
        height: calc(100vh - 90px);
        background: #2c3e50;
        color: white;
        padding: 20px 0;
    overflow-y: auto;
        z-index: 100;
}

.sidebar-header {
        padding: 0 20px;
        margin-bottom: 20px;
}

.sidebar-header h2 {
        font-size: 1.5rem;
    margin: 0;
}

.user-info {
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 20px;
    display: flex;
    align-items: center;
        gap: 15px;
}

.user-avatar {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar i {
        font-size: 24px;
    color: white;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
        margin-bottom: 5px;
}

.user-email {
        font-size: 0.85rem;
        opacity: 0.8;
}

    .sidebar-menu {
        padding: 0;
}

.menu-item {
        padding: 15px 20px;
    display: flex;
    align-items: center;
        gap: 10px;
        color: white;
    text-decoration: none;
        transition: all 0.3s;
        position: relative;
}

.menu-item i {
        width: 20px;
    text-align: center;
}

.menu-item span {
        flex: 1;
}

    .menu-item:hover, .menu-item.active {
        background: rgba(255, 255, 255, 0.1);
        padding-left: 25px;
}

    .menu-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
    background: var(--primary-color);
        transform: scaleY(0);
        transition: transform 0.3s;
}

    .menu-item:hover::before, .menu-item.active::before {
        transform: scaleY(1);
}

.notification-badge {
    background: #ff4444;
    color: white;
    font-size: 0.75rem;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        font-weight: 600;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
            transition: transform 0.3s;
    }

    .sidebar.active {
        transform: translateX(0);
    }
}
</style> 