<?php
// Prevent direct access to this file
if (!defined('BASE_PATH')) {
    die('Direct access to this file is not allowed');
}
?>
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-graduation-cap"></i>
        <h1>ScholarHub</h1>
    </div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="contact.php">Contact Us</a>
        <a href="scholarships.php">Scholarships</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav> 