<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ScholarHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Settings Specific Styles */
        .settings-container {
            margin-top: 70px;
            padding: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .settings-header {
            margin-bottom: 2rem;
        }

        .settings-header h1 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .settings-section {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
            outline: none;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--primary-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .toggle-label span {
            flex: 1;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .settings-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .settings-container {
                padding: 1rem;
            }

            .settings-actions {
                flex-direction: column;
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

    <!-- Settings Container -->
    <div class="settings-container">
        <div class="settings-header">
            <h1>Account Settings</h1>
            <p>Manage your account preferences and security settings</p>
        </div>

        <div class="settings-card">
            <!-- Account Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-user-cog"></i>
                    Account Settings
                </h3>
                <div class="form-group">
                    <label for="language">Preferred Language</label>
                    <select id="language" class="form-control">
                        <option value="en">English</option>
                        <option value="hi">Hindi</option>
                        <option value="mr">Marathi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select id="timezone" class="form-control">
                        <option value="IST">Indian Standard Time (IST)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-bell"></i>
                    Notification Settings
                </h3>
                <div class="toggle-label">
                    <span>Email Notifications</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-label">
                    <span>Application Status Updates</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-label">
                    <span>New Scholarship Alerts</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-label">
                    <span>Deadline Reminders</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </h3>
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" class="form-control">
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" class="form-control">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" class="form-control">
                </div>
                <div class="toggle-label">
                    <span>Two-Factor Authentication</span>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Privacy Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-lock"></i>
                    Privacy Settings
                </h3>
                <div class="toggle-label">
                    <span>Show Profile to Other Users</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-label">
                    <span>Allow Scholarship Providers to Contact</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Account Actions
                </h3>
                <div class="settings-actions">
                    <button class="btn btn-primary">Save Changes</button>
                    <button class="btn btn-danger">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Toggle switch functionality
        const toggleSwitches = document.querySelectorAll('.toggle-switch input');
        toggleSwitches.forEach(switchInput => {
            switchInput.addEventListener('change', (e) => {
                // Here you would typically save the preference to the server
                console.log('Toggle changed:', e.target.checked);
            });
        });

        // Form submission
        const settingsForm = document.querySelector('.settings-card');
        settingsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Here you would typically send the form data to the server
            alert('Settings updated successfully!');
        });

        // Delete account confirmation
        const deleteButton = document.querySelector('.btn-danger');
        deleteButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                // Here you would typically send a request to delete the account
                alert('Account deletion request sent. Please check your email for confirmation.');
            }
        });
    </script>
</body>
</html> 