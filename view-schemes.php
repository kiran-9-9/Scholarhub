<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Schemes - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* View Schemes Specific Styles */
        .schemes-container {
            margin-top: 70px;
            padding: 2rem;
        }

        .schemes-header {
            margin-bottom: 2rem;
        }

        .schemes-header h1 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-dropdown {
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            min-width: 200px;
        }

        .schemes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .scheme-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .scheme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .scheme-image {
            height: 200px;
            background: var(--light-bg);
            position: relative;
            overflow: hidden;
        }

        .scheme-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .scheme-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-open {
            background: #4CAF50;
            color: white;
        }

        .badge-closed {
            background: #f44336;
            color: white;
        }

        .scheme-content {
            padding: 1.5rem;
        }

        .scheme-title {
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .scheme-provider {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .scheme-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .detail-item {
            text-align: center;
        }

        .detail-value {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #666;
        }

        .scheme-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .scheme-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-outline {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .schemes-container {
                padding: 1rem;
            }

            .search-filter {
                flex-direction: column;
            }

            .search-box, .filter-dropdown {
                width: 100%;
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

    <!-- View Schemes Container -->
    <div class="schemes-container">
        <div class="schemes-header">
            <h1>Available Scholarship Schemes</h1>
            <p>Browse through our collection of scholarship opportunities</p>
        </div>

        <div class="search-filter">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search scholarships...">
            </div>
            <select class="filter-dropdown">
                <option value="">All Categories</option>
                <option value="merit">Merit-based</option>
                <option value="need">Need-based</option>
                <option value="sports">Sports</option>
                <option value="arts">Arts</option>
            </select>
            <select class="filter-dropdown">
                <option value="">All Amounts</option>
                <option value="0-50000">₹0 - ₹50,000</option>
                <option value="50000-100000">₹50,000 - ₹1,00,000</option>
                <option value="100000+">₹1,00,000+</option>
            </select>
        </div>

        <div class="schemes-grid">
            <!-- Scheme Card 1 -->
            <div class="scheme-card">
                <div class="scheme-image">
                    <img src="https://via.placeholder.com/300x200" alt="Scholarship Image">
                    <span class="scheme-badge badge-open">Open</span>
                </div>
                <div class="scheme-content">
                    <h3 class="scheme-title">National Merit Scholarship</h3>
                    <p class="scheme-provider">Ministry of Education</p>
                    <div class="scheme-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹<?php echo number_format($scholarship['amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-value">30</div>
                            <div class="detail-label">Days Left</div>
                        </div>
                    </div>
                    <p class="scheme-description">
                        This scholarship is awarded to students who have demonstrated exceptional academic performance in their previous studies.
                    </p>
                    <div class="scheme-actions">
                        <a href="#" class="btn btn-primary">Apply Now</a>
                        <a href="#" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>

            <!-- Scheme Card 2 -->
            <div class="scheme-card">
                <div class="scheme-image">
                    <img src="https://via.placeholder.com/300x200" alt="Scholarship Image">
                    <span class="scheme-badge badge-open">Open</span>
                </div>
                <div class="scheme-content">
                    <h3 class="scheme-title">Sports Excellence Scholarship</h3>
                    <p class="scheme-provider">Sports Authority of India</p>
                    <div class="scheme-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹<?php echo number_format($scholarship['amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-value">15</div>
                            <div class="detail-label">Days Left</div>
                        </div>
                    </div>
                    <p class="scheme-description">
                        For students who have represented their state or country in national/international sports competitions.
                    </p>
                    <div class="scheme-actions">
                        <a href="#" class="btn btn-primary">Apply Now</a>
                        <a href="#" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>

            <!-- Scheme Card 3 -->
            <div class="scheme-card">
                <div class="scheme-image">
                    <img src="https://via.placeholder.com/300x200" alt="Scholarship Image">
                    <span class="scheme-badge badge-closed">Closed</span>
                </div>
                <div class="scheme-content">
                    <h3 class="scheme-title">Women in STEM Scholarship</h3>
                    <p class="scheme-provider">Tech Foundation</p>
                    <div class="scheme-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹<?php echo number_format($scholarship['amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-value">0</div>
                            <div class="detail-label">Days Left</div>
                        </div>
                    </div>
                    <p class="scheme-description">
                        Supporting women pursuing education in Science, Technology, Engineering, and Mathematics fields.
                    </p>
                    <div class="scheme-actions">
                        <a href="#" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        const schemeCards = document.querySelectorAll('.scheme-card');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            schemeCards.forEach(card => {
                const title = card.querySelector('.scheme-title').textContent.toLowerCase();
                const description = card.querySelector('.scheme-description').textContent.toLowerCase();
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter functionality
        const filterDropdowns = document.querySelectorAll('.filter-dropdown');
        filterDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', () => {
                // Implement filter logic here
                console.log('Filter changed:', dropdown.value);
            });
        });
    </script>
</body>
</html> 