<?php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'includes/Database.php';

// Create auth instance
$auth = new Auth();

// Check if user is logged in
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

// Get application ID from query string
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$applicationId) {
    header("Location: applications.php");
    exit();
}

// Get database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Debug: Log the application ID and user ID
    error_log("Fetching application details for ID: " . $applicationId . ", User ID: " . $user['id']);
    
    // Fetch application details with documents
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.status,
            a.application_date,
            a.additional_info,
            a.created_at,
            s.scholarship_name,
            s.amount,
            s.deadline,
            s.requirements,
            GROUP_CONCAT(
                DISTINCT
                CONCAT(
                    ad.document_type, '|',
                    ad.file_path, '|',
                    IFNULL(ad.original_filename, ad.file_path), '|',
                    IFNULL(ad.file_size, 0)
                ) SEPARATOR ';;'
            ) as documents
        FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        LEFT JOIN application_documents ad ON a.id = ad.application_id
        WHERE a.id = ? AND a.user_id = ?
        GROUP BY a.id, a.status, a.application_date, a.additional_info, a.created_at,
                 s.scholarship_name, s.amount, s.deadline, s.requirements
    ");
    
    try {
        $stmt->execute([$applicationId, $user['id']]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            error_log("No application found for ID: " . $applicationId . " and User ID: " . $user['id']);
            $_SESSION['error'] = "Application not found or access denied.";
            header("Location: applications.php");
            exit();
        }

        // Debug: Log successful fetch
        error_log("Successfully fetched application details for ID: " . $applicationId);

        // Process documents
        $documentList = [];
        if (!empty($application['documents'])) {
            $documents = explode(';;', $application['documents']);
            foreach ($documents as $doc) {
                list($type, $path, $origName, $size) = explode('|', $doc);
                $documentList[] = [
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $origName,
                    'size' => $size
                ];
            }
        }

    } catch (PDOException $e) {
        error_log("Database Error in view-application-details.php: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Error Info: " . print_r($stmt->errorInfo(), true));
        $_SESSION['error'] = "Failed to fetch application details. Please try again later.";
        header("Location: applications.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Database Error in view-application-details.php: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch application details. Please try again later.";
    header("Location: applications.php");
    exit();
} catch (Exception $e) {
    error_log("General Error in view-application-details.php: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again later.";
    header("Location: applications.php");
    exit();
}

// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return number_format($bytes) . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - ScholarHub</title>
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

        .navbar .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .navbar .logo i {
            font-size: 24px;
            color: #4A90E2;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            text-decoration: none;
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
            color: #4A90E2;
        }

        .nav-links .login-btn {
            background: #4A90E2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .nav-links .login-btn:hover {
            background: #357abd;
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
            width: 250px;
            height: calc(100vh - 90px);
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background: #f8f9fa;
            min-height: calc(100vh - 90px);
        }
        .main-content h1:first-child {
            margin-top: 0;
            padding-top: 10px;
        }

        @media (max-width: 768px) {
            .quick-links {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Profile Specific Styles */
        .profile-header {
            margin-bottom: 0;
        }

        .profile-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #666;
            font-size: 16px;
        }

        .profile-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.10);
            padding: 40px 32px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            background: linear-gradient(135deg, #4A90E2 60%, #6dd5ed 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.10);
        }

        .profile-avatar i {
            font-size: 48px;
            color: white;
        }

        .profile-details {
            text-align: center;
        }

        .profile-name {
            font-size: 26px;
            color: #222;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .profile-meta, .profile-email {
            color: #666;
            font-size: 15px;
            margin-bottom: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px 32px;
            margin-bottom: 28px;
        }

        .info-item {
            background: #f4f8fb;
            border-radius: 8px;
            padding: 18px 16px;
            border: none;
            box-shadow: 0 1px 3px rgba(44, 62, 80, 0.04);
        }

        .info-label {
            color: #888;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .info-value {
            color: #222;
            font-size: 16px;
            font-weight: 500;
        }

        .status-active {
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .profile-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 18px;
        }

        .btn, .btn:hover, .btn:focus, .menu-item, .menu-item:hover, .menu-item:focus, .nav-links a, .nav-links a:hover, .nav-links a:focus, .login-btn, .login-btn:hover, .login-btn:focus, a, a:hover, a:focus {
            text-decoration: none !important;
        }

        .btn {
            padding: 10px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.06);
            transition: background 0.2s, color 0.2s;
        }

        .btn-edit {
            background: #4A90E2;
            color: white;
        }

        .btn-edit:hover {
            background: #357abd;
        }

        .btn-change-password {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-change-password:hover {
            background: #e3e8ee;
        }

        @media (max-width: 900px) {
            .profile-section {
                padding: 24px 8px;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }
        }

        .application-details {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: #f4f8fb;
            border-radius: 8px;
            padding: 1.2rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-value {
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .documents-section {
            margin-top: 2rem;
        }

        .document-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .document-item {
            background: #f8f9fa;
            padding: 1.2rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .document-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .document-info {
            flex: 1;
        }

        .document-type {
            font-weight: 600;
            color: var(--text-color);
            display: block;
            margin-bottom: 0.25rem;
        }

        .document-name {
            color: #666;
            font-size: 0.9rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .document-size {
            color: #999;
            font-size: 0.8rem;
        }

        .btn-view-doc {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .btn-view-doc:hover {
            background: var(--primary-dark);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn-back {
            padding: 0.75rem 1.5rem;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .additional-info {
            background: #f4f8fb;
            border-radius: 8px;
            padding: 1.5rem;
            color: #2c3e50;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .document-list {
                grid-template-columns: 1fr;
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
                <h1>Application Details</h1>
                <p>View your scholarship application information</p><br>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="application-details">
                <h2 class="section-title">Scholarship Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Scholarship Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($application['scholarship_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Amount</div>
                        <div class="info-value">₹<?php echo number_format($application['amount'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Application Date</div>
                        <div class="info-value"><?php echo date('F d, Y', strtotime($application['application_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($application['additional_info'])): ?>
                <h2 class="section-title">Additional Information</h2>
                <div class="additional-info">
                    <?php echo nl2br(htmlspecialchars($application['additional_info'])); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($documentList)): ?>
                <div class="documents-section">
                    <h2 class="section-title">Submitted Documents</h2>
                    <div class="document-list">
                        <?php foreach ($documentList as $doc): ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-type"><?php echo htmlspecialchars($doc['type']); ?></span>
                                    <span class="document-name"><?php echo htmlspecialchars($doc['original_name']); ?></span>
                                    <span class="document-size"><?php echo formatFileSize($doc['size']); ?></span>
                                </div>
                                <a href="<?php echo htmlspecialchars($doc['path']); ?>" class="btn-view-doc" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <a href="applications.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script src="js/main.js"></script>
</body>
</html> 