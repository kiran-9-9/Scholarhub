<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Create auth instance
$auth = new Auth();

// Check admin authentication
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get the file name from the query string
$fileName = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($fileName)) {
    die("No file specified");
}

// Sanitize the file name
$fileName = basename($fileName);

// Construct the full file path
$filePath = '../uploads/applications/' . $fileName;

// Check if file exists and is accessible
if (!file_exists($filePath)) {
    die("File not found");
}

// Get file mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Set appropriate headers for download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($filePath);
exit(); 