<?php
require_once 'includes/init.php';
require_once 'includes/Auth.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get test user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['testuser']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found test user:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Password hash: " . $user['password'] . "\n\n";
        
        // Test password verification
        $password = 'testpass123';
        $verified = password_verify($password, $user['password']);
        
        echo "Testing password verification:\n";
        echo "Password tried: " . $password . "\n";
        echo "Verification result: " . ($verified ? "Success" : "Failed") . "\n";
        
        if (!$verified) {
            // Update password
            echo "\nUpdating password...\n";
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$newHash, $user['id']]);
            
            if ($result) {
                echo "Password updated successfully!\n";
            } else {
                echo "Failed to update password\n";
            }
        }
    } else {
        echo "Test user not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 