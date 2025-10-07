<?php
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'includes/Database.php';
require_once 'includes/init.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Enable PDO error mode
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $errors = [];

        // Validate input
        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($subject)) $errors[] = 'Subject is required.';
        if (empty($message)) $errors[] = 'Message is required.';

        if (empty($errors)) {
            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert into contact_messages
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $subject, $message]);
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode(['success' => true]);
                exit;

            } catch (PDOException $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                error_log("Database error in contact form: " . $e->getMessage());
                echo json_encode([
                    'success' => false, 
                    'errors' => ['Sorry, there was a problem saving your message. Please try again later.']
                ]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

    } catch (Exception $e) {
        error_log("General error in contact form: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'errors' => ['An unexpected error occurred. Please try again later.']
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Contact Page Specific Styles */
        .contact-hero {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%);
            padding: 6rem 2rem 4rem;
            text-align: center;
        }

        .contact-hero h1 {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }

        .contact-hero p {
            font-size: 1.2rem;
            color: var(--text-color);
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.2s;
        }

        .contact-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 40px;
            padding: 40px 0;
            min-height: 80vh;
            flex-wrap: wrap;
        }
        .contact-info, .contact-form {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44, 62, 80, 0.08);
            padding: 32px 28px;
            width: 370px;
            min-width: 300px;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .contact-form form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 1rem;
            background: #f9fafb;
            transition: border 0.2s;
        }
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        .submit-btn {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #217dbb;
        }
        @media (max-width: 900px) {
            .contact-container {
                flex-direction: column;
                align-items: center;
                gap: 24px;
            }
            .contact-info, .contact-form {
                width: 95%;
                min-width: unset;
            }
        }
        .contact-info h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .contact-info h2 i {
            font-size: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item i {
            font-size: 1.2rem;
            color: var(--primary-color);
            width: 30px;
            height: 30px;
            background: rgba(74, 144, 226, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-item p {
            color: var(--text-color);
            line-height: 1.6;
        }

        .map-container {
            grid-column: 1 / -1;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .success-message {
            background: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .error-message {
            background: #f44336;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 2rem;
            }

            .contact-hero p {
                font-size: 1rem;
            }

            .contact-container {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }
        }

        .social-links {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 28px;
        }
        .social-icon {
            font-size: 2rem;
            color: #3498db;
            transition: color 0.2s;
            margin: 0;
        }
        .social-icon:hover {
            color: #217dbb;
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

    <!-- Contact Hero Section -->
    <section class="contact-hero">
        <h1>Contact Us</h1>
        <p>Have questions or need assistance? We're here to help you with your scholarship journey.</p>
    </section>

    <!-- Contact Container -->
    <div class="contact-container">
        <!-- Contact Information -->
        <div class="contact-info" data-aos="fade-right">
            <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <p>ScholarHub</p>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <p>+91 9353797345</p>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <p>schoalrhub517@gmail.com</p>
            </div>
         <div class="social-links">
                <a href="https://www.facebook.com/share/16F2Lnbzwv/" class="social-icon"><i class="fab fa-facebook"></i></a>
                <a href="" class="social-icon"><i class="fa-brands fa-x-twitter"></i></a>
                <!-- <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a> -->
                <a href="https://www.instagram.com/scholarhub2?igsh=cXAxOWF0NWs3cDN3" class="social-icon"><i class="fab fa-instagram"></i></a>
            </div>   
        </div>

        <!-- Contact Form -->
        <div class="contact-form" data-aos="fade-left">
            <h2><i class="fas fa-paper-plane"></i> Send us a Message</h2>
            <div class="success-message" id="success-message" style="display: none;">
                <i class="fas fa-check-circle"></i> Message sent successfully!
            </div>
            <div class="error-message" id="error-message" style="display: none;">
                <i class="fas fa-exclamation-circle"></i> <span class="error-text"></span>
            </div>
            <form id="contactForm" method="POST">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
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
                <p>Email:schoalrhub517@gmail.com</p>
                <p>Phone:+91 9353797345 </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ScholarHub. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Form submission handling (AJAX)
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Disable submit button and show loading state
            const submitBtn = this.querySelector('.submit-btn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Send form data
            fetch('contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Your message has been sent successfully.',
                        confirmButtonColor: '#3498db'
                    });
                    
                    // Clear form
                    this.reset();
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.errors ? data.errors.join('\n') : 'Something went wrong!',
                        confirmButtonColor: '#3498db'
                    });
                }
            })
            .catch(error => {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong! Please try again later.',
                    confirmButtonColor: '#3498db'
                });
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
