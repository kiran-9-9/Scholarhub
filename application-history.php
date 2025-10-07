<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use the init file instead of manually starting session
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

// Get first name and last name
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);

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

// Fetch ALL user activity logs
$userId = $user['id'];
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$all_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get activity count
$activityCount = count($all_activities);

// Pagination
$itemsPerPage = 15;
$totalPages = ceil($activityCount / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get paginated activity logs
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$userId, $itemsPerPage, $offset]);
$paginated_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Also fetch related notifications
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Navbar Styles */
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

        /* Container Layout */
        .dashboard-container {
            display: flex;
            margin-top: 90px;
            min-height: calc(100vh - 90px);
        }

        .sidebar {
            position: fixed;
            top: 90px;
            left: 0;
            width: 260px;
            height: calc(100vh - 90px);
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 100;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            background: #f8f9fa;
            min-height: calc(100vh - 90px);
        }
        
        .main-content h1:first-child {
            margin-top: 0;
            padding-top: 10px;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }

        .activity-header h1 {
            font-size: 1.75rem;
            color: #2c3e50;
            margin: 0;
        }

        .back-button {
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

        .back-button:hover {
            background: #357ABD;
            transform: translateX(-2px);
        }

        .back-button i {
            font-size: 0.9rem;
        }

        .activity-subheader {
            color: #666;
            margin-bottom: 2rem;
        }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .activity-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #4A90E2;
        }

        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4A90E2;
            border: 2px solid white;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.2);
            transition: all 0.3s ease;
        }

        .activity-item:hover::before {
            background: #2c3e50;
            transform: scale(1.2);
        }

        .activity-type {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-type i {
            color: #4A90E2;
        }

        .activity-description {
            color: #555;
            margin-bottom: 0.5rem;
            line-height: 1.5;
            padding-left: 1.5rem;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 1.5rem;
        }

        .activity-time i {
            font-size: 0.95rem;
            color: #4A90E2;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            background: white;
            color: #555;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #f8f9fa;
            border-color: #4A90E2;
        }

        .pagination .active {
            background: #4A90E2;
            color: white;
            border-color: #4A90E2;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* No Activity Message */
        .no-activity {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .no-activity i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-activity p {
            color: #888;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading i {
            font-size: 2rem;
            color: #4A90E2;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
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
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
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
                <a href="notifications.php" class="menu-item">
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
            <div class="activity-header">
                <div>
                    <h1>Activity History</h1>
                    <p class="activity-subheader">View all your recent activities and notifications</p>
                </div>
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
            
            <div class="activity-subheader">
                <p>Showing <?php echo $activityCount > 0 ? min($itemsPerPage, $activityCount) : 0; ?> of <?php echo $activityCount; ?> activities</p>
            </div>

            <div class="activity-timeline">
                <?php if (empty($paginated_activities)): ?>
                    <div class="no-activity">
                        <i class="fas fa-history"></i>
                        <p>No activity found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($paginated_activities as $activity): ?>
                        <div class="activity-item">
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
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
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

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
    </script>
</body>
</html> 