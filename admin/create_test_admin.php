<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if test admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = ?");
    $stmt->execute(['admin']);
    $adminExists = $stmt->fetchColumn();

    if (!$adminExists) {
        // Create test admin account
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admin (username, email, password, role, status) 
            VALUES (?, ?, ?, 'super_admin', 'active')
        ");
        $stmt->execute(['admin', 'scholarhub517@gmail.com', $hashedPassword]);
        echo "Test admin account created successfully!\n";
    } else {
        // Update existing admin email
        $stmt = $pdo->prepare("
            UPDATE admin 
            SET email = 'scholarhub517@gmail.com'
            WHERE username = 'admin'
        ");
        $stmt->execute();
        echo "Test admin account email updated!\n";
    }

    echo "You can now login with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "Email: scholarhub517@gmail.com\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 