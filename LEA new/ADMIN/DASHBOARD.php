<?php
session_start();
require_once 'DATABASE/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize chart data variables
$jobTrends = [];
$jobTrendsLabels = [];
$appStatusLabels = [];
$appStatusCounts = [];

// Fetch dashboard statistics
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jobs WHERE status = 'active'");
    $stmt->execute();
    $activeJobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM applications");
    $stmt->execute();
    $totalApplications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total revenue (from payments table)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
    $stmt->execute();
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch Job Posting Trends (last 6 months)
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count FROM jobs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jobTrendsLabels[] = $row['month'];
        $jobTrends[] = (int)$row['count'];
    }
    
    // If no jobs exist, show a placeholder
    if (empty($jobTrendsLabels)) {
        $jobTrendsLabels = ['No Jobs Posted'];
        $jobTrends = [0];
    }

    // Fetch Application Status Distribution
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $appStatusLabels[] = ucfirst($row['status']);
        $appStatusCounts[] = (int)$row['count'];
    }
    
    // If no applications exist, show a placeholder
    if (empty($appStatusLabels)) {
        $appStatusLabels = ['No Applications'];
        $appStatusCounts = [1];
    }

} catch (PDOException $e) {
    // Set default values if queries fail
    $totalUsers = 0;
    $activeJobs = 0;
    $totalApplications = 0;
    $totalRevenue = 0;
    
    // Set default chart data
    $jobTrendsLabels = ['No Data'];
    $jobTrends = [0];
    $appStatusLabels = ['No Data'];
    $appStatusCounts = [0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-green: #064239;
            --primary-green-light: #0a5a4a;
            --primary-green-dark: #04352c;
            --accent-green: #0d9488;
            --accent-green-light: #14b8a6;
            --white: #ffffff;
            --gray-light: #f8f9fa;
            --gray-border: #e9ecef;
            --text-dark: #212529;
            --text-medium: #495057;
            --text-light: #6c757d;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
        }
        
        .main-content {
            margin-left: 300px;
            flex: 1;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 1s ease-out;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 30px;
            border-radius: 25px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
        }
        
        .header h1 {
            color: var(--primary-green);
            font-size: 3rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: var(--text-medium);
            font-size: 1.2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 66, 57, 0.25);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-title {
            font-size: 1.1rem;
            color: var(--text-medium);
            font-weight: 600;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-green);
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-change.positive {
            color: var(--success);
        }
        
        .stat-change.negative {
            color: var(--danger);
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .chart-title {
            font-size: 1.5rem;
            color: var(--primary-green);
            font-weight: 700;
        }
        
        .chart-filters {
            display: flex;
            gap: 10px;
        }
        
        .chart-filter {
            padding: 8px 16px;
            border: 2px solid var(--gray-border);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-medium);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .chart-filter:hover, .chart-filter.active {
            background: var(--accent-green);
            color: var(--white);
            border-color: var(--accent-green);
            transform: translateY(-2px);
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .activity-title {
            font-size: 1.5rem;
            color: var(--primary-green);
            font-weight: 700;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--white);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--primary-green);
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.2);
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .chart-filters { flex-wrap: wrap; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening with your job portal</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Users</div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +12% from last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Active Jobs</div>
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($activeJobs); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +8% from last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Applications</div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalApplications); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +15% from last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-value">UGX <?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +20% from last month
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Job Posting Trends</h3>
                        <div class="chart-filters">
                            <button class="chart-filter active" onclick="updateChart('monthly')">Monthly</button>
                            <button class="chart-filter" onclick="updateChart('quarterly')">Quarterly</button>
                            <button class="chart-filter" onclick="updateChart('yearly')">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="jobTrendsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Application Status Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="applicationStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="activity-header">
                    <h3 class="activity-title">Recent Activity</h3>
                    <button class="chart-filter" onclick="viewAllActivity()">View All</button>
                        </div>
                <?php
                // Fetch 5 most recent activities from users, jobs, applications, companies
                $activity_sql = "
                    (SELECT 'user' AS type, id, CAST(full_name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS main, NULL AS extra, created_at AS time, NULL AS status FROM users WHERE status = 'active')
                    UNION ALL
                    (SELECT 'job' AS type, id, CAST(title AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS main, CAST(company AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS extra, created_at AS time, NULL AS status FROM jobs WHERE status = 'active')
                    UNION ALL
                    (SELECT 'application' AS type, a.id, CAST(u.full_name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS main, CAST(j.title AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS extra, a.application_date AS time, CAST(a.status AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS status FROM applications a JOIN users u ON a.user_id = u.id JOIN jobs j ON a.job_id = j.id)
                    UNION ALL
                    (SELECT 'company' AS type, id, CAST(name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS main, NULL AS extra, created_at AS time, CAST(status AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS status FROM companies WHERE status = 'active')
                    UNION ALL
                    (SELECT 'application_approved' AS type, a.id, CAST(u.full_name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS main, CAST(j.title AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS extra, a.updated_at AS time, CAST(a.status AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) AS status FROM applications a JOIN users u ON a.user_id = u.id JOIN jobs j ON a.job_id = j.id WHERE a.status = 'hired')
                    ORDER BY time DESC LIMIT 5
                ";
                $stmt = $pdo->prepare($activity_sql);
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                function timeAgo($datetime) {
                    $timestamp = strtotime($datetime);
                    $diff = time() - $timestamp;
                    if ($diff < 60) return $diff . ' seconds ago';
                    $mins = floor($diff / 60);
                    if ($mins < 60) return $mins . ' minutes ago';
                    $hours = floor($mins / 60);
                    if ($hours < 24) return $hours . ' hours ago';
                    $days = floor($hours / 24);
                    if ($days < 7) return $days . ' days ago';
                    return date('M d, Y', $timestamp);
                }
                $icons = [
                    'user' => 'fa-user-plus',
                    'job' => 'fa-briefcase',
                    'application' => 'fa-file-alt',
                    'company' => 'fa-building',
                    'application_approved' => 'fa-check-circle',
                ];
                $labels = [
                    'user' => 'New user registered: ',
                    'job' => 'New job posted: ',
                    'application' => 'Application submitted: ',
                    'company' => 'New company registered: ',
                    'application_approved' => 'Job application approved: ',
                ];
                foreach ($activities as $activity):
                    $icon = $icons[$activity['type']] ?? 'fa-info-circle';
                    $label = $labels[$activity['type']] ?? '';
                    $main = htmlspecialchars($activity['main']);
                    $extra = $activity['extra'] ? htmlspecialchars($activity['extra']) : '';
                    $time = timeAgo($activity['time']);
                ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">
                            <?php echo $label . $main; ?><?php if ($extra) echo ($activity['type']==='job' ? ' at ' : ($activity['type']==='application' || $activity['type']==='application_approved' ? ' for ' : '')) . $extra; ?>
                        </div>
                        <div class="activity-time"><?php echo $time; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">No recent activity</div>
                        <div class="activity-time">—</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Declare chart variables globally
        let jobTrendsChart;
        let applicationStatusChart;
        
        // Wait for DOM to be fully loaded before initializing charts
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            const jobTrendsLabels = <?php echo json_encode($jobTrendsLabels); ?>;
            const jobTrendsData = <?php echo json_encode($jobTrends); ?>;
            const appStatusLabels = <?php echo json_encode($appStatusLabels); ?>;
            const appStatusData = <?php echo json_encode($appStatusCounts); ?>;

            jobTrendsChart = new Chart(document.getElementById('jobTrendsChart'), {
                type: 'line',
                data: {
                    labels: jobTrendsLabels,
                    datasets: [{
                        label: 'Job Postings',
                        data: jobTrendsData,
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointBackgroundColor: '#0d9488',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#0d9488',
                            borderWidth: 1,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.1)' },
                            ticks: {
                                stepSize: 1,
                                color: '#6c757d',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: { color: 'rgba(0, 0, 0, 0.1)' },
                            ticks: {
                                color: '#6c757d',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: '#0d9488'
                        }
                    }
                }
            });

            applicationStatusChart = new Chart(document.getElementById('applicationStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: appStatusLabels,
                    datasets: [{
                        data: appStatusData,
                        backgroundColor: appStatusLabels.length === 1 && appStatusLabels[0] === 'No Applications' 
                            ? ['#6c757d'] 
                            : [
                                '#ffc107', // Pending
                                '#0dcaf0', // Reviewed
                                '#198754', // Shortlisted
                                '#20c997', // Hired
                                '#dc3545'  // Rejected
                            ].slice(0, appStatusLabels.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });

        function updateChart(period) {
            // Check if chart is initialized
            if (!jobTrendsChart) {
                console.error('Chart not initialized yet');
                return;
            }
            
            // Update chart filters
            document.querySelectorAll('.chart-filter').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show loading state
            const chart = jobTrendsChart;
            chart.data.labels = ['Loading...'];
            chart.data.datasets[0].data = [0];
            chart.update();
            
            // Fetch new data based on period
            fetch(`get_chart_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.data;
                    chart.update();
                })
                .catch(error => {
                    console.error('Error fetching chart data:', error);
                    // Fallback to current data
                    const jobTrendsLabels = <?php echo json_encode($jobTrendsLabels); ?>;
                    const jobTrendsData = <?php echo json_encode($jobTrends); ?>;
                    chart.data.labels = jobTrendsLabels;
                    chart.data.datasets[0].data = jobTrendsData;
                    chart.update();
                });
        }

        function viewAllActivity() {
            // Navigate to activity log page
            window.location.href = 'activity_log.php';
        }
    </script>
</body>
</html>