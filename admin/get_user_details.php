<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Security.php';
require_once '../includes/Logger.php';

$auth = new Auth();
$security = Security::getInstance();
$logger = Logger::getInstance();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid user ID');
}

$user = $auth->getUserDetails($userId);
if (!$user) {
    header('HTTP/1.1 404 Not Found');
    exit('User not found');
}

// Generate HTML for user details
$html = '
<div class="user-details">
    <div class="user-header">
        <div class="user-avatar-large">
            ' . ($user['profile_picture'] ? 
                '<img src="../uploads/profile/' . htmlspecialchars($user['profile_picture']) . '" alt="Profile Picture">' : 
                '<i class="fas fa-user"></i>') . '
        </div>
        <div class="user-info">
            <h2>' . htmlspecialchars($user['full_name']) . '</h2>
            <p class="text-muted">ID: ' . htmlspecialchars($user['id']) . '</p>
            <p class="text-muted">Username: ' . htmlspecialchars($user['username']) . '</p>
            <p class="text-muted">Email: ' . htmlspecialchars($user['email']) . '</p>
        </div>
    </div>

    <div class="user-stats">
        <div class="stat-card">
            <h3>Total Applications</h3>
            <p class="stat-number">' . $user['total_applications'] . '</p>
        </div>
        <div class="stat-card">
            <h3>Approved</h3>
            <p class="stat-number">' . $user['approved_applications'] . '</p>
        </div>
        <div class="stat-card">
            <h3>Pending</h3>
            <p class="stat-number">' . $user['pending_applications'] . '</p>
        </div>
        <div class="stat-card">
            <h3>Rejected</h3>
            <p class="stat-number">' . $user['rejected_applications'] . '</p>
        </div>
    </div>

    <div class="user-details-section">
        <h3>Contact Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Phone:</label>
                <span>' . ($user['phone'] ? htmlspecialchars($user['phone']) : 'Not provided') . '</span>
            </div>
            <div class="info-item">
                <label>Address:</label>
                <span>' . ($user['address'] ? htmlspecialchars($user['address']) : 'Not provided') . '</span>
            </div>
            <div class="info-item">
                <label>Status:</label>
                <span class="badge badge-' . $user['status'] . '">' . ucfirst($user['status']) . '</span>
            </div>
            <div class="info-item">
                <label>Email Verified:</label>
                <span class="badge badge-' . ($user['email_verified'] ? 'success' : 'warning') . '">
                    ' . ($user['email_verified'] ? 'Yes' : 'No') . '
                </span>
            </div>
        </div>
    </div>

    <div class="user-details-section">
        <h3>Recent Applications</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Scholarship</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';

foreach ($user['recent_applications'] as $application) {
    $html .= '
                    <tr>
                        <td>' . htmlspecialchars($application['scholarship_name']) . '</td>
                        <td><span class="badge badge-' . $application['status'] . '">' . 
                            ucfirst($application['status']) . '</span></td>
                        <td>' . date('Y-m-d', strtotime($application['created_at'])) . '</td>
                    </tr>';
}

$html .= '
                </tbody>
            </table>
        </div>
    </div>

    <div class="user-details-section">
        <h3>Recent Activity</h3>
        <div class="activity-list">';

foreach ($user['recent_activity'] as $activity) {
    $html .= '
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="activity-details">
                    <div class="activity-title">' . htmlspecialchars($activity['activity_type']) . '</div>
                    <div class="activity-time">' . date('Y-m-d H:i', strtotime($activity['created_at'])) . '</div>
                </div>
            </div>';
}

$html .= '
        </div>
    </div>
</div>';

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['html' => $html]); 