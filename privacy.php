<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Privacy Policy Specific Styles */
        .privacy-hero {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%);
            padding: 6rem 2rem 4rem;
            text-align: center;
        }

        .privacy-hero h1 {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }

        .privacy-hero p {
            font-size: 1.2rem;
            color: var(--text-color);
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.2s;
        }

        .privacy-container {
            max-width: 1000px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .privacy-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease;
        }

        .privacy-section h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .privacy-section h2 i {
            font-size: 1.5rem;
        }

        .privacy-section p {
            color: var(--text-color);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .privacy-section ul {
            list-style-type: none;
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .privacy-section ul li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            line-height: 1.6;
        }

        .privacy-section ul li::before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: 0;
            font-size: 1.2rem;
        }

        .privacy-section h3 {
            color: var(--primary-color);
            margin: 1.5rem 0 1rem;
            font-size: 1.2rem;
        }

        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .back-to-top.visible {
            opacity: 1;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            background: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .privacy-hero h1 {
                font-size: 2rem;
            }

            .privacy-hero p {
                font-size: 1rem;
            }

            .privacy-container {
                padding: 0 1rem;
            }

            .privacy-section {
                padding: 1.5rem;
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

    <!-- Privacy Policy Hero Section -->
    <section class="privacy-hero">
        <h1>Privacy Policy</h1>
        <p>Your privacy is important to us. This policy outlines how we collect, use, and protect your personal information.</p>
    </section>

    <!-- Privacy Policy Content -->
    <div class="privacy-container">
        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-shield-alt"></i> Information We Collect</h2>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li>Personal identification information (name, email address, phone number)</li>
                <li>Academic information (educational background, grades, achievements)</li>
                <li>Financial information (for scholarship applications)</li>
                <li>Usage data (how you interact with our website)</li>
            </ul>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-lock"></i> How We Use Your Information</h2>
            <p>We use the collected information for various purposes:</p>
            <ul>
                <li>To process and manage your scholarship applications</li>
                <li>To communicate with you about your applications</li>
                <li>To improve our services and website functionality</li>
                <li>To send you updates and notifications</li>
                <li>To comply with legal obligations</li>
            </ul>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-share-alt"></i> Information Sharing</h2>
            <p>We may share your information with:</p>
            <ul>
                <li>Scholarship providers and educational institutions</li>
                <li>Service providers who assist in our operations</li>
                <li>Legal authorities when required by law</li>
            </ul>
            <h3>Third-Party Services</h3>
            <p>We use third-party services for analytics, payment processing, and communication. These services have their own privacy policies.</p>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-user-shield"></i> Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal information</li>
                <li>Correct inaccurate information</li>
                <li>Request deletion of your information</li>
                <li>Opt-out of marketing communications</li>
                <li>Withdraw consent for data processing</li>
            </ul>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-cookie"></i> Cookies and Tracking</h2>
            <p>We use cookies and similar tracking technologies to:</p>
            <ul>
                <li>Remember your preferences</li>
                <li>Analyze website traffic</li>
                <li>Improve user experience</li>
            </ul>
            <p>You can control cookies through your browser settings.</p>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-shield-virus"></i> Security Measures</h2>
            <p>We implement appropriate security measures to protect your information:</p>
            <ul>
                <li>Encryption of sensitive data</li>
                <li>Regular security assessments</li>
                <li>Access controls and authentication</li>
                <li>Secure data storage</li>
            </ul>
        </div>

        <div class="privacy-section" data-aos="fade-up">
            <h2><i class="fas fa-question-circle"></i> Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us:</p>
            <ul>
                <li>Email: scholarhub517@gmail.com</li>
                <li>Phone: +91 9353797345</li>
                <!-- <li></li> -->
            </ul>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

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
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Back to Top Button
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html> 