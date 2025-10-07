<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Notification.php';

// Check if user is logged in and is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['application_id']) || !isset($_POST['status'])) {
            throw new Exception("Missing required parameters");
        }

        $applicationId = $_POST['application_id'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'] ?? '';

        // Start transaction
        $pdo->beginTransaction();

        // Get application details with user and scholarship info
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                s.scholarship_name,
                s.amount,
                u.id as user_id,
                u.full_name,
                u.email
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            throw new Exception("Application not found");
        }

        // Update application status
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET status = ?, 
                admin_remarks = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$status, $remarks, $applicationId]);

        // Initialize notification system
        $notification = new Notification();

        // Prepare notification content based on status
        switch ($status) {
            case 'approved':
                $title = "Congratulations! Application Approved";
                $message = "Your application for {$application['scholarship_name']} scholarship has been approved. " . 
                          "Amount: ₹" . number_format($application['amount'], 2);
                if ($remarks) {
                    $message .= "\nAdmin Remarks: $remarks";
                }
                $type = 'approval';
                break;

            case 'rejected':
                $title = "Application Status Update";
                $message = "Your application for {$application['scholarship_name']} scholarship was not approved.";
                if ($remarks) {
                    $message .= "\nReason: $remarks";
                }
                $type = 'rejection';
                break;

            default:
                $title = "Application Status Update";
                $message = "Your application for {$application['scholarship_name']} is under review.";
                if ($remarks) {
                    $message .= "\nNote: $remarks";
                }
                $type = 'system';
        }

        // Send notification to user
        $notificationSent = $notification->send(
            $application['user_id'],
            $title,
            $message,
            $type,
            $applicationId
        );

        if (!$notificationSent) {
            throw new Exception("Failed to send notification to user");
        }

        // Log the notification
        error_log("Notification sent to user {$application['user_id']} for application {$applicationId} - Status: {$status}");

        $pdo->commit();
        $_SESSION['success'] = "Application status updated and notification sent successfully";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error processing application: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect back to applications page
    header("Location: view-applications.php");
    exit();
}

// If not POST request, redirect to applications page
header("Location: view-applications.php");
exit(); 