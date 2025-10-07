<?php
class Database {
    private $pdo;
    private static $instance = null;

    private function __construct() {
        try {
            // Load database configuration
            $config = require dirname(__DIR__) . '/config/database.php';
            
            // First connect without database to create it if needed
            $this->pdo = new PDO(
                "mysql:host={$config['host']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            // Create database if it doesn't exist
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Connect to the specific database
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            if (!$this->pdo) {
                error_log("Database connection failed in Database constructor");
                throw new Exception("Database connection failed");
            }

            // Initialize database tables
            $this->initializeTables();

        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            $this->pdo = null;
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeTables() {
        // Create settings table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) UNIQUE NOT NULL,
            `value` TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (`key`)
        )");

        // Create password_resets table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            used TINYINT(1) DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Insert default settings if they don't exist
        $defaultSettings = [
            ['site_name', 'ScholarHub'],
            ['site_description', 'Empowering students through scholarships'],
            ['contact_email', 'scholarhub517@gmail.com'],
            ['contact_phone', '9353797345'],
            ['footer_text', '© 2025 ScholarHub. All rights reserved.'],
            ['maintenance_mode', '0'],
            ['allow_registration', '1'],
            ['max_file_size', '5242880'], // 5MB in bytes
            ['allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png']
        ];

        foreach ($defaultSettings as $setting) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
            $stmt->execute([$setting[0]]);
            if ($stmt->fetchColumn() == 0) {
                $insertStmt = $this->pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
                $insertStmt->execute($setting);
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if (!$this->pdo) {
            error_log("PDO connection is null in getConnection()");
            throw new Exception("Database connection is not available");
        }
        return $this->pdo;
    }
}
?> 