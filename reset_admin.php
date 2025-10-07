<?php
require_once 'includes/init.php';
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "Admin password has been reset successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 