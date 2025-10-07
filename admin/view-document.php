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

// Debug information
error_log("Attempting to access file: " . $filePath);
error_log("File exists check: " . (file_exists($filePath) ? 'Yes' : 'No'));
error_log("Is readable: " . (is_readable($filePath) ? 'Yes' : 'No'));

// Check if file exists and is accessible
if (!file_exists($filePath)) {
    error_log("File not found at path: " . $filePath);
    die("File not found. Please check if the file exists in the uploads directory.");
}

if (!is_readable($filePath)) {
    error_log("File not readable at path: " . $filePath);
    die("File is not readable. Please check file permissions.");
}

// Get file mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Set appropriate headers
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');

// Output file content
readfile($filePath);
exit(); 