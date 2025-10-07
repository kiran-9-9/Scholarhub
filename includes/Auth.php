<?php
$project_root = dirname(__DIR__);
require_once __DIR__ . '/Database.php';
require_once $project_root . '/config/config.php';
require_once $project_root . '/lib/Mailer.php';

// NO PHPMailer or vendor/autoload.php requirements

class Auth {
    private $pdo;

    public function __construct() {
        try {
            // Get the existing database connection from the Database singleton
            $db = Database::getInstance();
            if (!$db) {
                error_log("Failed to get Database instance in Auth constructor");
                throw new Exception("Database initialization failed");
            }

            $this->pdo = $db->getConnection();
            if (!$this->pdo) {
                error_log("Database connection is null in Auth constructor");
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("Auth constructor error: " . $e->getMessage());
            throw new Exception("Failed to initialize Auth: " . $e->getMessage());
        }
    }

    public function loadUserData($userId) {
        if ($this->pdo === null) {
            error_log("loadUserData called but PDO connection is null");
            return null;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error loading user data: " . $e->getMessage());
            return null;
        }
    }

    private function sendEmail($to, $subject, $message, $headers = '') {
        // Use PHPMailer directly with Gmail SMTP
        require_once __DIR__ . '/../vendor/autoload.php';
        $emailConfig = require __DIR__ . '/../config/email.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $emailConfig['smtp_host'];
            $mail->SMTPAuth = $emailConfig['smtp_auth'];
            $mail->Username = $emailConfig['smtp_username'];
            $mail->Password = $emailConfig['smtp_password'];
            $mail->SMTPSecure = $emailConfig['smtp_secure'];
            $mail->Port = $emailConfig['smtp_port'];
            $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            $mail->CharSet = 'UTF-8';
            $mail->send();
            error_log("PHPMailer: Email sent to $to");
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer: Failed to send email to $to. Error: " . $e->getMessage());
            return false;
        }
    }

    public function register($username, $email, $password, $full_name, $phone = null, $address = null) {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                return ['success' => false, 'message' => 'All required fields must be filled'];
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Check if username already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Begin transaction
            $this->pdo->beginTransaction();

            // Insert new user with email_verified set to 1
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, full_name, phone, address, email_verified, email_verified_at) 
                VALUES (:username, :email, :password, :full_name, :phone, :address, 1, NOW())
            ");
            
            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':address' => $address
            ]);

            if ($result) {
                $this->pdo->commit();
                return ['success' => true, 'message' => 'Registration successful. You can now login.'];
            } else {
                $this->pdo->rollBack();
                error_log("Registration failed: " . implode(", ", $stmt->errorInfo()));
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: Database error'];
        }
    }

    public function verifyEmail($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ev.*, u.email 
                FROM email_verifications ev 
                JOIN users u ON ev.user_id = u.id 
                WHERE ev.token = ? AND ev.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$verification) {
                return ['success' => false, 'message' => 'Invalid or expired verification token.'];
            }

            $this->pdo->beginTransaction();

            // Update user's email verification status
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_verified = 1, email_verified_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$verification['user_id']]);

            // Delete used verification token
            $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE token = ?");
            $stmt->execute([$token]);

            $this->pdo->commit();

            return [
                'success' => true, 
                'message' => 'Email verified successfully.',
                'user_id' => $verification['user_id']
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while verifying your email.'];
        }
    }

    public function resendVerificationEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE email = ? AND email_verified = 0");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'No unverified account found with this email.'];
            }

            // Generate new verification token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Store new verification token
            $stmt = $this->pdo->prepare("
                INSERT INTO email_verifications (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);

            // Send verification email
            $verificationLink = APP_URL . "/verify-email.php?token=" . $token;
            $subject = APP_NAME . " - Email Verification";
            $message = "Hello " . $user['username'] . ",\n\n";
            $message .= "Please click the link below to verify your email address:\n\n";
            $message .= $verificationLink . "\n\n";
            $message .= "This link will expire in 24 hours.\n\n";
            $message .= "Best regards,\n" . APP_NAME . " Team";

            $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
            $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if ($this->sendEmail($email, $subject, $message, $headers)) {
                return ['success' => true, 'message' => 'Verification email has been sent.'];
            } else {
                throw new Exception("Failed to send verification email");
            }
        } catch (Exception $e) {
            error_log("Resend verification email error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while sending the verification email.'];
        }
    }

    public function login($username, $password) {
        try {
            // First try admin login
            $stmt = $this->pdo->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = 'admin';
                $_SESSION['just_logged_in'] = true;
                
                // Add admin session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_last_activity'] = time();
                
                return ['success' => true, 'message' => 'Admin login successful'];
            }

            // Then try user login
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = 'user';
                $_SESSION['just_logged_in'] = true;
                return ['success' => true, 'message' => 'Login successful'];
            }

            return ['success' => false, 'message' => 'Invalid username or password'];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed: Database error'];
        }
    }

        public function isLoggedIn() {
        // Enhanced version of isLoggedIn
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // For better performance, we don't verify in database - just trust the session
        return true;
    }

    public function isAdmin() {
        // Check if admin session variables are set
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
            !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
            !isset($_SESSION['admin_id'])) {
            return false;
        }

        // Verify against database
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM admin WHERE id = ? AND username = ?");
            $stmt->execute([$_SESSION['admin_id'], $_SESSION['admin_username']]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Admin verification error: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function getUserData($userId = null) {
        try {
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            
            if (!$userId) {
                return null;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Split full name into parts
                $nameParts = explode(' ', $user['full_name']);
                $user['first_name'] = $nameParts[0];
                $user['last_name'] = isset($nameParts[1]) ? $nameParts[1] : '';
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log("Error fetching user data: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile($userId, $data) {
        try {
            $update_fields = [];
            $params = [];

            // Add fields to update
            if (isset($data['full_name'])) {
                $update_fields[] = "full_name = ?";
                $params[] = $data['full_name'];
            }
            
            if (isset($data['email'])) {
                $update_fields[] = "email = ?";
                $params[] = $data['email'];
            }

            if (isset($data['phone'])) {
                $update_fields[] = "phone = ?";
                $params[] = $data['phone'];
            }

            if (isset($data['address'])) {
                $update_fields[] = "address = ?";
                $params[] = $data['address'];
            }

            if (isset($data['password'])) {
                $update_fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($update_fields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute($params)) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating your profile'];
        }
    }

    public function isEmailExists($email, $excludeUserId = null) {
        try {
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$email];
            
            if ($excludeUserId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail($email) {
        try {
            // Check if the email exists
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // For security reasons, don't tell the user the email doesn't exist
                error_log("Password reset requested for non-existent email: $email");
                return ['success' => true, 'message' => 'If an account exists with this email, you will receive password reset instructions.'];
            }
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // First, invalidate any existing tokens for this user
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Create new reset token
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // Prepare email
            $resetLink = APP_URL . "/reset-password.php?token=" . $token;
            $subject = APP_NAME . " - Password Reset Request";
            $message = "Hello " . $user['username'] . ",\n\n";
            $message .= "We received a request to reset your password. If you did not make this request, please ignore this email.\n\n";
            $message .= "To reset your password, please click the link below:\n\n";
            $message .= $resetLink . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "Best regards,\n" . APP_NAME . " Team";
            
            // Send email and commit transaction if successful
            if ($this->sendEmail($email, $subject, $message)) {
                $this->pdo->commit();
                error_log("Password reset email sent successfully to: $email");
                return ['success' => true, 'message' => 'If an account exists with this email, you will receive password reset instructions.'];
            } else {
                $this->pdo->rollBack();
                throw new Exception("Failed to send password reset email");
            }
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Password reset email error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while processing your request.'];
        }
    }
    
    public function validateResetToken($token) {
        try {
            // Check if token is empty
            if (empty($token)) {
                error_log("Empty reset token provided");
                return null;
            }
            
            // Get current time for comparison
            $currentTime = date('Y-m-d H:i:s');
            
            // Query for token
            $stmt = $this->pdo->prepare("
                SELECT pr.*, u.id as user_id, u.email, u.username
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > ?
            ");
            $stmt->execute([$token, $currentTime]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset) {
                error_log("Invalid or expired reset token: " . substr($token, 0, 10) . "...");
                return null;
            }
            
            return $reset;
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return null;
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            $reset = $this->validateResetToken($token);
            
            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid or expired token.'];
            }
            
            // Validate new password
            $passwordValidation = $this->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return ['success' => false, 'message' => $passwordValidation['message']];
            }
            
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $reset['user_id']]);
            
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Your password has been updated successfully.'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while resetting your password.'];
        }
    }

    private function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['valid' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one special character'];
        }

        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one number'];
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
        }

        return ['valid' => true, 'message' => 'Password meets all requirements'];
    }

    public function getUsersList($limit = 10, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT a.id) as total_applications,
                       COUNT(DISTINCT CASE WHEN a.status = 'approved' THEN a.id END) as approved_applications
                FROM users u
                LEFT JOIN applications a ON u.id = a.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users list: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalUsers() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting total users: " . $e->getMessage());
            return 0;
        }
    }

    public function updateUserStatus($userId, $status) {
        try {
            if (!in_array($status, ['active', 'inactive', 'suspended'])) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $userId])) {
                return ['success' => true, 'message' => 'User status updated successfully'];
            }
            return ['success' => false, 'message' => 'Failed to update user status'];
        } catch (PDOException $e) {
            error_log("Error updating user status: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating user status'];
        }
    }

    public function getUserDetails($userId) {
        try {
            // Get user basic info
            $stmt = $this->pdo->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT a.id) as total_applications,
                       COUNT(DISTINCT CASE WHEN a.status = 'approved' THEN a.id END) as approved_applications,
                       COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending_applications,
                       COUNT(DISTINCT CASE WHEN a.status = 'rejected' THEN a.id END) as rejected_applications
                FROM users u
                LEFT JOIN applications a ON u.id = a.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            // Get recent activity
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_activity_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $user['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent applications
            $stmt = $this->pdo->prepare("
                SELECT a.*, s.scholarship_name 
                FROM applications a
                JOIN scholarships s ON a.scholarship_id = s.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $user['recent_applications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $user;
        } catch (PDOException $e) {
            error_log("Error fetching user details: " . $e->getMessage());
            return null;
        }
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteUser($user_id) {
        try {
            // First delete the user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$user_id]);
            
            if ($result) {
                error_log("User $user_id deleted successfully");
                return true;
            } else {
                error_log("Failed to delete user $user_id");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error deleting user $user_id: " . $e->getMessage());
            return false;
        }
    }

    public function logActivity($userId, $activityType, $description) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            return $stmt->execute([$userId, $activityType, $description]);
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadNotificationCount($userId) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM notifications
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching unread notification count: " . $e->getMessage());
            return 0;
        }
    }
}
?> 