<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // After successful application submission
    $result = $auth->submitApplication($userId, $scholarshipId, $data);
    if ($result['success']) {
        // Log the application activity
        $auth->logActivity(
            $userId,
            'application',
            'User submitted an application for ' . $scholarship['scholarship_name']
        );
        
        $_SESSION['success'] = "Application submitted successfully!";
        header("Location: applications.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
} 