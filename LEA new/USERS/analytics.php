<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get application statistics
$sql = "SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
            SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
            SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted_applications,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
            SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired_applications
        FROM applications 
        WHERE user_id = :user_id";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $application_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get saved jobs count
    $sql = "SELECT COUNT(*) as saved_jobs FROM favorites WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $saved_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['saved_jobs'];

    // Get recent applications
    $sql = "SELECT a.*, j.title, j.company, j.type, j.location
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            WHERE a.user_id = :user_id
            ORDER BY a.application_date DESC
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get application status distribution
    $sql = "SELECT status, COUNT(*) as count
            FROM applications
            WHERE user_id = :user_id
            GROUP BY status";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly application trend
    $sql = "SELECT 
                DATE_FORMAT(application_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM applications
            WHERE user_id = :user_id
            GROUP BY DATE_FORMAT(application_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - LEA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #02486b;
            --secondary-color: #036fa3;
            --accent-color: #2196F3;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .page-title {
            color: var(--text-dark);
            font-size: 1.5em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-title {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .stat-value {
            color: var(--text-dark);
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-description {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .chart-container {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .chart-title {
            color: var(--text-dark);
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .recent-applications {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .application-card {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .application-card:last-child {
            border-bottom: none;
        }

        .application-title {
            color: var(--text-dark);
            font-size: 1.1em;
            margin-bottom: 5px;
        }

        .application-company {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .application-details {
            display: flex;
            gap: 15px;
            color: var(--text-light);
            font-size: 0.9em;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-top: 5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #cce5ff; color: #004085; }
        .status-shortlisted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-hired { background: #d1e7dd; color: #0f5132; }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">📊 Analytics</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Applications</div>
                <div class="stat-value"><?php echo $application_stats['total_applications']; ?></div>
                <div class="stat-description">Total jobs you've applied to</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Applications</div>
                <div class="stat-value"><?php echo $application_stats['pending_applications']; ?></div>
                <div class="stat-description">Applications under review</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Shortlisted</div>
                <div class="stat-value"><?php echo $application_stats['shortlisted_applications']; ?></div>
                <div class="stat-description">Applications shortlisted</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Saved Jobs</div>
                <div class="stat-value"><?php echo $saved_jobs; ?></div>
                <div class="stat-description">Jobs in your favorites</div>
            </div>
        </div>

        <div class="chart-container">
            <h2 class="chart-title">Application Status Distribution</h2>
            <canvas id="statusChart"></canvas>
        </div>

        <div class="chart-container">
            <h2 class="chart-title">Monthly Application Trend</h2>
            <canvas id="trendChart"></canvas>
        </div>

        <div class="recent-applications">
            <h2 class="chart-title">Recent Applications</h2>
            <?php
            if (count($recent_applications) > 0) {
                foreach ($recent_applications as $application) {
                    ?>
                    <div class="application-card">
                        <div class="application-title"><?php echo htmlspecialchars($application['title']); ?></div>
                        <div class="application-company">🏢 <?php echo htmlspecialchars($application['company']); ?></div>
                        <div class="application-details">
                            <span>💼 <?php echo htmlspecialchars($application['type']); ?></span>
                            <span>📍 <?php echo htmlspecialchars($application['location']); ?></span>
                            <span>📅 <?php echo date('M j, Y', strtotime($application['application_date'])); ?></span>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                            <?php echo ucfirst($application['status']); ?>
                        </span>
                    </div>
                    <?php
                }
            } else {
                echo '<p style="text-align: center; color: var(--text-light);">No applications yet</p>';
            }
            ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
        }

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Reviewed', 'Shortlisted', 'Rejected', 'Hired'],
                datasets: [{
                    data: [
                        <?php echo $application_stats['pending_applications']; ?>,
                        <?php echo $application_stats['reviewed_applications']; ?>,
                        <?php echo $application_stats['shortlisted_applications']; ?>,
                        <?php echo $application_stats['rejected_applications']; ?>,
                        <?php echo $application_stats['hired_applications']; ?>
                    ],
                    backgroundColor: [
                        '#fff3cd',
                        '#cce5ff',
                        '#d4edda',
                        '#f8d7da',
                        '#d1e7dd'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    $months = [];
                    $counts = [];
                    foreach ($monthly_trend as $trend) {
                        $months[] = date('M Y', strtotime($trend['month'] . '-01'));
                        $counts[] = $trend['count'];
                    }
                    echo "'" . implode("','", array_reverse($months)) . "'";
                    ?>
                ],
                datasets: [{
                    label: 'Applications',
                    data: [<?php echo implode(',', array_reverse($counts)); ?>],
                    borderColor: '#02486b',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 