<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure admin is logged in and session variables are set
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}
?>
<!-- Include admin styles -->
<link rel="stylesheet" href="css/admin-style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h2>ScholarHub Admin</h2>
        </div>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
            <div class="user-role">Administrator</div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <!-- Dashboard Section -->
        <div class="menu-section">
            <a href="dashboard.php" class="menu-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Scholarship Management Section -->
        <div class="menu-section">
            <div class="section-title">Scholarship Management</div>
            <a href="scholarships.php" class="menu-item <?php echo $current_page === 'scholarships.php' ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap"></i>
                <span>Manage Scholarships</span>
            </a>
            <a href="applications.php" class="menu-item <?php echo $current_page === 'applications.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Applications</span>
            </a>
        </div>

        <!-- User Management Section -->
        <div class="menu-section">
            <div class="section-title">User Management</div>
            <a href="users.php" class="menu-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
        </div>

        <!-- System Section -->
        <div class="menu-section">
            <div class="section-title">System</div>
            <a href="settings.php" class="menu-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>

        <!-- Logout Section -->
        <div class="menu-section mt-auto">
            <a href="logout.php" class="menu-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle">
    <i class="fas fa-bars"></i>
</button>

<style>
.sidebar {
    width: 260px;
    height: 100vh;
    background: #2c3e50;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    z-index: 1000;
    transition: all 0.3s ease;
    color: white;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header .logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-header .logo i {
    font-size: 1.5rem;
    color: #fff;
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #fff;
}

.user-info {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar i {
    color: white;
    font-size: 1.2rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: white;
    margin-bottom: 0.25rem;
}

.user-role {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
}

.menu-section {
    padding: 0.5rem 0;
}

.section-title {
    padding: 0.75rem 1.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.5);
    font-weight: 600;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    gap: 0.75rem;
}

.menu-item i {
    width: 1.25rem;
    text-align: center;
    font-size: 1rem;
}

.menu-item span {
    font-size: 0.875rem;
}

.menu-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.menu-item.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left: 4px solid #4A90E2;
}

.logout-item {
    color: #ff6b6b;
}

.logout-item:hover {
    background: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
}

.mt-auto {
    margin-top: auto;
}

.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1001;
    background: #2c3e50;
    border: none;
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .mobile-menu-toggle {
        display: block;
    }
}
</style>

<script>
    // Mobile menu functionality
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
</script>