<?php
session_start();
require_once 'includes/Auth.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Get the application ID from URL
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($application_id === 0) {
    header("Location: applications.php");
    exit();
}

// Static application data (in a real application, this would come from a database)
$applications = [
    1 => [
        'title' => 'Merit-based Scholarship 2024',
        'description' => 'Scholarship for outstanding academic achievement',
        'status' => 'Under Review',
        'applied_date' => '2024-02-15',
        'amount' => '$5,000',
        'timeline' => [
            ['date' => '2024-02-15', 'event' => 'Application Submitted'],
            ['date' => '2024-02-16', 'event' => 'Document Verification Started'],
            ['date' => '2024-02-20', 'event' => 'Initial Review Completed']
        ]
    ],
    2 => [
        'title' => 'Need-based Financial Aid',
        'description' => 'Financial assistance for students from low-income backgrounds',
        'status' => 'Approved',
        'applied_date' => '2024-01-10',
        'amount' => '$7,500',
        'timeline' => [
            ['date' => '2024-01-10', 'event' => 'Application Submitted'],
            ['date' => '2024-01-15', 'event' => 'Document Verification Completed'],
            ['date' => '2024-01-20', 'event' => 'Financial Assessment Done'],
            ['date' => '2024-02-01', 'event' => 'Application Approved']
        ]
    ],
    3 => [
        'title' => 'Sports Excellence Scholarship',
        'description' => 'Scholarship for outstanding sports achievements',
        'status' => 'Pending',
        'applied_date' => '2024-03-01',
        'amount' => '$3,000',
        'timeline' => [
            ['date' => '2024-03-01', 'event' => 'Application Submitted'],
            ['date' => '2024-03-02', 'event' => 'Awaiting Sports Department Review']
        ]
    ]
];

// Check if the application exists
if (!isset($applications[$application_id])) {
    header("Location: applications.php");
    exit();
}

$application = $applications[$application_id];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --text-color: #2c3e50;
            --border-color: #e0e0e0;
            --background-color: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .application-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .application-title {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .application-description {
            color: #666;
            margin-bottom: 1rem;
        }

        .application-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .status-Approved { background-color: #d4edda; color: #155724; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Under { background-color: #cce5ff; color: #004085; }

        .timeline {
            position: relative;
            margin: 1.5rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: var(--border-color);
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }

        .timeline-date {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.15rem;
            font-size: 0.9rem;
        }

        .timeline-event {
            font-weight: 500;
            margin-bottom: 0.15rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 2rem;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #357abd;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .application-header {
                flex-direction: column;
                gap: 1rem;
            }

            .application-meta {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="applications.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Applications
        </a>

        <div class="card">
            <div class="application-header">
                <div class="application-info">
                    <h1 class="application-title"><?php echo htmlspecialchars($application['title']); ?></h1>
                    <p class="application-description"><?php echo htmlspecialchars($application['description']); ?></p>
                    <div class="application-meta">
                        <div class="meta-item">
                            <i class="far fa-calendar"></i>
                            <span>Applied: <?php echo htmlspecialchars($application['applied_date']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Amount: <?php echo htmlspecialchars($application['amount']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="status-badge status-<?php echo str_replace(' ', '', $application['status']); ?>">
                                <?php echo htmlspecialchars($application['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <h2>Application Timeline</h2>
            <div class="timeline">
                <?php foreach ($application['timeline'] as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-date"><?php echo htmlspecialchars($event['date']); ?></div>
                    <div class="timeline-event"><?php echo htmlspecialchars($event['event']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html> 