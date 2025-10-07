<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use the init file instead of manually starting session
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'config/database.php';

// Debug - check if session contains user_id
if (!isset($_SESSION['user_id'])) {
    echo "<!-- DEBUG: Session missing user_id. Current session: -->";
    echo "<!-- " . print_r($_SESSION, true) . " -->";
}

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Get scholarship data
$db = Database::getInstance();
$pdo = $db->getConnection();

// Function to get appropriate icon for activity type
function getActivityIcon($activityType) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'profile' => 'user-edit',
        'application' => 'file-alt',
        'password' => 'key',
        'email' => 'envelope',
        'address' => 'map-marker-alt',
        'phone' => 'phone',
        'name' => 'user',
        'default' => 'circle'
    ];
    
    return $icons[strtolower($activityType)] ?? $icons['default'];
}

// Fetch recent activity
$userId = $user['id'];
$stmt = $pdo->prepare("
    SELECT n.*, s.scholarship_name, a.status as application_status
    FROM notifications n
    LEFT JOIN scholarships s ON n.message LIKE CONCAT('%', s.scholarship_name, '%')
    LEFT JOIN applications a ON a.user_id = n.user_id AND a.scholarship_id = s.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activity logs - limit to 5 for dashboard
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Show welcome message if just logged in
$welcomeMessage = '';
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in']) {
    $welcomeMessage = "Welcome back, " . htmlspecialchars($user['full_name']) . "! You've successfully logged in.";
    unset($_SESSION['just_logged_in']);
}

// Get first name and last name
$firstName = $user['first_name'];
$lastName = $user['last_name'];
$fullName = trim($firstName . ' ' . $lastName);

// Get user status and related notifications
$stmt = $pdo->prepare("
    SELECT status FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$userStatus = $stmt->fetchColumn();

// Check if user is inactive or suspended
$isRestricted = in_array($userStatus, ['inactive', 'suspended']);

// Get status-related notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND type = 'status_update'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$statusNotification = $stmt->fetch(PDO::FETCH_ASSOC);

// Get unread notification count
$unreadNotificationCount = $auth->getUnreadNotificationCount($userId);

// If user is restricted, prevent any POST actions
if ($isRestricted && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['error'] = "Your account is currently " . $userStatus . ". You cannot perform any actions.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['action'])) {
    $appId = intval($_POST['application_id']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';

    error_log('Admin action triggered: appId=' . $appId . ', action=' . $action);

    // Update application status
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->execute([$action, $appId]);

    // Fetch user_id and scholarship for notification
    $stmt = $pdo->prepare("SELECT a.user_id, a.scholarship_id, s.scholarship_name 
                          FROM applications a 
                          JOIN scholarships s ON a.scholarship_id = s.id 
                          WHERE a.id = ?");
    $stmt->execute([$appId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($app) {
        error_log('Fetched app: userId=' . $app['user_id'] . ', scholarshipName=' . $app['scholarship_name']);
        $userId = $app['user_id'];
        $scholarshipName = $app['scholarship_name'];
        $statusText = $action === 'approved' ? 'approved' : 'rejected';

        // Create notification
        $title = "Application " . ucfirst($statusText);
        $message = "Your application for '" . $scholarshipName . "' has been " . $statusText . ".";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                             VALUES (?, ?, ?, 'application', 0, NOW())");
        $result = $stmt->execute([$userId, $title, $message]);
        if ($result) {
            error_log('Notification inserted for userId=' . $userId);
        } else {
            error_log('Notification insert failed: ' . print_r($stmt->errorInfo(), true));
        }
    } else {
        error_log('No app found for appId=' . $appId);
    }
}

// Get statistics from database
// 1. Count active applications for this user
$activeAppsStmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications 
    WHERE user_id = ? AND status = 'pending'
");
$activeAppsStmt->execute([$userId]);
$activeApplications = $activeAppsStmt->fetchColumn();

// 2. Count all applications for this user
$totalAppsStmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications 
    WHERE user_id = ?
");
$totalAppsStmt->execute([$userId]);
$totalApplications = $totalAppsStmt->fetchColumn();

// 3. Count approved scholarships
$approvedAppsStmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications 
    WHERE user_id = ? AND status = 'approved'
");
$approvedAppsStmt->execute([$userId]);
$approvedScholarships = $approvedAppsStmt->fetchColumn();

// 4. Calculate total awarded amount
$totalAwardedStmt = $pdo->prepare("
    SELECT SUM(s.amount) 
    FROM applications a
    JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.user_id = ? AND a.status = 'approved'
");
$totalAwardedStmt->execute([$userId]);
$totalAwarded = $totalAwardedStmt->fetchColumn();
$totalAwarded = $totalAwarded ? $totalAwarded : 0; // Handle null result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Dashboard Specific Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 90px;
            min-height: 90px;
            display: flex;
            align-items: center;
        }

        .dashboard-container {
            display: flex;
            margin-top: 90px; /* Updated to match navbar height of 90px */
            min-height: calc(100vh - 90px);
        }

        .sidebar {
            width: 260px;
            position: fixed;
            top: 90px; /* Updated to match navbar height */
            left: 0;
            height: calc(100vh - 90px); /* Updated calculation */
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .user-email {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            margin-top: 1rem;
        }

        .menu-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            width: 100%;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 2rem;
        }

        .menu-item:hover::before, .menu-item.active::before {
            transform: scaleY(1);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .menu-item span {
            flex: 1;
            font-size: 0.95rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            background: #f8f9fa;
            min-height: calc(100vh - 90px); /* Updated calculation */
        }
        
        .main-content h1:first-child {
            margin-top: 0;
            padding-top: 10px; /* Add some space at the top of the first heading */
        }

        .dashboard-header {
            margin-top: 0; /* Remove top margin from header */
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            margin-top: 0; /* Remove top margin from heading */
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .stat-card h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Recent Activity */
        .activity-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }

        .activity-header h2 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #4A90E2;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            background: #357ABD;
            transform: translateX(2px);
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-type {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-type i {
            color: #4A90E2;
        }

        .activity-description {
            color: #555;
            line-height: 1.5;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-time i {
            font-size: 0.95rem;
            color: #4A90E2;
        }

        .no-activity {
            text-align: center;
            padding: 2rem;
            color: #888;
        }

        .no-activity i {
            font-size: 2rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 220px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        .btn, .btn:hover, .btn:focus, .menu-item, .menu-item:hover, .menu-item:focus, .nav-links a, .nav-links a:hover, .nav-links a:focus, .login-btn, .login-btn:hover, .login-btn:focus, a, a:hover, a:focus {
            text-decoration: none !important;
        }

        .status-section {
            margin: 1rem 0;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-card {
            padding: 1rem;
        }

        .status-card h3 {
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-indicator.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-indicator.inactive {
            background: #ffebee;
            color: #c62828;
        }

        .status-indicator.suspended {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-indicator i {
            font-size: 0.8rem;
        }

        .status-notification {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
        }

        .status-notification p {
            margin: 0;
            color: var(--text-color);
        }

        .status-notification small {
            display: block;
            margin-top: 0.5rem;
            color: #6c757d;
        }

        .restricted-message {
            margin: 2rem 0;
        }

        .alert {
            padding: 1.5rem;
            border-radius: 8px;
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            color: #ef6c00;
        }

        .alert-warning {
            background: #fff3e0;
            border-color: #ffe0b2;
            color: #ef6c00;
        }

        .alert i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .alert h3 {
            margin: 0.5rem 0;
            color: #ef6c00;
        }

        .alert p {
            margin: 0.5rem 0;
            color: #666;
        }

        .menu-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Notification Badge Style */
        .notification-badge {
            display: inline-block;
            background-color: #e74c3c; /* Red color for unread count */
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            border-radius: 50%;
            padding: 0.2em 0.5em;
            margin-left: 0.5rem;
            vertical-align: super;
            line-height: 1;
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
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <a href="index.php">Home</a>
        <a href="contact.php">Contact Us</a>
        <a href="scholarships.php">Scholarships</a>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="applications.php">Applications</a>
        <a href="notifications.php">Notifications
            <?php if ($unreadNotificationCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadNotificationCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- Dashboard Container -->
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
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <?php if (!$isRestricted): ?>
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
                <a href="notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotificationCount; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
               
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-section">
                <h1>Welcome back, <span class="welcome-name"><?php echo htmlspecialchars($fullName); ?></span>!</h1>
                <p>Here's an overview of your scholarship applications and opportunities.</p><br>
                 
            </div>

            <!-- Stats Grid -->
            <?php if (!$isRestricted): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $activeApplications; ?></h3>
                    <p>Active Applications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $totalApplications; ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $approvedScholarships; ?></h3>
                    <p>Approved Scholarships</p>
                </div>
               
            </div>
            <?php else: ?>
            <div class="restricted-message">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Account <?php echo ucfirst($userStatus); ?></h3>
                    <p>Your account is currently <?php echo $userStatus; ?>. You cannot perform any actions until your account is reactivated.</p>
                    <p>Please contact the administrator for assistance.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <?php if (!$isRestricted): ?>
            <div class="activity-section">
                <div class="activity-header">
                    <h2>Recent Activity</h2>
                    <a href="application-history.php" class="view-all">
                        <i class="fas fa-history"></i>
                        View All
                    </a>
                </div>
                <ul class="activity-list">
                    <?php if ($recent_activities): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="activity-item">
                                <div class="activity-type">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['activity_type']); ?>"></i>
                                    <?php echo htmlspecialchars($activity['activity_type']); ?>
                                </div>
                                <div class="activity-description">
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                </div>
                                <div class="activity-time">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="no-activity">
                            <i class="fas fa-history"></i>
                            <p>No recent activity found.</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

             
        </main>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        const sidebar = document.querySelector('.sidebar');
        const mobileMenu = document.querySelector('.mobile-menu');

        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
            sidebar.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && 
                !mobileMenu.contains(e.target)) {
                sidebar.classList.remove('active');
                navLinks.classList.remove('active');
                mobileMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    </script>
</body>
</html> 