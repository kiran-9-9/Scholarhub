<?php
class Security {
    private static $instance = null;
    private $logger;

    private function __construct() {
        $this->logger = Logger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        return true;
    }

    public function checkLoginAttempts($username) {
        $attempts = $_SESSION['login_attempts'][$username] ?? 0;
        $lastAttempt = $_SESSION['last_attempt'][$username] ?? 0;

        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            if (time() - $lastAttempt < LOGIN_LOCKOUT_TIME) {
                $this->logger->logSecurityEvent('Account locked', [
                    'username' => $username,
                    'attempts' => $attempts
                ]);
                return false;
            }
            // Reset attempts after lockout period
            $_SESSION['login_attempts'][$username] = 0;
        }
        return true;
    }

    public function incrementLoginAttempts($username) {
        $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
        $_SESSION['last_attempt'][$username] = time();
    }

    public function resetLoginAttempts($username) {
        unset($_SESSION['login_attempts'][$username]);
        unset($_SESSION['last_attempt'][$username]);
    }

    public function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ALLOWED_FILE_TYPES;
        }
        if ($maxSize === null) {
            $maxSize = MAX_FILE_SIZE;
        }

        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($file['size'] > $maxSize) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedTypes)) {
            return false;
        }

        return true;
    }

    public function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validatePhone($phone) {
        return preg_match('/^\+?[1-9]\d{1,14}$/', $phone);
    }

    public function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Store old session data
            $old_session = $_SESSION;
            
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Restore session data
            $_SESSION = $old_session;
            
            // Set cookie parameters to ensure consistent behavior
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'domain' => $cookieParams['domain'],
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
    }
} 