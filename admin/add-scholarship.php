<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Check if user is logged in and is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['scholarship_name']) || empty($_POST['amount']) || empty($_POST['deadline']) || empty($_POST['description'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Validate amount
        if (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
            throw new Exception("Amount must be a positive number");
        }

        // Validate deadline date
        $deadline = new DateTime($_POST['deadline']);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Reset time to start of day for fair comparison
        
        if ($deadline < $today) {
            throw new Exception("Deadline cannot be set to a past date");
        }

        // Start transaction
        $pdo->beginTransaction();

        // Insert scholarship
        $stmt = $pdo->prepare("INSERT INTO scholarships (scholarship_name, description, amount, deadline, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            trim($_POST['scholarship_name']),
            trim($_POST['description']),
            floatval($_POST['amount']),
            $_POST['deadline']
        ]);

        $scholarship_id = $pdo->lastInsertId();

        // Handle document requirements
        if (!empty($_POST['doc_names']) && is_array($_POST['doc_names'])) {
            $stmt = $pdo->prepare("INSERT INTO scholarship_document_requirements (scholarship_id, document_name, is_required) VALUES (?, ?, ?)");
            
            foreach ($_POST['doc_names'] as $key => $name) {
                if (!empty(trim($name))) {
                    $stmt->execute([
                        $scholarship_id,
                        trim($name),
                        isset($_POST['doc_required'][$key]) ? 1 : 0
                    ]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Scholarship added successfully!";
        
        // Send notification to all active users about the new scholarship
        try {
            require_once '../includes/NotificationManager.php';
            $notificationManager = new NotificationManager();

            // Fetch all active user IDs
            $stmtUsers = $pdo->prepare("SELECT id FROM users WHERE status = 'active'");
            $stmtUsers->execute();
            $activeUserIds = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

            $notificationTitle = "New Scholarship Available!";
            $notificationMessage = "A new scholarship, '" . htmlspecialchars($_POST['scholarship_name']) . "', has been added. Check it out!";

            foreach ($activeUserIds as $userId) {
                $notificationManager->addNotification($userId, $notificationTitle, $notificationMessage, 'scholarship_added');
            }

            error_log("Sent new scholarship notification to " . count($activeUserIds) . " active users.");

        } catch (Exception $e) {
            error_log("Failed to send new scholarship notifications: " . $e->getMessage());
            // Optionally, set a session warning if notifications failed
            // $_SESSION['warning'] = "New scholarship added, but failed to send notifications.";
        }

        header("Location: scholarships.php");
        exit();

    } catch (PDOException $e) {
        error_log("Database error when adding scholarship: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add scholarship. Database error.";
    } catch (Exception $e) {
        error_log("Error adding scholarship: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add scholarship: " . $e->getMessage();
    }
}

// Get today's date in YYYY-MM-DD format for the min attribute
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship - ScholarHub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .document-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .document-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .btn-add-doc {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .btn-remove-doc {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
        }
        .checkbox-group {
            margin-top: 5px;
        }
        .checkbox-group label {
            display: inline;
            font-weight: normal;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Add New Scholarship</h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" id="scholarshipForm" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="scholarship_name">Scholarship Name *</label>
                        <input type="text" id="scholarship_name" name="scholarship_name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (₹) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="deadline">Application Deadline *</label>
                        <input type="date" id="deadline" name="deadline" min="<?php echo $today; ?>" required>
                        <div id="deadlineError" class="error-message">Deadline cannot be set to a past date</div>
                    </div>

                    <div class="document-section">
                        <h3>Required Documents</h3>
                        <button type="button" class="btn-add-doc" onclick="addDocument()">
                            <i class="fas fa-plus"></i> Add Document Requirement
                        </button>
                        <div id="document-list"></div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Scholarship
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function addDocument() {
            const container = document.getElementById('document-list');
            const index = container.children.length;
            
            const docElement = document.createElement('div');
            docElement.className = 'document-item';
            docElement.innerHTML = `
                <button type="button" class="btn-remove-doc" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="form-group">
                    <label>Document Name *</label>
                    <input type="text" name="doc_names[]" required placeholder="e.g., Transcript, ID Card">
                </div>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="doc_required[]" value="${index}" checked>
                        This document is required
                    </label>
                </div>
            `;
            
            container.appendChild(docElement);
        }

        function validateForm() {
            const deadlineInput = document.getElementById('deadline');
            const deadlineError = document.getElementById('deadlineError');
            const selectedDate = new Date(deadlineInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day

            if (selectedDate < today) {
                deadlineError.style.display = 'block';
                deadlineInput.focus();
                return false;
            }

            deadlineError.style.display = 'none';
            return true;
        }

        // Add event listener to deadline input to validate on change
        document.getElementById('deadline').addEventListener('change', function() {
            const deadlineError = document.getElementById('deadlineError');
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                deadlineError.style.display = 'block';
            } else {
                deadlineError.style.display = 'none';
            }
        });
    </script>
</body>
</html> 