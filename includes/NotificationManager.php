<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $pdo;
    private $mailer;
    private $emailConfig;
    private $emailEnabled = true;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        
        try {
            // Load email configuration
            $configFile = __DIR__ . '/../config/email.php';
            if (!file_exists($configFile)) {
                throw new Exception("Email configuration file not found");
            }
            $this->emailConfig = require $configFile;
            
            // Initialize PHPMailer
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->emailConfig['smtp_host'];
            $this->mailer->SMTPAuth = $this->emailConfig['smtp_auth'];
            $this->mailer->Username = $this->emailConfig['smtp_username'];
            $this->mailer->Password = $this->emailConfig['smtp_password'];
            $this->mailer->SMTPSecure = $this->emailConfig['smtp_secure'];
            $this->mailer->Port = $this->emailConfig['smtp_port'];
            $this->mailer->setFrom(
                $this->emailConfig['from_email'], 
                $this->emailConfig['from_name']
            );
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            $this->emailEnabled = false;
        }
    }

    public function addNotification($userId, $title, $message, $type = 'application') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$userId, $title, $message, $type]);
        } catch (Exception $e) {
            error_log("Failed to add notification: " . $e->getMessage());
            return false;
        }
    }

    public function sendApplicationEmail($userEmail, $userName, $scholarshipName, $applicationId) {
        if (!$this->emailEnabled) {
            error_log("Email sending skipped - email system disabled");
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            $this->mailer->Subject = 'Scholarship Application Submitted - ' . $scholarshipName;
            
            // Email body with better formatting
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Application Confirmation</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$userName},</p>
                        <p>Your application for the <strong>{$scholarshipName}</strong> scholarship has been successfully submitted.</p>
                        <p><strong>Application ID:</strong> {$applicationId}</p>
                        <p>You can track your application status through your dashboard.</p>
                        <p>Best regards,<br>{$this->emailConfig['from_name']} Team</p>
                    </div>
                    <div class='footer'>
                        This is an automated message, please do not reply.
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            $result = $this->mailer->send();
            if ($result) {
                $this->logEmailSent($userEmail, 'application_confirmation', $applicationId);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function checkDuplicateApplication($userId, $scholarshipId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM applications 
                WHERE user_id = ? AND scholarship_id = ?
            ");
            $stmt->execute([$userId, $scholarshipId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Failed to check duplicate application: " . $e->getMessage());
            return false;
        }
    }

    public function notifyDuplicateApplication($userId, $userName, $userEmail, $scholarshipName) {
        // Add in-app notification
        $title = "Duplicate Application Warning";
        $message = "You have already submitted an application for the {$scholarshipName} scholarship. Multiple applications are not allowed.";
        $this->addNotification($userId, $title, $message, 'warning');

        if (!$this->emailEnabled) {
            return false;
        }

        // Send email notification
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            $this->mailer->Subject = 'Duplicate Application Warning';
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #f39c12; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Application Warning</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$userName},</p>
                        <p>We noticed that you attempted to submit another application for the <strong>{$scholarshipName}</strong> scholarship.</p>
                        <p>Please note that multiple applications for the same scholarship are not allowed.</p>
                        <p>Best regards,<br>{$this->emailConfig['from_name']} Team</p>
                    </div>
                    <div class='footer'>
                        This is an automated message, please do not reply.
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            $result = $this->mailer->send();
            if ($result) {
                $this->logEmailSent($userEmail, 'duplicate_application_warning', null);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendEmail($userEmail, $userName, $subject, $body) {
        if (!$this->emailEnabled) {
            error_log("Email sending skipped - email system disabled");
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            $result = $this->mailer->send();
            if ($result) {
                $this->logEmailSent($userEmail, 'status_notification');
            }
            return $result;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function logEmailSent($recipient, $type, $referenceId = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (
                    recipient_email,
                    email_type,
                    reference_id,
                    sent_at
                ) VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$recipient, $type, $referenceId]);
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as unread_count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['unread_count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting unread notifications count: " . $e->getMessage());
            return 0;
        }
    }

    public function sendStatusUpdateNotification($userId, $newStatus) {
        try {
            // Get user details
            $stmt = $this->pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Prepare notification content
            $title = "Account Status Update";
            $message = "Your account status has been updated to: " . ucfirst($newStatus);
            $type = 'status_update';
            
            // Add in-app notification
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$userId, $title, $message, $type]);
            
            // Send email notification
            $emailSubject = "ScholarHub - Account Status Update";
            $emailBody = "
                <html>
                <body>
                    <h2>Account Status Update</h2>
                    <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
                    <p>Your account status has been updated to: <strong>" . ucfirst($newStatus) . "</strong></p>
                    <p>If you have any questions or concerns, please contact our support team.</p>
                    <br>
                    <p>Best regards,<br>ScholarHub Team</p>
                </body>
                </html>
            ";
            
            // Send email
            $this->sendEmail($user['email'], $user['full_name'], $emailSubject, $emailBody);
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending status update notification: " . $e->getMessage());
            return false;
        }
    }
} 