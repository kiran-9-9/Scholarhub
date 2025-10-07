<?php
require_once 'includes/Auth.php';
require_once 'config/database.php';
$auth = new Auth();

// Fetch scholarships from the database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scholarship_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM scholarships WHERE status = 'active' ORDER BY deadline ASC");
    $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $scholarships = [];
}

// Get real statistics from database
// 1. Count active scholarships
$activeScholarshipsStmt = $pdo->query("SELECT COUNT(*) FROM scholarships WHERE status = 'active'");
$activeScholarshipsCount = $activeScholarshipsStmt->fetchColumn();

// 2. Count registered students
$registeredStudentsStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$registeredStudentsCount = $registeredStudentsStmt->fetchColumn();

// 3. Calculate total scholarship amount
$totalAmountStmt = $pdo->query("SELECT SUM(amount) FROM scholarships WHERE status = 'active'");
$totalAmount = $totalAmountStmt->fetchColumn();
$totalAmountCrores = number_format($totalAmount / 10000000, 2); // Convert to crores
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarHub - Your Gateway to Educational Opportunities</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Hero Section Styles */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: 
                linear-gradient(135deg, rgba(74, 144, 226, 0.25) 0%, rgba(74, 144, 226, 0.10) 100%),
                url('img.jpg') center center/cover no-repeat;
            padding: 2rem;
            position: relative;
        }

        .hero-content {
            background: rgba(255,255,255,0.85);
            border-radius: 18px;
            padding: 2rem 2.5rem;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.12);
            max-width: 800px;
            width: 100%;
            text-align: center;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .hero-btn {
            padding: 1rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background: var(--primary-color);
            color: white;
        }

        .secondary-btn {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-btn {
                width: 100%;
                text-align: center;
            }
            .hero-content {
                padding: 1.2rem 0.5rem;
            }
        }

        .hero-image {
            width: 100%;
            max-width: 420px;
            max-height: 320px;
            display: block;
            margin: 0 auto 2rem auto;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.12);
            object-fit: cover;
        }

        @media (max-width: 600px) {
            .hero-image {
                max-width: 95vw;
                max-height: 180px;
                margin-bottom: 1rem;
            }
            body {
                overflow-x: hidden;
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
            <a href="about.php">About</a>
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
        <a href="index.php" class="active">Home</a>
        <a href="about.php">About</a>
        <a href="scholarships.php">Scholarships</a>
        <a href="admin/login.php">Admin</a>
        <a href="login.php">Login</a>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to ScholarHub</h1>
            <p>Your gateway to educational opportunities.</p>
            <div class="hero-buttons">
                <a href="register.php" class="hero-btn primary-btn">Get Started</a>
                <a href="scholarships.php" class="hero-btn secondary-btn">Explore Scholarships</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" data-aos="fade-up">
        <h2>Why Choose ScholarHub?</h2>
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-right">
                <i class="fas fa-search"></i>
                <h3>Easy Search</h3>
                <p>Find scholarships that match your profile with our smart search system.</p>
            </div>
            <div class="feature-card" data-aos="fade-up">
                <i class="fas fa-file-alt"></i>
                <h3>Simple Application</h3>
                <p>Streamlined application process with real-time status updates.</p>
            </div>
            <div class="feature-card" data-aos="fade-left">
                <i class="fas fa-bell"></i>
                <h3>Instant Updates</h3>
                <p>Get notified about application status and new opportunities.</p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="statistics" data-aos="fade-up">
        <div class="stat-container">
            <div class="stat-item">
                <h3><?php echo number_format($activeScholarshipsCount); ?>+</h3>
                <p>Active Scholarships</p>
            </div>
            <div class="stat-item">
                <h3><?php echo number_format($registeredStudentsCount); ?>+</h3>
                <p>Registered Students</p>
            </div>
            <div class="stat-item">
                <h3>₹<?php echo $totalAmountCrores; ?>Cr+</h3>
                <p>Scholarship Amount</p>
            </div>
        </div>
    </section>

    <!-- Scholarships Section -->
     

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
                <p>Email: scholarhub517@gmail.com</p>
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

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 