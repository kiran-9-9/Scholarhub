<?php
require_once 'includes/init.php';
require_once 'includes/Settings.php';

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize settings
$settings = new Settings($pdo);

// Update the contact email
$settings->set('contact_email', 'scholarhub517@gmail.com');

echo "Contact email updated successfully!";
?> 