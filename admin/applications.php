<?php
// Include necessary files
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/NotificationManager.php';

// Create auth instance
$auth = new Auth();

// Check admin authentication
if (!$auth->isAdmin()) {
    error_log('Applications Page - Auth check failed, redirecting to admin login');
    header("Location: login.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Create notification manager instance
$notificationManager = new NotificationManager();

// Handle application status updates if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $action = $_POST['action'];
    $applicationId = $_POST['application_id'];
    
    try {
        if ($action === 'approve' || $action === 'reject') {
            // Log session data for debugging
            error_log("Admin session data: " . print_r($_SESSION, true));

            // Verify admin session
            if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
                error_log("Admin session variables missing. Required: admin_id, admin_username");
                throw new Exception("Admin session not properly initialized");
            }

            // Verify admin exists in database
            $adminStmt = $pdo->prepare("SELECT id FROM admin WHERE id = ? AND username = ?");
            $adminStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_username']]);
            if ($adminStmt->rowCount() === 0) {
                error_log("Admin not found in database. ID: {$_SESSION['admin_id']}, Username: {$_SESSION['admin_username']}");
                throw new Exception("Invalid admin credentials");
            }

            // Get application details before updating
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.email, s.scholarship_name 
                FROM applications a
                JOIN users u ON a.user_id = u.id
                JOIN scholarships s ON a.scholarship_id = s.id
                WHERE a.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                // Update application status with review information
                $stmt = $pdo->prepare("
                    UPDATE applications 
                    SET status = ?, 
                        reviewed_by = ?, 
                        reviewed_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                
                // Log the values being used for the update
                error_log("Updating application with values: " . print_r([
                    'status' => $action === 'approve' ? 'approved' : 'rejected',
                    'reviewed_by' => $_SESSION['admin_id'],
                    'application_id' => $applicationId
                ], true));
                
                $result = $stmt->execute([
                    $action === 'approve' ? 'approved' : 'rejected',
                    $_SESSION['admin_id'],
                    $applicationId
                ]);
                
                if (!$result) {
                    error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to execute update statement");
                }
                
                if ($stmt->rowCount() === 0) {
                    error_log("No rows were updated for application ID: " . $applicationId);
                    throw new Exception("No application was updated");
                }

                // Send email notification
                $emailSubject = "Scholarship Application " . ucfirst($action === 'approve' ? 'Approved' : 'Rejected');
                $emailBody = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: " . ($action === 'approve' ? '#28a745' : '#dc3545') . "; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Application {$action}d</h2>
                        </div>
                        <div class='content'>
                            <p>Dear {$application['full_name']},</p>
                            <p>Your application for the <strong>{$application['scholarship_name']}</strong> scholarship has been {$action}d.</p>
                            " . ($action === 'approve' ? "
                            <p>Congratulations! Your application has been approved. We will contact you shortly with further instructions.</p>
                            " : "
                            <p>We regret to inform you that your application has been rejected. We encourage you to apply for other scholarships that match your qualifications.</p>
                            ") . "
                            <p>Best regards,<br>ScholarHub Team</p>
                        </div>
                        <div class='footer'>
                            This is an automated message, please do not reply.
                        </div>
                    </div>
                </body>
                </html>
                ";

                // Send the email notification
                $notificationManager->sendEmail(
                    $application['email'],
                    $application['full_name'],
                    $emailSubject,
                    $emailBody
                );

                // Add in-app notification
                $notificationTitle = "Application " . ucfirst($action);
                $notificationMessage = "Your application for {$application['scholarship_name']} has been {$action}d.";
                $notificationType = $action === 'approve' ? 'success' : 'warning';
                
                $notificationManager->addNotification(
                    $application['user_id'],
                    $notificationTitle,
                    $notificationMessage,
                    $notificationType
                );

                $_SESSION['success'] = "Application status updated successfully and notifications sent.";
            } else {
                throw new Exception("Application not found");
            }
            
            // Redirect to prevent form resubmission
            header("Location: applications.php");
            exit();
        }
    } catch (PDOException $e) {
        // error_log("Error updating application status: " . $e->getMessage());
        // $_SESSION['error'] = "Failed to update application status.";
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while processing your request.";
    }
}

// Fetch all applications with user and scholarship details
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.status,
            a.application_date,
            a.additional_info,
            u.full_name, 
            u.email, 
            s.scholarship_name,
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
        JOIN users u ON a.user_id = u.id
        JOIN scholarships s ON a.scholarship_id = s.id
        LEFT JOIN application_documents ad ON a.id = ad.application_id
        GROUP BY a.id, u.full_name, u.email, s.scholarship_name
        ORDER BY a.application_date DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process documents for each application
    foreach ($applications as &$application) {
        if (!empty($application['documents'])) {
            $docArray = [];
            $documents = explode(';;', $application['documents']);
            foreach ($documents as $doc) {
                list($type, $path, $origName, $size) = explode('|', $doc);
                $docArray[] = [
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $origName,
                    'size' => number_format($size / 1024 / 1024, 2) . ' MB'
                ];
            }
            $application['document_list'] = $docArray;
        } else {
            $application['document_list'] = [];
        }
    }
    unset($application); // Clear reference

} catch (PDOException $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch applications.";
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - ScholarHub Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        /* Additional styles specific to applications page */
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--text-color);
        }

        .applications-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            color: var(--text-color);
            font-size: 1.2rem;
            margin: 0;
        }

        .filters {
            display: flex;
            gap: 1rem;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: white;
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

        .action-btn-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }

        .btn {
            min-width: 90px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
        }

        .btn-reject {
            background-color: var(--error-color);
            color: white;
        }

        .btn-reject:hover {
            background-color: #c82333;
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #357abd;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }

        /* Document viewer styles */
        .documents-cell {
            max-width: 200px;
        }

        .document-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
        }

        .document-icon {
            color: #6c757d;
            font-size: 1rem;
        }

        .document-link {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .document-link:hover {
            text-decoration: underline;
        }

        .document-size {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .document-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .document-modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .document-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .document-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }

        .document-modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .document-preview {
            width: 100%;
            height: 600px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Applications Management</h1>
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

            <div class="applications-table">
                <div class="table-header">
                    <h2>All Applications</h2>
                    <div class="filters">
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Email</th>
                            <th>Scholarship</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No applications found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($application['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($application['email']); ?></td>
                                    <td><?php echo htmlspecialchars($application['scholarship_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btn-group">
                                        <?php if ($application['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <a href="view-application.php?id=<?php echo $application['application_id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Status filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('tbody tr');

        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value.toLowerCase();
            
            tableRows.forEach(row => {
                const statusCell = row.querySelector('.status-badge');
                if (!statusCell) return; // Skip if no status badge (like "No applications found" row)
                
                const rowStatus = statusCell.textContent.trim().toLowerCase();
                if (selectedStatus === '' || rowStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 