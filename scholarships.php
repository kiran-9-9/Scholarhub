<?php
// Ensure consistent session handling with init.php
require_once 'includes/init.php';
require_once 'includes/Auth.php';
require_once 'config/database.php';

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

// Set userData variable for consistency with the template
$userData = $user;

// Get name information for display
$nameParts = explode(' ', $user['full_name']);
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';
$fullName = trim($firstName . ' ' . $lastName);

// Fetch scholarships from database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get filter parameters
$minAmount = isset($_GET['min_amount']) ? floatval($_GET['min_amount']) : 0;
$maxAmount = isset($_GET['max_amount']) ? floatval($_GET['max_amount']) : PHP_FLOAT_MAX;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Modify the scholarship query to include amount filters
$query = "
    SELECT s.*, 
           (SELECT COUNT(*) FROM applications a WHERE a.scholarship_id = s.id) as application_count
    FROM scholarships s
    WHERE s.status = 'active'
    AND s.amount BETWEEN ? AND ?
";

$params = [$minAmount, $maxAmount];

if (!empty($searchQuery)) {
    $query .= " AND (s.scholarship_name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get min and max amounts for the filter range
$stmt = $pdo->query("SELECT MIN(amount) as min_amount, MAX(amount) as max_amount FROM scholarships WHERE status = 'active'");
$amountRange = $stmt->fetch(PDO::FETCH_ASSOC);
$globalMinAmount = $amountRange['min_amount'] ?? 0;
$globalMaxAmount = $amountRange['max_amount'] ?? 100000;

// If no scholarships found, initialize as empty array to avoid foreach errors
if (!$scholarships) {
    $scholarships = [];
}

// Process document requirements for each scholarship
foreach ($scholarships as &$scholarship) {
    $docRequirements = [];
    if (!empty($scholarship['document_requirements'])) {
        $requirements = explode(';;', $scholarship['document_requirements']);
        foreach ($requirements as $req) {
            $parts = explode('|', $req);
            if (count($parts) >= 3) {
                list($name, $type, $required) = $parts;
                $docRequirements[] = [
                    'name' => $name,
                    'type' => $type,
                    'required' => $required == '1'
                ];
            }
        }
    }
    $scholarship['parsed_requirements'] = $docRequirements;
}
unset($scholarship); // Break the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Scholarships - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .scholarship-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            align-items: flex-start;
            margin-top: 2rem;
            width: 100%;
        }
        .scholarship-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.08);
            padding: 2rem 1.5rem;
            max-width: 350px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .scholarship-card h3 {
            color: #357abd;
            margin-bottom: 0.5rem;
        }
        .scholarship-card p {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .scholarship-card strong {
            color: #222;
        }
        .btn-apply {
            margin-top: 1rem;
            background: #357abd;
            color: #fff;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-apply:hover {
            background: #285a8c;
        }
        .wrapper {
            display: flex;
            margin-top: 90px;
        }
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
        .sidebar {
            width: 260px;
            position: fixed;
            top: 90px;
            left: 0;
            height: calc(100vh - 90px);
            z-index: 100;
            background-color: #357abd;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
            margin-top: 0;
            width: calc(100% - 260px);
            max-width: 100%;
        }
        /* Add this for guest view */
        <?php if (!$userData): ?>
        .wrapper {
            display: block;
            margin-top: 90px;
        }
        .main-content {
            margin-left: 0;
            margin-top: 0;
            width: 100%;
        }
        <?php endif; ?>
        
        /* Mobile Responsive Fixes */
        @media (max-width: 767px) {
            .wrapper {
                display: block;
                margin-top: 90px;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 100%;
                max-width: 260px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                max-width: 100%;
            }
            .scholarship-card {
                max-width: 100%;
            }
            .scholarship-cards {
                gap: 1rem;
            }
            body {
                overflow-x: hidden;
            }
        }
        
        @media (max-width: 575px) {
            .wrapper {
                margin-top: 70px;
            }
            .navbar {
                height: 70px;
                min-height: 70px;
            }
            .sidebar {
                top: 70px;
                height: calc(100vh - 70px);
            }
        }
        
        .btn, .btn:hover, .btn:focus, .menu-item, .menu-item:hover, .menu-item:focus, .nav-links a, .nav-links a:hover, .nav-links a:focus, .login-btn, .login-btn:hover, .login-btn:focus, a, a:hover, a:focus {
            text-decoration: none !important;
        }
        .scholarships h2 {
            text-align: center;
            margin-top: 0;
            padding-top: 10px;
        }

        /* Filter Section Styles */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filter-header h2 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filter-group input:focus {
            border-color: #4A90E2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn.apply {
            background: #4A90E2;
            color: white;
        }

        .filter-btn.apply:hover {
            background: #357ABD;
        }

        .filter-btn.reset {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }

        .filter-btn.reset:hover {
            background: #e9ecef;
        }

        .amount-range {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .amount-range span {
            color: #4A90E2;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <h1>ScholarHub</h1>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="contact.php">Contact Us</a>
            <a href="scholarships.php" class="active">Scholarships</a>
            <?php if ($userData): ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
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
        <a href="about.php">About</a>
        <a href="scholarships.php" class="active">Scholarships</a>
        <?php if ($userData): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="edit-profile.php">Edit Profile</a>
            <a href="applications.php">Applications</a>
            <a href="notifications.php">Notifications</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="admin/login.php">Admin</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>

    <div class="wrapper">
        <?php if ($userData): ?>
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Dashboard</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($userData['email']); ?></div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="scholarships.php" class="menu-item active">
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
        </div>
        <?php endif; ?>
        <div class="main-content">
            <div class="filter-section">
                <div class="filter-header">
                    <h2>Filter Scholarships</h2>
                </div>
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="min_amount">Minimum Amount</label>
                        <input type="number" 
                               id="min_amount" 
                               name="min_amount" 
                               min="<?php echo $globalMinAmount; ?>" 
                               max="<?php echo $globalMaxAmount; ?>" 
                               value="<?php echo $minAmount; ?>"
                               step="100">
                    </div>
                    <div class="filter-group">
                        <label for="max_amount">Maximum Amount</label>
                        <input type="number" 
                               id="max_amount" 
                               name="max_amount" 
                               min="<?php echo $globalMinAmount; ?>" 
                               max="<?php echo $globalMaxAmount; ?>" 
                               value="<?php echo $maxAmount === PHP_FLOAT_MAX ? $globalMaxAmount : $maxAmount; ?>"
                               step="100">
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               placeholder="Search scholarships...">
                    </div>
                    <div class="filter-buttons">
                        <button type="submit" class="filter-btn apply">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="scholarships.php" class="filter-btn reset">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </form>
                <div class="amount-range">
                    Available range: <span>₹<?php echo number_format($globalMinAmount); ?></span> - 
                    <span>₹<?php echo number_format($globalMaxAmount); ?></span>
                </div>
            </div>
            <section class="scholarships">
                <h2>Available Scholarships</h2>
                <div class="container">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['show_notification']) && $_SESSION['show_notification']): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showNotification(
                                    <?php echo json_encode($_SESSION['notification_message']); ?>,
                                    <?php echo json_encode($_SESSION['notification_type'] ?? 'error'); ?>,
                                    <?php echo json_encode($_SESSION['notification_title'] ?? ''); ?>
                                );
                            });
                        </script>
                        <?php 
                        unset($_SESSION['show_notification']);
                        unset($_SESSION['notification_message']);
                        unset($_SESSION['notification_type']);
                        unset($_SESSION['notification_title']);
                        ?>
                    <?php endif; ?>

                    <div class="scholarship-cards">
                        <?php foreach ($scholarships as $scholarship): ?>
                            <div class="scholarship-card">
                                <h3><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></h3>
                                <p><?php echo htmlspecialchars($scholarship['description']); ?></p>
                                <div class="scholarship-info">
                                    <div class="info-item">
                                        <strong>Amount</strong>
                                        <span>₹<?php echo number_format($scholarship['amount'], 2); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong>Deadline</strong>
                                        <span><?php echo date('F d, Y', strtotime($scholarship['deadline'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong>Status</strong>
                                        <span class="status-badge">Active</span>
                                    </div>
                                </div>
                                
                                <!-- Add a hidden div with document requirements -->
                                <div class="document-requirements" style="display: none;">
                                    <?php if (!empty($scholarship['parsed_requirements'])): ?>
                                        <h4>Required Documents:</h4>
                                        <ul>
                                            <?php foreach ($scholarship['parsed_requirements'] as $req): ?>
                                                <?php if ($req['required']): ?>
                                                    <li><?php echo htmlspecialchars($req['name']); ?> (<?php echo htmlspecialchars($req['type']); ?>)</li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($userData): ?>
                                    <a href="apply-scholarship.php?id=<?php echo $scholarship['id']; ?>" class="btn-apply" onclick="return checkRequirements(this)">Apply Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn-apply">Login to Apply</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/notifications.js"></script>
    <script>
    function checkRequirements(button) {
        // Get the requirements list from the hidden div
        const requirementsDiv = button.parentElement.querySelector('.document-requirements');
        const requirementsList = requirementsDiv.querySelector('ul');
        
        if (requirementsList) {
            // Show requirements in a confirmation dialog
            const requirements = Array.from(requirementsList.getElementsByTagName('li'))
                .map(li => li.textContent)
                .join('\n');
                
            const message = `This scholarship requires the following documents:\n\n${requirements}\n\nDo you have these documents ready to upload?`;
            
            if (confirm(message)) {
                return true; // Allow the link to proceed
            } else {
                return false; // Prevent the link from proceeding
            }
        }
        return true; // If no requirements, allow the link to proceed
    }

    // Show notification if session flag is set
    <?php if (isset($_SESSION['show_notification']) && $_SESSION['show_notification']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification(
                <?php echo json_encode($_SESSION['notification_message']); ?>,
                <?php echo json_encode($_SESSION['notification_type'] ?? 'error'); ?>,
                <?php echo json_encode($_SESSION['notification_title'] ?? ''); ?>
            );
        });
        <?php 
        unset($_SESSION['show_notification']);
        unset($_SESSION['notification_message']);
        unset($_SESSION['notification_type']);
        unset($_SESSION['notification_title']);
        ?>
    <?php endif; ?>

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links'); // This might not be needed for mobile menu toggle
    const sidebar = document.querySelector('.sidebar');
    const mobileMenu = document.querySelector('.mobile-menu'); // Get the mobile menu element

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            if (navLinks) navLinks.classList.toggle('active'); // Keep if needed for desktop responsiveness
            if (sidebar) sidebar.classList.toggle('active'); // Keep if sidebar should also be toggled
            if (mobileMenu) mobileMenu.classList.toggle('active'); // Toggle the mobile menu
        });
    }

    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
        if (hamburger && !hamburger.contains(e.target) && 
            (!mobileMenu || !mobileMenu.contains(e.target)) && // Include mobile menu
            (!navLinks || !navLinks.contains(e.target)) && // Include navLinks
            (!sidebar || !sidebar.contains(e.target))) { // Include sidebar
            
            if (hamburger) hamburger.classList.remove('active');
            if (mobileMenu) mobileMenu.classList.remove('active');
            if (navLinks) navLinks.classList.remove('active');
            if (sidebar) sidebar.classList.remove('active');
        }
    });

    // Add input validation for amount filters
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            const minAmount = parseFloat(document.getElementById('min_amount').value);
            const maxAmount = parseFloat(document.getElementById('max_amount').value);
            
            if (minAmount > maxAmount) {
                alert('Minimum amount cannot be greater than maximum amount');
                this.value = this.id === 'min_amount' ? 
                    document.getElementById('max_amount').value : 
                    document.getElementById('min_amount').value;
            }
        });
    });
    </script>
</body>
</html> 