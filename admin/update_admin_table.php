<?php
require_once __DIR__ . '/../includes/Database.php';

function log_message($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message");
    echo "$message\n";
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM admin LIKE 'reset_token'");
    $resetTokenExists = $stmt->fetch();

    $stmt = $pdo->query("SHOW COLUMNS FROM admin LIKE 'reset_token_expiry'");
    $resetTokenExpiryExists = $stmt->fetch();

    // Add columns if they don't exist
    if (!$resetTokenExists) {
        $pdo->exec("ALTER TABLE admin ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
        log_message("Added reset_token column to admin table");
    }

    if (!$resetTokenExpiryExists) {
        $pdo->exec("ALTER TABLE admin ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL");
        log_message("Added reset_token_expiry column to admin table");
    }

    // Add email column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM admin LIKE 'email'");
    $emailExists = $stmt->fetch();

    if (!$emailExists) {
        $pdo->exec("ALTER TABLE admin ADD COLUMN email VARCHAR(255) DEFAULT NULL");
        log_message("Added email column to admin table");
    }

    log_message("Admin table updated successfully!");

} catch (Exception $e) {
    log_message("Error updating admin table: " . $e->getMessage());
    exit(1);
} 