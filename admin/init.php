<?php
// This file should be included at the beginning of all admin pages
// It handles session checks, database connections, and common functions

// Include session check to ensure administrator is logged in
require_once __DIR__ . '/check_session.php';

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Common admin functions can be defined here

// Function to get admin data by ID
function getAdminData($pdo, $adminId) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$adminId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to log admin activity
function logAdminActivity($pdo, $adminId, $action, $details = '') {
    $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$adminId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

// Other helper functions as needed
?> 