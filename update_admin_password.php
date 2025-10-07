<?php
require_once 'config/database.php';

try {
    // Hash the new password
    $new_password = 'kiranmudur123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the admin password
    $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "Admin password has been successfully updated to: kiranmudur123";
} catch(PDOException $e) {
    echo "Error updating password: " . $e->getMessage();
}
?> 