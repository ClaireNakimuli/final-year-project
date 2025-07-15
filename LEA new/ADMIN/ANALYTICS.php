<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Fetch key metrics
$metrics = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'],
    'total_jobs' => $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch(PDO::FETCH_ASSOC)['count'],
    'total_applications' => $conn->query("SELECT COUNT(*) as count FROM applications")->fetch(PDO::FETCH_ASSOC)['count'],
    'total_companies' => $conn->query("SELECT COUNT(*) as count FROM companies")->fetch(PDO::FETCH_ASSOC)['count']
];

// Fetch application status distribution
$status_query = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_result = $conn->query($status_query);
$status_distribution = [];
while ($row = $status_result->fetch(PDO::FETCH_ASSOC)) {
    $status_distribution[] = $row;
}

// Fetch recent applications
$recent_apps_query = "SELECT a.*, u.full_name, j.title as job_title 
                     FROM applications a 
                     JOIN users u ON a.user_id = u.id 
                     JOIN jobs j ON a.job_id = j.id 
                     ORDER BY a.application_date DESC LIMIT 5";
$recent_applications = $conn->query($recent_apps_query);

// Fetch job posting trends (last 6 months)
$trends_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM jobs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY month 
                ORDER BY month";
$trends_result = $conn->query($trends_query);
$job_trends = [];
while ($row = $trends_result->fetch(PDO::FETCH_ASSOC)) {
    $job_trends[] = $row;
}

// Fetch top companies by job postings
$top_companies_query = "SELECT company, COUNT(*) as job_count 
                       FROM jobs 
                       GROUP BY company 
                       ORDER BY job_count DESC 
                       LIMIT 5";
$top_companies = $conn->query($top_companies_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Analytics Dashboard</title>
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
            --sidebar-width: 280px;
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
            margin-left: var(--sidebar-width);
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
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 66, 57, 0.25);
        }
        
        .metric-icon {
            font-size: 2.5rem;
            color: var(--accent-green);
            margin-bottom: 15px;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 10px;
        }
        
        .metric-label {
            color: var(--text-medium);
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
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
        }
        
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .chart-card h3 {
            color: var(--primary-green);
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .recent-activity:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .recent-activity h3 {
            color: var(--primary-green);
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--gray-border);
            transition: all 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: rgba(6, 66, 57, 0.05);
            border-radius: 10px;
            padding-left: 15px;
            padding-right: 15px;
            margin: 0 -15px;
        }
        
        .activity-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.2rem;
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .activity-meta {
            color: var(--text-medium);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .status-reviewed { background: rgba(13, 202, 240, 0.1); color: var(--info); }
        .status-shortlisted { background: rgba(25, 135, 84, 0.1); color: var(--success); }
        .status-rejected { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .status-hired { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        
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
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .charts-grid { grid-template-columns: 1fr; }
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 480px) {
            .metrics-grid { grid-template-columns: 1fr; }
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
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                <p>Monitor your job portal's performance with real-time insights</p>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-users"></i></div>
                    <div class="metric-value"><?php echo number_format($metrics['total_users']); ?></div>
                    <div class="metric-label">Total Users</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="metric-value"><?php echo number_format($metrics['total_jobs']); ?></div>
                    <div class="metric-label">Active Jobs</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="metric-value"><?php echo number_format($metrics['total_applications']); ?></div>
                    <div class="metric-label">Total Applications</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-building"></i></div>
                    <div class="metric-value"><?php echo number_format($metrics['total_companies']); ?></div>
                    <div class="metric-label">Registered Companies</div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fas fa-pie-chart"></i> Application Status Distribution</h3>
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-area"></i> Job Posting Trends</h3>
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <div class="recent-activity">
                <h3><i class="fas fa-clock"></i> Recent Applications</h3>
                <ul class="activity-list">
                    <?php if ($recent_applications && $recent_applications->rowCount() > 0): ?>
                        <?php while ($app = $recent_applications->fetch(PDO::FETCH_ASSOC)): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title"><?php echo htmlspecialchars($app['full_name']); ?> applied for <?php echo htmlspecialchars($app['job_title']); ?></div>
                                    <div class="activity-meta">
                                        <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">No recent applications</div>
                                <div class="activity-meta">Start receiving applications by posting jobs</div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    foreach ($status_distribution as $status):
                        echo "'" . ucfirst($status['status']) . "',";
                    endforeach;
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        foreach ($status_distribution as $status):
                            echo $status['count'] . ",";
                        endforeach;
                        ?>
                    ],
                    backgroundColor: [
                        '#ffc107', // pending
                        '#0dcaf0', // reviewed
                        '#198754', // shortlisted
                        '#dc3545', // rejected
                        '#20c997'  // hired
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Job Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    foreach ($job_trends as $trend):
                        echo "'" . date('M Y', strtotime($trend['month'] . '-01')) . "',";
                    endforeach;
                    ?>
                ],
                datasets: [{
                    label: 'Job Postings',
                    data: [
                        <?php 
                        foreach ($job_trends as $trend):
                            echo $trend['count'] . ",";
                        endforeach;
                        ?>
                    ],
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0d9488',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#495057'
                        },
                        grid: {
                            color: 'rgba(73, 80, 87, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#495057'
                        },
                        grid: {
                            color: 'rgba(73, 80, 87, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 