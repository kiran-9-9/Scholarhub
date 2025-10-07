<?php
// Get database connection
require_once 'includes/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get real statistics from database
// 1. Count registered students
$registeredStudentsStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$registeredStudentsCount = $registeredStudentsStmt->fetchColumn();

// 2. Count scholarships
$scholarshipsStmt = $pdo->query("SELECT COUNT(*) FROM scholarships");
$scholarshipsCount = $scholarshipsStmt->fetchColumn();

// 3. Calculate total scholarship amount
$totalAmountStmt = $pdo->query("SELECT SUM(amount) FROM scholarships WHERE status = 'active'");
$totalAmount = $totalAmountStmt->fetchColumn();
$totalAmountCrores = number_format($totalAmount / 10000000, 2); // Convert to crores

// 4. Calculate success rate (approved applications / total applications)
$totalAppsStmt = $pdo->query("SELECT COUNT(*) FROM applications");
$totalApps = $totalAppsStmt->fetchColumn();

$approvedAppsStmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'");
$approvedApps = $approvedAppsStmt->fetchColumn();

// Calculate success rate, default to 0 if no applications
$successRate = ($totalApps > 0) ? round(($approvedApps / $totalApps) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* About Page Specific Styles */
        .about-hero {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%);
            padding: 6rem 2rem 4rem;
            text-align: center;
        }

        .about-hero h1 {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }

        .about-hero p {
            font-size: 1.2rem;
            color: var(--text-color);
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.2s;
        }

        .mission-vision {
            padding: 4rem 2rem;
            background: white;
        }

        .mission-vision-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .mission-card, .vision-card {
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .mission-card:hover, .vision-card:hover {
            transform: translateY(-5px);
        }

        .mission-card h2, .vision-card h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mission-card i, .vision-card i {
            font-size: 1.5rem;
        }

        .team-section {
            padding: 4rem 2rem;
            background: var(--light-bg);
        }

        .team-section h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 3rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .team-member {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .team-member h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .team-member p {
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-links a {
            color: var(--primary-color);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        .achievements {
            padding: 4rem 2rem;
            background: white;
        }

        .achievements h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 3rem;
        }

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .achievement-card {
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            background: var(--light-bg);
            transition: transform 0.3s ease;
        }

        .achievement-card:hover {
            transform: translateY(-5px);
        }

        .achievement-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .achievement-card h3 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .achievement-card p {
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2rem;
            }

            .about-hero p {
                font-size: 1rem;
            }

            .mission-vision-grid,
            .team-grid,
            .achievements-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="about.php" class="active">About</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="admin/login.php">Admin</a>
            <a href="login.php" class="login-btn">Login</a>
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
        <a href="about.php" class="active">About</a>
        <a href="scholarships.php">Scholarships</a>
        <a href="admin/login.php">Admin</a>
        <a href="login.php">Login</a>
    </div>

    <!-- About Hero Section -->
    <section class="about-hero">
        <h1>About ScholarHub</h1>
        <p>Empowering students to achieve their academic dreams through accessible scholarship opportunities and comprehensive support.</p>
    </section>

    <!-- Mission and Vision Section -->
    <section class="mission-vision">
        <div class="mission-vision-grid">
            <div class="mission-card" data-aos="fade-right">
                <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
                <p>To democratize access to education by connecting students with scholarship opportunities that match their unique profiles and aspirations. We strive to break down financial barriers and create equal opportunities for all students to pursue their academic dreams.</p>
            </div>
            <div class="vision-card" data-aos="fade-left">
                <h2><i class="fas fa-eye"></i> Our Vision</h2>
                <p>To become the leading platform for scholarship discovery and application, transforming the way students access educational funding. We envision a world where every student has the opportunity to pursue higher education without financial constraints.</p>
            </div>
        </div>
    </section>

    <!-- Achievements Section -->
    <section class="achievements">
        <h2>Our Achievements</h2>
        <div class="achievements-grid">
            <div class="achievement-card" data-aos="fade-up">
                <i class="fas fa-users"></i>
                <h3><?php echo number_format($registeredStudentsCount); ?>+</h3>
                <p>Registered Students</p>
            </div>
            <div class="achievement-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo number_format($scholarshipsCount); ?>+</h3>
                <p>Scholarships Listed</p>
            </div>
            <div class="achievement-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-rupee-sign"></i>
                <h3>₹<?php echo $totalAmountCrores; ?>Cr+</h3>
                <p>Scholarship Amount</p>
            </div>
            <div class="achievement-card" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $successRate; ?>%</h3>
                <p>Success Rate</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>ScholarHub</h3>
                <p>Empowering education through accessible scholarships.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact</a>
                <a href="privacy.php">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email:scholarhub517@gmail.com</p>
                <p>Phone: +91 9353797345</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ScholarHub. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/main.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html> 