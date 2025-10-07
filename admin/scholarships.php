<?php
// Ensure consistent session handling with init.php
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }

        switch ($_POST['action']) {
            case 'edit':
                if (!isset($_POST['scholarship_id']) || !is_numeric($_POST['scholarship_id'])) {
                    throw new Exception("Invalid scholarship ID");
                }

                // Start transaction
                $pdo->beginTransaction();

                // Update scholarship details
                $stmt = $pdo->prepare("
                    UPDATE scholarships 
                    SET scholarship_name = ?,
                        description = ?,
                        amount = ?,
                        deadline = ?,
                        requirements = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    trim($_POST['scholarship_name']),
                    trim($_POST['description']),
                    floatval($_POST['amount']),
                    $_POST['deadline'],
                    trim($_POST['requirements']),
                    $_POST['status'],
                    $_POST['scholarship_id']
                ]);

                // Delete existing document requirements
                $stmt = $pdo->prepare("DELETE FROM scholarship_document_requirements WHERE scholarship_id = ?");
                $stmt->execute([$_POST['scholarship_id']]);

                // Insert updated document requirements
                if (isset($_POST['document_names']) && is_array($_POST['document_names'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO scholarship_document_requirements 
                        (scholarship_id, document_name, document_type, is_required, description) 
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    foreach ($_POST['document_names'] as $key => $name) {
                        if (!empty(trim($name))) {
                            $stmt->execute([
                                $_POST['scholarship_id'],
                                trim($name),
                                trim($_POST['document_types'][$key]),
                                $_POST['is_required'][$key],
                                isset($_POST['document_descriptions'][$key]) ? trim($_POST['document_descriptions'][$key]) : null
                            ]);
                        }
                    }
                }

                $pdo->commit();
                $_SESSION['success'] = "Scholarship updated successfully.";
                break;

            case 'delete':
                if (!isset($_POST['scholarship_id'])) {
                    throw new Exception("Scholarship ID is required");
                }

                $scholarshipId = $_POST['scholarship_id'];

                // Start transaction
                $pdo->beginTransaction();

                // Delete related records first
                $stmt = $pdo->prepare("DELETE FROM scholarship_document_requirements WHERE scholarship_id = ?");
                $stmt->execute([$scholarshipId]);

                $stmt = $pdo->prepare("DELETE FROM applications WHERE scholarship_id = ?");
                $stmt->execute([$scholarshipId]);

                // Finally delete the scholarship
                $stmt = $pdo->prepare("DELETE FROM scholarships WHERE id = ?");
                $stmt->execute([$scholarshipId]);

                // Commit transaction
                $pdo->commit();
                $_SESSION['success'] = "Scholarship deleted successfully.";
                break;

            default:
                throw new Exception("Invalid action specified");
        }
        
        // Redirect to prevent form resubmission
        header("Location: scholarships.php");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error managing scholarships: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: scholarships.php");
        exit();
    }
}

// Fetch all scholarships
try {
    $stmt = $pdo->query("SELECT s.*, COUNT(a.id) as application_count 
                         FROM scholarships s 
                         LEFT JOIN applications a ON s.id = a.scholarship_id 
                         GROUP BY s.id 
                         ORDER BY s.created_at DESC");
    $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching scholarships: " . $e->getMessage());
    $scholarships = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships Management - ScholarHub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .scholarships-table-container {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-add {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete {
            background-color: var(--error-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete:hover {
            background-color: #dc3545;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--bg-color);
            font-weight: 600;
        }

        tr:hover {
            background-color: var(--bg-color);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Scholarships Management</h1>
                <a href="add-scholarship.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Scholarship
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="scholarships-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Deadline</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scholarships)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No scholarships found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scholarships as $scholarship): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></td>
                                    <td>₹<?php echo number_format($scholarship['amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($scholarship['deadline'])); ?></td>
                                    <td><?php echo $scholarship['application_count']; ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($scholarship['status'])); ?></td>
                                    <td class="actions">
                                        <a href="edit-scholarship.php?id=<?php echo $scholarship['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this scholarship?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="scholarship_id" value="<?php echo $scholarship['id']; ?>">
                                            <button type="submit" class="btn-delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>