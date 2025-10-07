<?php
session_start();
require_once 'includes/Auth.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get application ID from request
$applicationId = isset($_GET['id']) ? $_GET['id'] : null;
if (!$applicationId) {
    die("Application ID is required");
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Set headers for file download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="ScholarHub_Application_' . $applicationId . '.txt"');

// Generate the content
$content = "ScholarHub Application Details\n";
$content .= "================================\n\n";

// Application Information
$content .= "APPLICATION INFORMATION\n";
$content .= "----------------------\n";
$content .= "Application ID: " . $applicationId . "\n";
$content .= "Submission Date: " . date('F d, Y') . "\n";
$content .= "Status: Pending Review\n\n";

// Scholarship Information
$content .= "SCHOLARSHIP INFORMATION\n";
$content .= "----------------------\n";
$content .= "Scholarship Name: Merit Scholarship 2024\n";
$content .= "Amount: ₹50,000\n";
$content .= "Applied Date: January 15, 2024\n\n";

// Applicant Information
$content .= "APPLICANT INFORMATION\n";
$content .= "--------------------\n";
$content .= "Full Name: " . $user['full_name'] . "\n";
$content .= "Email: " . $user['email'] . "\n";
$content .= "Phone: " . ($user['phone'] ?? 'Not provided') . "\n";
$content .= "Address: " . ($user['address'] ?? 'Not provided') . "\n\n";

// Submitted Documents
$content .= "SUBMITTED DOCUMENTS\n";
$content .= "------------------\n";
$content .= "• Academic Transcripts\n";
$content .= "• Income Certificate\n";
$content .= "• Recommendation Letter\n\n";

// Application Timeline
$content .= "APPLICATION TIMELINE\n";
$content .= "------------------\n";
$content .= "Jan 15, 2024 - 10:30 AM: Application Submitted\n";
$content .= "Jan 16, 2024 - 2:15 PM: Document Verification\n";
$content .= "Current Status: Under Review\n\n";

// Terms and Conditions
$content .= "TERMS AND CONDITIONS\n";
$content .= "------------------\n";
$content .= "By submitting this application, I declare that all information provided is true and\n";
$content .= "accurate to the best of my knowledge. I understand that any false statements may\n";
$content .= "result in the rejection of my application or withdrawal of the scholarship if\n";
$content .= "already awarded.\n\n";

$content .= "Signature: _____________________\n";
$content .= "Date: " . date('F d, Y') . "\n";

// Output the content
echo $content;
?> 