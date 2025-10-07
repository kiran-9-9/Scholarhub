<?php
// Ensure consistent session handling with init.php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'config/database.php';

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

// Set userData and fullName for consistency
$userId = $user['id'];
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);

// Fetch user's applications
$db = Database::getInstance();
$pdo = $db->getConnection();

// Debug: Log the user ID
error_log("Fetching applications for user ID: " . $userId);

$stmt = $pdo->prepare("
    SELECT DISTINCT
        a.id as application_id,
        a.status,
        a.created_at,
        a.additional_info,
        s.id as scholarship_id,
        s.scholarship_name,
        s.amount,
        s.deadline
    FROM applications a
    JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");

$stmt->execute([$userId]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - ScholarHub</title>
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
            display: flex;
            align-items: center;
            padding: 0 2rem;
            justify-content: space-between;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .navbar .logo i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links .login-btn {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .nav-links .login-btn:hover {
            background: var(--primary-dark);
        }

        /* Container Layout */
        .dashboard-container {
            display: flex;
            margin-top: 90px;
            min-height: calc(100vh - 90px);
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: #f8f9fa;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #6c757d;
        }

        .applications-list {
            display: grid;
            gap: 1.5rem;
        }

        .application-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .application-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .application-content {
            flex: 1;
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .application-title {
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .application-meta {
            display: flex;
            gap: 2rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .application-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-approved {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-rejected {
            background: #ffebee;
            color: #c62828;
        }

        .btn-view {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .btn-view:hover {
            background: var(--primary-dark);
        }

        .no-applications {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-applications i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .no-applications p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .application-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .application-header {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar {
                padding: 0 1rem;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>ScholarHub</h1>
        </a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="contact.php">Contact Us</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>My Applications</h1>
                <p>Track and manage your scholarship applications</p>
            </div>

            <div class="applications-list">
                <?php if (empty($applications)): ?>
                    <div class="no-applications">
                        <i class="fas fa-folder-open"></i>
                        <p>You haven't applied for any scholarships yet.</p>
                        <a href="scholarships.php" class="btn-primary">
                            <i class="fas fa-search"></i> Browse Scholarships
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <div class="application-card">
                            <div class="application-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="application-content">
                                <div class="application-header">
                                    <div>
                                        <h3 class="application-title"><?php echo htmlspecialchars($application['scholarship_name']); ?></h3>
                                        <div class="application-meta">
                                            <span>
                                                <i class="fas fa-calendar"></i>
                                                Applied: <?php echo date('M d, Y', strtotime($application['created_at'])); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-money-bill-wave"></i>
                                                Amount: ₹<?php echo number_format($application['amount'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                                <a href="view-application-details.php?id=<?php echo $application['application_id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
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