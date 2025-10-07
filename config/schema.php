<?php
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Create users table first since other tables depend on it
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        profile_picture VARCHAR(255),
        email_verified TINYINT(1) DEFAULT 0,
        email_verified_at TIMESTAMP NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        login_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create password_resets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )");
    
    // Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'application', 'system', 'scholarship') NOT NULL DEFAULT 'info',
        related_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_read (user_id, is_read),
        INDEX idx_created_at (created_at),
        INDEX idx_related (related_id)
    )");
    
    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->execute([':username' => 'admin']);
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, email_verified) 
            VALUES (:username, :email, :password, :full_name, :email_verified)
        ");
        $stmt->execute([
            ':username' => 'admin',
            ':email' => 'admin@scholarship.com',
            ':password' => $hashed_password,
            ':full_name' => 'Administrator',
            ':email_verified' => 1
        ]);
        echo "Default admin user created.\n";
    }
    
    // Insert test user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->execute([':username' => 'testuser']);
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, email_verified) 
            VALUES (:username, :email, :password, :full_name, :email_verified)
        ");
        $stmt->execute([
            ':username' => 'testuser',
            ':email' => 'test@example.com',
            ':password' => $hashed_password,
            ':full_name' => 'Test User',
            ':email_verified' => 1
        ]);
        echo "Test user created.\n";
    }
    
    echo "Database schema initialized successfully.\n";
} catch (Exception $e) {
    echo "Error initializing database schema: " . $e->getMessage() . "\n";
    exit(1);
} 