<?php
// Start with session handling
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Settings.php';

// Debug logging
error_log('Admin Dashboard - Request received');
error_log('Admin Dashboard - SESSION data: ' . print_r($_SESSION, true));

// Create auth instance
$auth = new Auth();

// Check admin authentication
if (!$auth->isAdmin()) {
    error_log('Admin Dashboard - Auth check failed, redirecting to admin login');
    header("Location: login.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize settings
$settings = new Settings($pdo);
$maintenance_mode = $settings->get('maintenance_mode') === '1';

// Fetch statistics and data for dashboard
try {
    // Fetch total applications
    $stmt = $pdo->query("SELECT COUNT(*) FROM applications");
    $totalApplications = $stmt->fetchColumn();

    // Fetch pending applications
    $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
    $pendingApplications = $stmt->fetchColumn();

    // Fetch approved applications
    $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'");
    $approvedApplications = $stmt->fetchColumn();

    // Fetch rejected applications
    $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'");
    $rejectedApplications = $stmt->fetchColumn();

    // Fetch recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name, u.email, s.scholarship_name 
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN scholarships s ON a.scholarship_id = s.id
        ORDER BY a.application_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent contact messages
    $stmt = $pdo->prepare("
        SELECT * FROM contact_messages
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $contactMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Dashboard - Database error: " . $e->getMessage());
    $error = "An error occurred while fetching dashboard data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ScholarHub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        /* Additional styles specific to dashboard */
        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 1rem;
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

        /* Applications Table */
        .applications-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-header h2 {
            color: var(--text-color);
            font-size: 1.2rem;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--bg-color);
            font-weight: 600;
            color: var(--text-color);
        }

        tr:hover {
            background: var(--bg-color);
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        /* Messages Section */
        .messages-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .message-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .message-sender {
            font-weight: 500;
            color: var(--text-color);
        }

        .message-date {
            font-size: 0.85rem;
            color: #666;
        }

        .message-content {
            color: #444;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Maintenance Notice */
        .maintenance-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }

        .maintenance-notice i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            animation: wrench 3s ease infinite;
        }

        @keyframes wrench {
            0% { transform: rotate(0deg); }
            20% { transform: rotate(-15deg); }
            40% { transform: rotate(15deg); }
            60% { transform: rotate(-15deg); }
            80% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }

        .maintenance-notice strong {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .maintenance-notice p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
            </div>

            <?php if ($maintenance_mode): ?>
                <div class="maintenance-notice">
                    <i class="fas fa-tools"></i>
                    <strong>Maintenance Mode is Active</strong>
                    <p>The website is currently in maintenance mode. Only administrators can access the site.</p>
                    <a href="settings.php" class="btn btn-warning">
                        <i class="fas fa-cog"></i> Disable Maintenance Mode
                    </a>
                </div>
            <?php endif; ?>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $totalApplications; ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $pendingApplications; ?></h3>
                    <p>Pending Applications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $approvedApplications; ?></h3>
                    <p>Approved Applications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $rejectedApplications; ?></h3>
                    <p>Rejected Applications</p>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="applications-table">
                <div class="table-header">
                    <h2>Recent Applications</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Scholarship</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No applications found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($app['full_name']); ?><br>
                                        <small><?php echo htmlspecialchars($app['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['scholarship_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Messages -->
            <div class="messages-section">
                <div class="table-header">
                    <h2>Recent Messages</h2>
                </div>
                <?php if (empty($contactMessages)): ?>
                    <div class="message-item">
                        <p style="text-align: center;">No messages found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($contactMessages as $message): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <span class="message-sender"><?php echo htmlspecialchars($message['name']); ?></span>
                                <span class="message-date"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></span>
                            </div>
                            <div class="message-content">
                                <?php echo htmlspecialchars($message['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 