<?php
require_once '../includes/init.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Check admin authentication
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize variables
$scholarship = null;
$error = null;

// Check if scholarship ID is provided
if (!isset($_GET['id'])) {
    header("Location: scholarships.php");
    exit();
}

// Fetch scholarship details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               GROUP_CONCAT(
                   CONCAT(
                       sdr.document_name, '|',
                       sdr.document_type, '|',
                       sdr.is_required, '|',
                       IFNULL(sdr.description, '')
                   )
                   SEPARATOR ';;'
               ) as document_requirements
        FROM scholarships s
        LEFT JOIN scholarship_document_requirements sdr ON s.id = sdr.scholarship_id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$_GET['id']]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scholarship) {
        $_SESSION['error'] = "Scholarship not found.";
        header("Location: scholarships.php");
        exit();
    }

    // Parse document requirements
    $documentRequirements = [];
    if (!empty($scholarship['document_requirements'])) {
        $requirements = explode(';;', $scholarship['document_requirements']);
        foreach ($requirements as $req) {
            list($name, $type, $required, $desc) = explode('|', $req);
            $documentRequirements[] = [
                'name' => $name,
                'type' => $type,
                'required' => $required == '1',
                'description' => $desc
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching scholarship: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch scholarship details.";
    header("Location: scholarships.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scholarship - ScholarHub Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        /* Additional styles specific to edit scholarship page */
        .edit-scholarship-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .card-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .status-select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.95rem;
            width: 100%;
            max-width: 200px;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .currency-symbol {
            padding: 0.75rem;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 4px 0 0 4px;
            color: var(--text-color);
        }

        .input-group .form-control {
            border-radius: 0 4px 4px 0;
        }

        .document-entry {
            margin-bottom: 1rem;
        }
        .document-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 3fr auto;
            gap: 1rem;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        .remove-document {
            padding: 0.75rem;
            height: fit-content;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="edit-scholarship-card">
                <div class="card-header">
                    <h1 class="card-title">Edit Scholarship</h1>
                    <a href="scholarships.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Scholarships
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php 
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="scholarships.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="scholarship_id" value="<?php echo htmlspecialchars($scholarship['id']); ?>">

                    <div class="form-group">
                        <label class="form-label" for="scholarship_name">Scholarship Name</label>
                        <input type="text" id="scholarship_name" name="scholarship_name" class="form-control" 
                               value="<?php echo htmlspecialchars($scholarship['scholarship_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($scholarship['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (₹)</label>
                        <input type="number" step="0.01" min="0" id="amount" name="amount" class="form-control" value="<?php echo htmlspecialchars($scholarship['amount'] ?? ''); ?>" required>
                        <small class="form-text text-muted">Enter the scholarship amount in Indian Rupees (₹)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="deadline">Application Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime($scholarship['deadline'])); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" class="form-control" required><?php echo htmlspecialchars($scholarship['requirements']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="status-select">
                            <option value="active" <?php echo $scholarship['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $scholarship['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required Documents</label>
                        <div id="documents-container">
                            <?php foreach ($documentRequirements as $doc): ?>
                            <div class="document-entry">
                                <div class="document-row">
                                    <input type="text" name="document_names[]" class="form-control" 
                                           value="<?php echo htmlspecialchars($doc['name']); ?>" required>
                                    <input type="text" name="document_types[]" class="form-control" 
                                           value="<?php echo htmlspecialchars($doc['type']); ?>" required>
                                    <select name="is_required[]" class="form-control">
                                        <option value="1" <?php echo $doc['required'] ? 'selected' : ''; ?>>Required</option>
                                        <option value="0" <?php echo !$doc['required'] ? 'selected' : ''; ?>>Optional</option>
                                    </select>
                                    <textarea name="document_descriptions[]" class="form-control" 
                                              placeholder="Document Description"><?php echo htmlspecialchars($doc['description']); ?></textarea>
                                    <button type="button" class="btn btn-danger remove-document" onclick="removeDocument(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addDocument()">
                            <i class="fas fa-plus"></i> Add Document
                        </button>
                        <div class="helper-text">Specify the documents that applicants need to submit.</div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="scholarships.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function addDocument() {
            const container = document.getElementById('documents-container');
            const documentEntry = document.createElement('div');
            documentEntry.className = 'document-entry';
            documentEntry.innerHTML = `
                <div class="document-row">
                    <input type="text" name="document_names[]" class="form-control" 
                           placeholder="Document Name" required>
                    <input type="text" name="document_types[]" class="form-control" 
                           placeholder="Document Type" required>
                    <select name="is_required[]" class="form-control">
                        <option value="1">Required</option>
                        <option value="0">Optional</option>
                    </select>
                    <textarea name="document_descriptions[]" class="form-control" 
                              placeholder="Document Description"></textarea>
                    <button type="button" class="btn btn-danger remove-document" onclick="removeDocument(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(documentEntry);
        }

        function removeDocument(button) {
            const documentEntry = button.closest('.document-entry');
            if (documentEntry) {
                documentEntry.remove();
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const name = document.getElementById('scholarship_name').value.trim();
            const amount = document.getElementById('amount').value;
            const deadline = document.getElementById('deadline').value;
            const requirements = document.getElementById('requirements').value.trim();

            if (!name || !amount || !deadline || !requirements) {
                alert('Please fill in all required fields');
                return;
            }

            if (amount <= 0) {
                alert('Amount must be greater than 0');
                return;
            }

            // Validate document requirements
            const documentNames = document.getElementsByName('document_names[]');
            let hasDocuments = false;
            for (let i = 0; i < documentNames.length; i++) {
                if (documentNames[i].value.trim()) {
                    hasDocuments = true;
                    break;
                }
            }

            if (!hasDocuments) {
                alert('Please add at least one document requirement');
                return;
            }

            // If all validation passes, submit the form
            this.submit();
        });
    </script>
</body>
</html> 