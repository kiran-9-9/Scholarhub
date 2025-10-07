<?php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'includes/Database.php';
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
$fullName = isset($user['full_name']) ? $user['full_name'] : '';
$userEmail = isset($user['email']) ? $user['email'] : '';
$userId = $user['id']; // Store user ID for later use

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get scholarship ID from URL
$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user has already applied for this scholarship
$stmt = $pdo->prepare("
    SELECT id, status, application_date 
    FROM applications 
    WHERE user_id = ? AND scholarship_id = ?
");
$stmt->execute([$userId, $scholarship_id]);
$existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingApplication) {
    $status = ucfirst($existingApplication['status']);
    $applicationDate = date('F d, Y', strtotime($existingApplication['application_date']));
    
    // Debug log
    error_log("Duplicate application detected - Setting error message");
    
    // Set a more detailed error message
    $_SESSION['error'] = "You have already applied for this scholarship on {$applicationDate}. Current status: {$status}";
    
    // Set notification data
    $_SESSION['show_notification'] = true;
    $_SESSION['notification_type'] = 'warning';
    $_SESSION['notification_title'] = 'Duplicate Application';
    $_SESSION['notification_message'] = "You have already applied for this scholarship on {$applicationDate}.<br>Current Status: <strong>{$status}</strong>";
    
    // Debug log
    error_log("Session data set: " . print_r($_SESSION, true));
    
    // Log the duplicate application attempt
    error_log("Duplicate application attempt - User ID: {$userId}, Scholarship ID: {$scholarship_id}, Existing Status: {$status}");
    
    header("Location: scholarships.php");
    exit();
}

// Fetch scholarship details
try {
    $stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ? AND status = 'active'");
    $stmt->execute([$scholarship_id]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$scholarship) {
        $_SESSION['error'] = "Scholarship not found or not active.";
        header("Location: scholarships.php");
        exit();
    }
    
    // Get scholarship name for display
    $scholarship_name = htmlspecialchars($scholarship['scholarship_name']);

    // Fetch required documents
    $stmt = $pdo->prepare("SELECT * FROM scholarship_document_requirements WHERE scholarship_id = ?");
    $stmt->execute([$scholarship_id]);
    $required_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching scholarship details.";
    header("Location: scholarships.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
        // Initialize notification system
        $notification = new Notification();
        
        // Start transaction before checking for duplicates
            $pdo->beginTransaction();
        
        // Check for duplicate applications with row locking
        $stmt = $pdo->prepare("
            SELECT id, status 
            FROM applications 
            WHERE user_id = ? AND scholarship_id = ? 
            FOR UPDATE
        ");
        $stmt->execute([$userId, $scholarship_id]);
        $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingApplication) {
            $pdo->rollBack();
            $notification->send(
                $userId,
                "Duplicate Application Detected",
                "You have already applied for the scholarship: " . $scholarship['scholarship_name'],
                'duplicate',
                $scholarship_id
            );
            throw new Exception("You have already applied for this scholarship. Current status: " . ucfirst($existingApplication['status']));
        }
            
            // Insert application
            $stmt = $pdo->prepare("
            INSERT INTO applications (user_id, scholarship_id, status, additional_info)
            VALUES (?, ?, 'pending', ?)
        ");
        $stmt->execute([$userId, $scholarship_id, json_encode($additional_info)]);
            $applicationId = $pdo->lastInsertId();

        // Handle document uploads
        $upload_dir = 'uploads/applications/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        $uploadErrors = [];
        $maxFileSize = 5 * 1024 * 1024; // 5MB limit

        // Process each required document
        foreach ($required_docs as $doc) {
            $doc_field = 'document_' . $doc['id'];
            
            if (!isset($_FILES[$doc_field])) {
                if ($doc['is_required']) {
                    $uploadErrors[] = "Missing required document: " . $doc['document_name'];
                }
                continue;
            }

            $file = $_FILES[$doc_field];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE && !$doc['is_required']) {
                    continue;
                }
                $uploadErrors[] = "Error uploading " . $doc['document_name'] . ": " . 
                                getUploadErrorMessage($file['error']);
                continue;
            }

            // Validate file size
            if ($file['size'] > $maxFileSize) {
                $uploadErrors[] = $doc['document_name'] . " exceeds maximum file size of 5MB";
                continue;
            }

            // Validate file type
            $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_types)) {
                $uploadErrors[] = "Invalid file type for " . $doc['document_name'] . 
                                ". Allowed types: " . implode(', ', $allowed_types);
                continue;
                    }

                    // Generate unique filename
            $new_filename = uniqid('doc_') . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
            $file_path = $upload_dir . $new_filename;
                    
                    // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploadErrors[] = "Failed to save " . $doc['document_name'];
                continue;
            }

            // Save document record
            $stmt = $pdo->prepare("INSERT INTO application_documents (application_id, document_type, file_path, original_filename, file_size) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $applicationId, 
                $doc['document_type'] ?? $doc['document_name'], 
                $file_path,
                $file['name'],
                $file['size']
            ]);
        }

        // Check if there were any upload errors
        if (!empty($uploadErrors)) {
            throw new Exception(implode("<br>", $uploadErrors));
        }

        // Send notification for successful application
        $notification->send(
            $userId,
            "Application Submitted Successfully",
            "Your application for " . $scholarship['scholarship_name'] . " has been submitted and is under review.",
            'application',
                $applicationId
            );
            
            $pdo->commit();
        $_SESSION['success'] = "Application submitted successfully!";
            header("Location: applications.php");
            exit();
            
        } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: apply-scholarship.php?id=" . $scholarship_id);
        exit();
    }
}

// Helper function to get upload error messages
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension";
        default:
            return "Unknown upload error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $scholarship_name; ?> - Apply for Scholarship - ScholarHub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
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
            padding: 0 2rem;
        }

        .dashboard-container {
            display: flex;
            margin-top: 90px;
            min-height: calc(100vh - 90px);
        }

        .sidebar {
            width: 260px;
            position: fixed;
            top: 90px;
            left: 0;
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
        }

        .page-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .page-header .scholarship-name {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #fff;
            font-weight: 600;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .scholarship-header {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .scholarship-header h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.8rem;
    }

    .scholarship-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
    }

    .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-item strong {
            display: block;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-item span {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .application-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .documents-section {
            margin-top: 2rem;
        }

        .documents-section h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .document-upload {
            background: #fff;
            border: 1px solid #e9ecef;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .document-upload:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .document-upload label {
            display: block;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .required-label {
            color: #dc3545;
            margin-left: 5px;
            font-weight: normal;
        }

        .file-input-wrapper {
            position: relative;
            margin-bottom: 0.5rem;
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 0.5rem;
            border: 1px dashed #ced4da;
        border-radius: 4px;
            background: #f8f9fa;
            cursor: pointer;
        }

        input[type="file"]:hover {
            background: #e9ecef;
        }

        .file-formats {
            display: block;
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .btn-submit {
            background: #007bff;
        color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fff5f5;
            color: #dc3545;
            border: 1px solid #fcc;
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
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="scholarships.php">Scholarships</a>
        <a href="applications.php">Applications</a>
        <a href="notifications.php">Notifications</a>
        <a href="contact.php">Contact Us</a>
        <a href="logout.php">Logout</a>
    </div>

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
                    <div class="user-email"><?php echo htmlspecialchars($userEmail); ?></div>
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
                <a href="applications.php" class="menu-item active">
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
            <div class="page-header">
                <h1>Scholarship Application</h1>
                <div class="scholarship-name"><?php echo $scholarship_name; ?></div>
                <p>Complete the form below to submit your application</p>
            </div>

            <div class="scholarship-header">
                <div class="scholarship-info">
                    <div class="info-item">
                        <strong>Amount</strong>
                        <span>$<?php echo number_format($scholarship['amount'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Application Deadline</strong>
                        <span><?php echo date('F d, Y', strtotime($scholarship['deadline'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Status</strong>
                        <span class="status-badge">Active</span>
                    </div>
                </div>
            </div>

            <div class="container">
                <?php 
                // Debug log
                error_log("Checking for error message in session: " . (isset($_SESSION['error']) ? $_SESSION['error'] : 'No error set'));
                
                if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php 
                // Debug log
                error_log("Checking for notification: " . (isset($_SESSION['show_notification']) ? 'true' : 'false'));
                ?>

                <div class="application-form">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="documents-section">
                            <h3>Required Documents for <?php echo $scholarship_name; ?></h3>
                            <?php foreach ($required_docs as $doc): ?>
                                <div class="document-upload">
                                    <label>
                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                        <?php if ($doc['is_required']): ?>
                                            <span class="required-label">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="document_<?php echo $doc['id']; ?>" 
                                               <?php echo $doc['is_required'] ? 'required' : ''; ?>>
                                        <span class="file-formats">
                                            <i class="fas fa-info-circle"></i>
                                            Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG
                                        </span>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Submit Application
                            </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/notifications.js"></script>
    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        const sidebar = document.querySelector('.sidebar');
        const mobileMenu = document.querySelector('.mobile-menu');

        if (hamburger) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                if (navLinks) navLinks.classList.toggle('active');
                if (sidebar) sidebar.classList.toggle('active');
                if (mobileMenu) mobileMenu.classList.toggle('active');
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (hamburger && !hamburger.contains(e.target) && 
                (!mobileMenu || !mobileMenu.contains(e.target)) &&
                (!navLinks || !navLinks.contains(e.target)) &&
                (!sidebar || !sidebar.contains(e.target))) {
                
                if (hamburger) hamburger.classList.remove('active');
                if (mobileMenu) mobileMenu.classList.remove('active');
                if (navLinks) navLinks.classList.remove('active');
                if (sidebar) sidebar.classList.remove('active');
            }
        });

        // Show notification if session flag is set
        <?php if (isset($_SESSION['show_notification']) && $_SESSION['show_notification']): ?>
            showNotification(
                <?php echo json_encode($_SESSION['notification_message']); ?>,
                <?php echo json_encode($_SESSION['notification_type'] ?? 'error'); ?>,
                <?php echo json_encode($_SESSION['notification_title'] ?? ''); ?>
            );
            <?php 
            unset($_SESSION['show_notification']);
            unset($_SESSION['notification_message']);
            unset($_SESSION['notification_type']);
            unset($_SESSION['notification_title']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html> 