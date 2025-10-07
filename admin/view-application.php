<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Create auth instance
$auth = new Auth();

// Check admin authentication
if (!$auth->isAdmin()) {
    error_log('View Application Page - Auth check failed, redirecting to admin login');
    header("Location: login.php");
    exit();
}

// Get database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection error in view-application.php: " . $e->getMessage());
    $_SESSION['error'] = "Database connection failed. Please try again later.";
    header("Location: applications.php");
    exit();
}

// Get application ID from query string
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$applicationId) {
    $_SESSION['error'] = "Invalid application ID.";
    header("Location: applications.php");
    exit();
}

try {
    // Debug: Log the application ID
    error_log("Admin viewing application ID: " . $applicationId);
    
    // Fetch application row only
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        error_log("No application found for ID: " . $applicationId);
        $_SESSION['error'] = "Application not found.";
        header("Location: applications.php");
        exit();
    }

    // Debug: Log successful application fetch
    error_log("Successfully fetched application for ID: " . $applicationId);

    // Fetch user info
    $user = null;
    if (!empty($application['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$application['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            error_log("User not found for ID: " . $application['user_id']);
        }
    }

    // Fetch scholarship info
    $scholarship = null;
    if (!empty($application['scholarship_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
        $stmt->execute([$application['scholarship_id']]);
        $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$scholarship) {
            error_log("Scholarship not found for ID: " . $application['scholarship_id']);
        }
    }

    // Fetch documents
    try {
        // First check if document_types table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'document_types'");
        $hasDocumentTypes = $stmt->rowCount() > 0;

        if ($hasDocumentTypes) {
            // If document_types exists, use the join query
            $stmt = $pdo->prepare("
                SELECT 
                    ad.id,
                    ad.document_type,
                    ad.file_path,
                    ad.original_filename,
                    ad.file_size,
                    ad.uploaded_at,
                    dt.document_name,
                    dt.description as type_description
                FROM application_documents ad
                LEFT JOIN document_types dt ON ad.document_type = dt.type_code
                WHERE ad.application_id = ?
                ORDER BY dt.display_order, ad.uploaded_at
            ");
        } else {
            // If document_types doesn't exist, use a simpler query
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    document_type,
                    file_path,
                    original_filename,
                    file_size,
                    uploaded_at,
                    document_type as document_name
                FROM application_documents
                WHERE application_id = ?
                ORDER BY uploaded_at
            ");
        }
        
        $stmt->execute([$applicationId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log document count
        error_log("Found " . count($documents) . " documents for application ID: " . $applicationId);
        
    } catch (PDOException $e) {
        error_log("Error fetching documents: " . $e->getMessage());
        $documents = [];
    }

    // Set warning flags
    $missingUser = !$user;
    $missingScholarship = !$scholarship;

} catch (PDOException $e) {
    error_log("Database Error in view-application.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Error Info: " . print_r($stmt->errorInfo(), true));
    $_SESSION['error'] = "Failed to fetch application details. Please try again later.";
    header("Location: applications.php");
    exit();
} catch (Exception $e) {
    error_log("General Error in view-application.php: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again later.";
    header("Location: applications.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - ScholarHub Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #4a90e2;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-value {
            color: #212529;
            font-size: 1rem;
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

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .document-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .document-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .document-icon {
            font-size: 2rem;
            color: #4a90e2;
        }

        .document-info {
            flex: 1;
        }

        .document-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .document-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .document-body {
            padding: 1rem;
        }

        .document-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-document {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.875rem;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-view {
            background: #4a90e2;
            color: white;
        }

        .btn-view:hover {
            background: #357abd;
        }

        .btn-download {
            background: #28a745;
            color: white;
        }

        .btn-download:hover {
            background: #218838;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .warning-banner {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .warning-banner i {
            font-size: 1.25rem;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .documents-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
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

            <div class="container">
                <div class="action-buttons">
                    <a href="applications.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>

                <?php if ($missingUser || $missingScholarship): ?>
                    <div class="warning-banner">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <?php if ($missingUser): ?>
                                <p>Warning: User information is missing or has been deleted.</p>
                            <?php endif; ?>
                            <?php if ($missingScholarship): ?>
                                <p>Warning: Scholarship information is missing or has been deleted.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Application Status Card -->
                <div class="card">
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Application Status
                        </h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Application Date</div>
                                <div class="info-value">
                                    <?php echo date('F j, Y g:i A', strtotime($application['application_date'])); ?>
                                </div>
                            </div>
                            <?php if ($application['reviewed_by']): ?>
                                <div class="info-item">
                                    <div class="info-label">Reviewed By</div>
                                    <div class="info-value">
                                        <?php 
                                            $stmt = $pdo->prepare("SELECT username FROM admin WHERE id = ?");
                                            $stmt->execute([$application['reviewed_by']]);
                                            $reviewer = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($reviewer['username'] ?? 'Unknown');
                                        ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Reviewed At</div>
                                    <div class="info-value">
                                        <?php echo date('F j, Y g:i A', strtotime($application['reviewed_at'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Applicant Information -->
                <div class="card">
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-user"></i>
                            Applicant Information
                        </h2>
                        <?php if ($user): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Phone</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p>User information not available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Scholarship Information -->
                <div class="card">
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-graduation-cap"></i>
                            Scholarship Information
                        </h2>
                        <?php if ($scholarship): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Scholarship Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Amount</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($scholarship['currency'] . ' ' . number_format($scholarship['amount'], 2)); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Deadline</div>
                                    <div class="info-value"><?php echo date('F j, Y', strtotime($scholarship['deadline'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="status-badge status-<?php echo strtolower($scholarship['status']); ?>">
                                            <?php echo ucfirst($scholarship['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="section">
                                <h3 class="section-title">Description</h3>
                                <p><?php echo nl2br(htmlspecialchars($scholarship['description'])); ?></p>
                            </div>
                            
                             
                        <?php else: ?>
                            <p>Scholarship information not available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                
                <!-- Documents -->
                <?php if (!empty($documents)): ?>
                    <div class="card">
                        <div class="section">
                            <h2 class="section-title">
                                <i class="fas fa-file-alt"></i>
                                Submitted Documents
                            </h2>
                            <div class="documents-grid">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="document-card">
                                        <div class="document-header">
                                            <i class="fas fa-file document-icon"></i>
                                            <div class="document-info">
                                                <div class="document-name">
                                                    <?php 
                                                        $docName = $doc['document_name'] ?? $doc['document_type'] ?? 'Unnamed Document';
                                                        echo htmlspecialchars($docName); 
                                                    ?>
                                                </div>
                                                <div class="document-meta">
                                                    <?php 
                                                        $uploadDate = $doc['uploaded_at'] ?? date('Y-m-d H:i:s');
                                                        $fileSize = $doc['file_size'] ?? 0;
                                                        echo date('M j, Y', strtotime($uploadDate)); ?> • 
                                                        <?php echo number_format($fileSize / 1024 / 1024, 2); ?> MB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="document-body">
                                            <div class="document-actions">
                                                <?php 
                                                    $filePath = $doc['file_path'] ?? '';
                                                    $originalFilename = $doc['original_filename'] ?? 'document';
                                                    
                                                    // Debug information
                                                    error_log("Original file path from database: " . $filePath);
                                                    error_log("Original filename from database: " . $originalFilename);
                                                    
                                                    // Get just the filename from the path
                                                    $fileName = !empty($filePath) ? basename($filePath) : '';
                                                    error_log("Extracted filename: " . $fileName);
                                                    
                                                    // Check if file exists in uploads directory
                                                    $fullPath = '../uploads/applications/' . $fileName;
                                                    error_log("Full path to check: " . $fullPath);
                                                    error_log("File exists: " . (file_exists($fullPath) ? 'Yes' : 'No'));
                                                    
                                                    // Set the correct URLs for view and download
                                                    $downloadUrl = !empty($fileName) ? 'download-document.php?file=' . urlencode($fileName) : '#';
                                                    $onclickAttr = empty($fileName) ? 'onclick="alert(\'File not accessible\'); return false;"' : '';
                                                ?>
                                                <a href="<?php echo htmlspecialchars($downloadUrl); ?>" 
                                                   class="btn-document btn-download"
                                                   <?php echo $onclickAttr; ?>>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($application['status'] === 'pending'): ?>
                    <div class="action-buttons">
                        <form method="POST" action="applications.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve">
                                <i class="fas fa-check"></i> Approve Application
                            </button>
                        </form>
                        <form method="POST" action="applications.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-reject">
                                <i class="fas fa-times"></i> Reject Application
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 