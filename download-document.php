<?php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'config/database.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user data
$user = $auth->getUserData();
$userId = $user['id'];

// Validate request parameters
if (!isset($_GET['path']) || !isset($_GET['name'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid request");
}

$filePath = $_GET['path'];
$fileName = $_GET['name'];

// Security checks
if (strpos($filePath, '..') !== false || strpos($fileName, '..') !== false) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Verify that the user has access to this document
$stmt = $pdo->prepare("
    SELECT ad.* 
    FROM application_documents ad
    JOIN applications a ON ad.application_id = a.id
    WHERE a.user_id = ? 
    AND ad.file_path = ?
");
$stmt->execute([$userId, $filePath]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Full path to the file
$fullPath = "uploads/applications/" . $document['application_id'] . "/" . $filePath;

// Check if file exists
if (!file_exists($fullPath)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

// Get file information
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Set headers for download
header("Content-Type: " . $mimeType);
header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
header("Content-Length: " . filesize($fullPath));
header("Cache-Control: private");

// Output file content
readfile($fullPath);
exit(); 