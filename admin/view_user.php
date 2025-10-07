<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if user is admin
$auth = new Auth($conn);
if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user details
$user = $auth->getUserById($user_id);

if (!$user) {
    header('Location: users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f6f8fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem;
        }
        .user-details-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.08);
            padding: 2rem;
        }
        h1 {
            color: #357abd;
            margin-bottom: 2rem;
            text-align: center;
        }
        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .info-group {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: 600;
            color: #357abd;
            margin-bottom: 0.5rem;
        }
        .info-value {
            color: #333;
            padding: 0.5rem;
            background: #f9fbfd;
            border-radius: 4px;
        }
        .back-button {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.5rem 1rem;
            background: #357abd;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .back-button:hover {
            background: #2c5d9e;
        }
    </style>
</head>
<body>
    <div class="user-details-container">
        <h1>User Details</h1>
        <div class="user-info">
            <div class="info-group">
                <div class="info-label">User ID</div>
                <div class="info-value"><?php echo htmlspecialchars($user['id']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Username</div>
                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Status</div>
                <div class="info-value"><?php echo htmlspecialchars($user['status']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Created At</div>
                <div class="info-value"><?php echo htmlspecialchars($user['created_at']); ?></div>
            </div>
        </div>
        <a href="users.php" class="back-button">Back to Users List</a>
    </div>
</body>
</html> 