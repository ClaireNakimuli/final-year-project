<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Fetch key metrics with error handling
try {
    // Check if tables exist before running queries
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    $companies_check = $conn->query("SHOW TABLES LIKE 'companies'");
    $jobs_check = $conn->query("SHOW TABLES LIKE 'jobs'");
    $applications_check = $conn->query("SHOW TABLES LIKE 'applications'");
    $payments_check = $conn->query("SHOW TABLES LIKE 'payments'");
    
    $stats = [
        'total_users' => $users_check->rowCount() > 0 ? $conn->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0 : 0,
        'total_companies' => $companies_check->rowCount() > 0 ? $conn->query("SELECT COUNT(*) as count FROM companies")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0 : 0,
        'total_jobs' => $jobs_check->rowCount() > 0 ? $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0 : 0,
        'total_applications' => $applications_check->rowCount() > 0 ? $conn->query("SELECT COUNT(*) as count FROM applications")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0 : 0,
        'active_jobs' => $jobs_check->rowCount() > 0 ? $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0 : 0,
        'total_revenue' => $payments_check->rowCount() > 0 ? $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0 : 0
    ];
} catch (PDOException $e) {
    $stats = [
        'total_users' => 0,
        'total_companies' => 0,
        'total_jobs' => 0,
        'total_applications' => 0,
        'active_jobs' => 0,
        'total_revenue' => 0
    ];
}

// Fetch monthly job posting trends
try {
    if ($jobs_check->rowCount() > 0) {
        $monthly_jobs_result = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM jobs 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        $monthly_jobs = [];
        if ($monthly_jobs_result) {
            while ($row = $monthly_jobs_result->fetch(PDO::FETCH_ASSOC)) {
                $monthly_jobs[] = $row;
            }
        }
    } else {
        $monthly_jobs = [];
    }
} catch (PDOException $e) {
    $monthly_jobs = [];
}

// Fetch application status distribution
try {
    if ($applications_check->rowCount() > 0) {
        $application_status_result = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM applications 
            GROUP BY status
        ");
        $application_status = [];
        if ($application_status_result) {
            while ($row = $application_status_result->fetch(PDO::FETCH_ASSOC)) {
                $application_status[] = $row;
            }
        }
    } else {
        $application_status = [];
    }
} catch (PDOException $e) {
    $application_status = [];
}

// Fetch top companies by job postings
try {
    if ($companies_check->rowCount() > 0 && $jobs_check->rowCount() > 0) {
        $top_companies_result = $conn->query("
            SELECT c.name, COUNT(j.id) as job_count 
            FROM companies c 
            LEFT JOIN jobs j ON c.id = j.company_id 
            GROUP BY c.id 
            ORDER BY job_count DESC 
            LIMIT 5
        ");
        $top_companies = [];
        if ($top_companies_result) {
            while ($row = $top_companies_result->fetch(PDO::FETCH_ASSOC)) {
                $top_companies[] = $row;
            }
        }
    } else {
        $top_companies = [];
    }
} catch (PDOException $e) {
    $top_companies = [];
}

// Fetch user registration trends
try {
    if ($users_check->rowCount() > 0) {
        $user_registrations_result = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        $user_registrations = [];
        if ($user_registrations_result) {
            while ($row = $user_registrations_result->fetch(PDO::FETCH_ASSOC)) {
                $user_registrations[] = $row;
            }
        }
    } else {
        $user_registrations = [];
    }
} catch (PDOException $e) {
    $user_registrations = [];
}

// Fetch revenue trends
try {
    if ($payments_check->rowCount() > 0) {
        $revenue_trends_result = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total 
            FROM payments 
            WHERE status = 'completed' AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        $revenue_trends = [];
        if ($revenue_trends_result) {
            while ($row = $revenue_trends_result->fetch(PDO::FETCH_ASSOC)) {
                $revenue_trends[] = $row;
            }
        }
    } else {
        $revenue_trends = [];
    }
} catch (PDOException $e) {
    $revenue_trends = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Reports Management</title>
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
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .report-card {
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
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .card-icon {
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
        
        .card-title {
            color: var(--primary-green);
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .report-filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-green-light) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 135, 84, 0.4);
        }
        
        .report-content {
            margin-top: 20px;
        }
        
        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(6, 66, 57, 0.1);
        }
        
        .summary-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .summary-label {
            color: var(--text-medium);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .chart-container {
            margin-top: 20px;
            height: 300px;
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
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .reports-grid { grid-template-columns: 1fr; }
            .filters-grid { grid-template-columns: 1fr; }
            .report-summary { grid-template-columns: repeat(2, 1fr); }
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
                <h1><i class="fas fa-chart-bar"></i> Reports Management</h1>
                <p>Generate and view comprehensive system reports</p>
            </div>

            <!-- Report Filters -->
            <div class="report-filters">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="report_type"><i class="fas fa-file-alt"></i> Report Type</label>
                        <select class="form-control" id="report_type" onchange="updateReport()">
                            <option value="user_activity">User Activity Report</option>
                            <option value="job_performance">Job Performance Report</option>
                            <option value="application_analytics">Application Analytics</option>
                            <option value="revenue_report">Revenue Report</option>
                            <option value="system_usage">System Usage Report</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_range"><i class="fas fa-calendar"></i> Date Range</label>
                        <select class="form-control" id="date_range" onchange="updateReport()">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 3 Months</option>
                            <option value="365">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="format"><i class="fas fa-download"></i> Export Format</label>
                        <select class="form-control" id="format">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button class="btn btn-primary" onclick="generateReport()">
                            <i class="fas fa-sync-alt"></i> Generate Report
                        </button>
                        <button class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <div class="reports-grid">
                <!-- User Activity Report -->
                <div class="report-card" id="user_activity_report">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-title">User Activity Report</div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-summary">
                            <div class="summary-item">
                                <div class="summary-number">1,234</div>
                                <div class="summary-label">Active Users</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">567</div>
                                <div class="summary-label">New Registrations</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">89%</div>
                                <div class="summary-label">Engagement Rate</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">45</div>
                                <div class="summary-label">Avg. Session Time</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Job Performance Report -->
                <div class="report-card" id="job_performance_report" style="display: none;">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="card-title">Job Performance Report</div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-summary">
                            <div class="summary-item">
                                <div class="summary-number">456</div>
                                <div class="summary-label">Active Jobs</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">1,234</div>
                                <div class="summary-label">Total Applications</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">2.7</div>
                                <div class="summary-label">Avg. Applications/Job</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">78%</div>
                                <div class="summary-label">Fill Rate</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="jobPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Application Analytics -->
                <div class="report-card" id="application_analytics_report" style="display: none;">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="card-title">Application Analytics</div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-summary">
                            <div class="summary-item">
                                <div class="summary-number">2,345</div>
                                <div class="summary-label">Total Applications</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">156</div>
                                <div class="summary-label">Pending Review</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">89</div>
                                <div class="summary-label">Shortlisted</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">23</div>
                                <div class="summary-label">Hired</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="applicationAnalyticsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue Report -->
                <div class="report-card" id="revenue_report" style="display: none;">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="card-title">Revenue Report</div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-summary">
                            <div class="summary-item">
                                <div class="summary-number">UGX 2.5M</div>
                                <div class="summary-label">Total Revenue</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">UGX 125K</div>
                                <div class="summary-label">Monthly Average</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">15%</div>
                                <div class="summary-label">Growth Rate</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">234</div>
                                <div class="summary-label">Transactions</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System Usage Report -->
                <div class="report-card" id="system_usage_report" style="display: none;">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="card-title">System Usage Report</div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-summary">
                            <div class="summary-item">
                                <div class="summary-number">99.9%</div>
                                <div class="summary-label">Uptime</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">2.3s</div>
                                <div class="summary-label">Avg. Response Time</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">45GB</div>
                                <div class="summary-label">Storage Used</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-number">1.2K</div>
                                <div class="summary-label">Daily Requests</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="systemUsageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function updateReport() {
            const reportType = document.getElementById('report_type').value;
            const reports = document.querySelectorAll('.report-card');
            
            reports.forEach(report => {
                report.style.display = 'none';
            });
            
            document.getElementById(reportType + '_report').style.display = 'block';
            generateCharts();
        }
        
        function generateReport() {
            const reportType = document.getElementById('report_type').value;
            const dateRange = document.getElementById('date_range').value;
            
            alert(`Generating ${reportType} report for the last ${dateRange} days...`);
            // Add actual report generation logic here
        }
        
        function exportReport() {
            const reportType = document.getElementById('report_type').value;
            const format = document.getElementById('format').value;
            
            alert(`Exporting ${reportType} report as ${format.toUpperCase()}...`);
            // Add actual export logic here
        }
        
        function generateCharts() {
            // User Activity Chart
            const userCtx = document.getElementById('userActivityChart');
            if (userCtx) {
                const userLabels = <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $user_registrations)); ?>;
                const userData = <?php echo json_encode(array_map(function($item) { return $item['count']; }, $user_registrations)); ?>;
                
                new Chart(userCtx, {
                    type: 'line',
                    data: {
                        labels: userLabels,
                        datasets: [{
                            label: 'User Registrations',
                            data: userData,
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
            
            // Job Performance Chart
            const jobCtx = document.getElementById('jobPerformanceChart');
            if (jobCtx) {
                const jobLabels = <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $monthly_jobs)); ?>;
                const jobData = <?php echo json_encode(array_map(function($item) { return $item['count']; }, $monthly_jobs)); ?>;
                
                new Chart(jobCtx, {
                    type: 'bar',
                    data: {
                        labels: jobLabels,
                        datasets: [{
                            label: 'Job Postings',
                            data: jobData,
                            backgroundColor: '#0d9488'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
            
            // Application Analytics Chart
            const appCtx = document.getElementById('applicationAnalyticsChart');
            if (appCtx) {
                const appLabels = <?php echo json_encode(array_map(function($item) { return ucfirst($item['status']); }, $application_status)); ?>;
                const appData = <?php echo json_encode(array_map(function($item) { return $item['count']; }, $application_status)); ?>;
                
                new Chart(appCtx, {
                    type: 'doughnut',
                    data: {
                        labels: appLabels,
                        datasets: [{
                            data: appData,
                            backgroundColor: [
                                '#ffc107',
                                '#0dcaf0',
                                '#198754',
                                '#20c997',
                                '#dc3545'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Revenue Chart
            const revCtx = document.getElementById('revenueChart');
            if (revCtx) {
                const revLabels = <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $revenue_trends)); ?>;
                const revData = <?php echo json_encode(array_map(function($item) { return $item['total']; }, $revenue_trends)); ?>;
                
                new Chart(revCtx, {
                    type: 'line',
                    data: {
                        labels: revLabels,
                        datasets: [{
                            label: 'Revenue',
                            data: revData,
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
            
            // System Usage Chart
            const sysCtx = document.getElementById('systemUsageChart');
            if (sysCtx) {
                new Chart(sysCtx, {
                    type: 'bar',
                    data: {
                        labels: ['CPU', 'Memory', 'Storage', 'Network'],
                        datasets: [{
                            label: 'Usage %',
                            data: [65, 45, 78, 30],
                            backgroundColor: '#0d9488'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        }
        
        // Initialize charts on page load
        document.addEventListener('DOMContentLoaded', function() {
            generateCharts();
        });
    </script>
</body>
</html> 