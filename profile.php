<?php
// Ensure consistent session handling with init.php
require_once 'includes/init.php';
require_once 'includes/Auth.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Extract name information from full_name field
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);

// If full_name is empty, use username instead
if (empty($fullName) && isset($user['username'])) {
    $fullName = $user['username'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // After successful profile update
    $result = $auth->updateProfile($user['id'], $_POST);
    if ($result['success']) {
        // Log the profile update activity
        $auth->logActivity(
            $user['id'],
            'profile',
            'User updated their profile information'
        );
        
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 90px;
            min-height: 90px;
            display: flex;
            align-items: center;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .navbar .logo i {
            font-size: 24px;
            color: #4A90E2;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #4A90E2;
        }

        .nav-links .login-btn {
            background: #4A90E2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .nav-links .login-btn:hover {
            background: #357abd;
        }

        /* Container Layout */
        .dashboard-container {
            display: flex;
            margin-top: 90px;
            min-height: calc(100vh - 90px);
        }

        .sidebar {
            position: fixed;
            top: 90px;
            left: 0;
            width: 250px;
            height: calc(100vh - 90px);
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background: #f8f9fa;
            min-height: calc(100vh - 90px);
        }
        .main-content h1:first-child {
            margin-top: 0;
            padding-top: 10px;
        }

        @media (max-width: 768px) {
            .quick-links {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Profile Specific Styles */
        .profile-header {
            margin-bottom: 0;
        }

        .profile-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #666;
            font-size: 16px;
        }

        .profile-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.10);
            padding: 40px 32px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            background: linear-gradient(135deg, #4A90E2 60%, #6dd5ed 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.10);
        }

        .profile-avatar i {
            font-size: 48px;
            color: white;
        }

        .profile-details {
            text-align: center;
        }

        .profile-name {
            font-size: 26px;
            color: #222;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .profile-meta, .profile-email {
            color: #666;
            font-size: 15px;
            margin-bottom: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px 32px;
            margin-bottom: 28px;
        }

        .info-item {
            background: #f4f8fb;
            border-radius: 8px;
            padding: 18px 16px;
            border: none;
            box-shadow: 0 1px 3px rgba(44, 62, 80, 0.04);
        }

        .info-label {
            color: #888;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .info-value {
            color: #222;
            font-size: 16px;
            font-weight: 500;
        }

        .status-active {
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .profile-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 18px;
        }

        .btn, .btn:hover, .btn:focus, .menu-item, .menu-item:hover, .menu-item:focus, .nav-links a, .nav-links a:hover, .nav-links a:focus, .login-btn, .login-btn:hover, .login-btn:focus, a, a:hover, a:focus {
            text-decoration: none !important;
        }

        .btn {
            padding: 10px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.06);
            transition: background 0.2s, color 0.2s;
        }

        .btn-edit {
            background: #4A90E2;
            color: white;
        }

        .btn-edit:hover {
            background: #357abd;
        }

        .btn-change-password {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-change-password:hover {
            background: #e3e8ee;
        }

        @media (max-width: 900px) {
            .profile-section {
                padding: 24px 8px;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <h1>ScholarHub</h1>
        </a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="contact.php">Contact Us</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php" class="active">Profile</a>
        <a href="scholarships.php">Scholarships</a>
        <a href="applications.php">Applications</a>
        <a href="notifications.php">Notifications</a>
        <a href="contact.php">Contact Us</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Dashboard</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profile.php" class="menu-item active">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="scholarships.php" class="menu-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Scholarships</span>
                </a>
                <a href="applications.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications</span>
                </a>
                <a href="notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>View and manage your personal information</p>
            </div>

            <div class="profile-section">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-details">
                        <h2 class="profile-name"><?php echo htmlspecialchars($fullName); ?></h2>
                        <div class="profile-meta">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">First Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($firstName); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Last Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($lastName); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not provided'; ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Not provided'; ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <span class="status-active">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="edit-profile.php" class="btn btn-edit">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="change-password.php" class="btn btn-change-password">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        const sidebar = document.querySelector('.sidebar');
        const mobileMenu = document.querySelector('.mobile-menu');

        if (hamburger) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                if (navLinks) navLinks.classList.toggle('active');
                if (sidebar) sidebar.classList.toggle('active');
                if (mobileMenu) mobileMenu.classList.toggle('active');
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (hamburger && !hamburger.contains(e.target) && 
                (!mobileMenu || !mobileMenu.contains(e.target)) &&
                (!navLinks || !navLinks.contains(e.target)) &&
                (!sidebar || !sidebar.contains(e.target))) {
                
                if (hamburger) hamburger.classList.remove('active');
                if (mobileMenu) mobileMenu.classList.remove('active');
                if (navLinks) navLinks.classList.remove('active');
                if (sidebar) sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html> 