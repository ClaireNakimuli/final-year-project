<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

try {
    // Get user's last login
    $sql = "SELECT updated_at FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_login = $user_data['updated_at'];

    // Get total available jobs
    $jobs_sql = "SELECT COUNT(*) as total_jobs FROM jobs WHERE status = 'active'";
    $jobs_result = $conn->query($jobs_sql);
    $jobs_data = $jobs_result->fetch(PDO::FETCH_ASSOC);
    $total_jobs = $jobs_data['total_jobs'];

    // Get user's total applications
    $applications_sql = "SELECT COUNT(*) as total_applications FROM applications WHERE user_id = :user_id";
    $applications_stmt = $conn->prepare($applications_sql);
    $applications_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $applications_stmt->execute();
    $applications_data = $applications_stmt->fetch(PDO::FETCH_ASSOC);
    $total_applications = $applications_data['total_applications'];

    // Get user's active applications (pending or reviewed)
    $active_applications_sql = "SELECT COUNT(*) as active_applications FROM applications WHERE user_id = :user_id AND status IN ('pending', 'reviewed')";
    $active_applications_stmt = $conn->prepare($active_applications_sql);
    $active_applications_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $active_applications_stmt->execute();
    $active_applications_data = $active_applications_stmt->fetch(PDO::FETCH_ASSOC);
    $active_applications = $active_applications_data['active_applications'];

    // Get saved jobs count
    $saved_jobs_sql = "SELECT COUNT(*) as saved_jobs FROM favorites WHERE user_id = :user_id";
    $saved_jobs_stmt = $conn->prepare($saved_jobs_sql);
    $saved_jobs_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $saved_jobs_stmt->execute();
    $saved_jobs_data = $saved_jobs_stmt->fetch(PDO::FETCH_ASSOC);
    $saved_jobs = $saved_jobs_data['saved_jobs'];

    // Get application status distribution
    $status_sql = "SELECT status, COUNT(*) as count FROM applications WHERE user_id = :user_id GROUP BY status";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $status_stmt->execute();
    $status_result = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    $status_data = [];
    $status_labels = [];
    $status_counts = [];
    foreach ($status_result as $row) {
        $status_data[] = $row;
        $status_labels[] = ucfirst($row['status']);
        $status_counts[] = (int)$row['count'];
    }

    // Get monthly application trends (last 6 months)
    $trends_sql = "SELECT DATE_FORMAT(application_date, '%Y-%m') as month, COUNT(*) as count 
                   FROM applications 
                   WHERE user_id = :user_id AND application_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                   GROUP BY month 
                   ORDER BY month";
    $trends_stmt = $conn->prepare($trends_sql);
    $trends_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $trends_stmt->execute();
    $trends_result = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);
    $trends_labels = [];
    $trends_data = [];
    foreach ($trends_result as $row) {
        $trends_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $trends_data[] = (int)$row['count'];
    }

    // Get recent applications
    $recent_apps_sql = "SELECT a.*, j.title, j.company, j.type, j.location 
                        FROM applications a 
                        JOIN jobs j ON a.job_id = j.id 
                        WHERE a.user_id = :user_id 
                        ORDER BY a.application_date DESC 
                        LIMIT 5";
    $recent_apps_stmt = $conn->prepare($recent_apps_sql);
    $recent_apps_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $recent_apps_stmt->execute();
    $recent_applications = $recent_apps_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent jobs for display
    $recent_jobs_sql = "SELECT * FROM jobs WHERE status = 'active' ORDER BY created_at DESC LIMIT 6";
    $recent_jobs_result = $conn->query($recent_jobs_sql);
    $recent_jobs = $recent_jobs_result->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
// Get user notifications (job alerts)
// Get job alerts posted by admin for all users
// Get latest notifications (assumed to be for users)
$notifications_sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->execute();
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LEA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --accent-color: #2196F3;
            --text-dark: #333;
            --text-light: #666;
    --white: #fff;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
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

        .header {
            background-color: var(--white);
    padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .welcome-message {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .welcome-message span {
            color: var(--primary-color);
            font-weight: 700;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white);
    padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 1.8em;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.2em;
            color: var(--text-dark);
            font-weight: 600;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .recent-applications {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .recent-applications h3 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .application-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .application-item:last-child {
            border-bottom: none;
        }

        .application-info h4 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .application-info p {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .application-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #cce5ff; color: #004085; }
        .status-shortlisted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-hired { background: #d1e7dd; color: #0f5132; }

.jobs-container {
    display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
}

.job-card {
    background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

.job-card:hover {
            transform: translateY(-5px);
        }

.job-header {
    margin-bottom: 15px;
}

.job-title {
            font-size: 1.2em;
            color: var(--text-dark);
    margin-bottom: 5px;
}

.company-name {
            color: var(--text-light);
            font-size: 0.9em;
        }

.job-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
}

        .detail-item {
    display: flex;
    align-items: center;
            gap: 5px;
            color: var(--text-light);
            font-size: 0.9em;
}

        .detail-item i {
            color: var(--primary-color);
}

.job-description {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

.job-tags {
    display: flex;
    flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
}

.tag {
            background: var(--light-gray);
            color: var(--text-light);
            padding: 3px 8px;
    border-radius: 15px;
            font-size: 0.8em;
}

.job-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
            margin-top: 15px;
    padding-top: 15px;
            border-top: 1px solid var(--border-color);
}

.salary {
            color: var(--primary-color);
            font-weight: 600;
        }

.apply-btn {
            background: var(--primary-color);
    color: var(--white);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }

.apply-btn:hover {
            background: var(--secondary-color);
        }

.no-jobs {
    text-align: center;
            padding: 40px;
    background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .no-jobs i {
            font-size: 3em;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .no-jobs h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .no-jobs p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .browse-jobs-btn {
            display: inline-block;
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .browse-jobs-btn:hover {
            background: var(--secondary-color);
        }

        @media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 15px;
    }

            .stats-container {
        grid-template-columns: 1fr;
    }

    .charts-grid {
        grid-template-columns: 1fr;
    }

    .jobs-container {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1 class="welcome-message">Welcome, <span><?php echo htmlspecialchars($full_name); ?></span></h1>
            <p>Last login: <?php echo date('M j, Y g:i A', strtotime($last_login)); ?></p>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-briefcase"></i>
                <h3><?php echo $total_jobs; ?></h3>
                <p>Available Jobs</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <h3><?php echo $total_applications; ?></h3>
                <p>Your Applications</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $active_applications; ?></h3>
                <p>Active Applications</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-heart"></i>
                <h3><?php echo $saved_jobs; ?></h3>
                <p>Saved Jobs</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Application Status Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Monthly Application Trends</h3>
                </div>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="recent-applications" id="job-alerts-container">
    <h3>Job Alerts</h3>
    <div id="notifications-container">
        <p style="text-align: center; color: var(--text-light);">Loading job alerts...</p>
    </div>
</div>


        <div class="jobs-container">
            <h3 style="grid-column: 1 / -1; margin-bottom: 20px; color: var(--text-dark);">Latest Job Postings</h3>
            <?php if (count($recent_jobs) > 0): ?>
                <?php foreach ($recent_jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h4 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                            <p class="company-name"><?php echo htmlspecialchars($job['company']); ?></p>
                        </div>
                        <div class="job-details">
                            <div class="detail-item"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['type']); ?></div>
                            <div class="detail-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></div>
                        </div>
                        <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                        <div class="job-footer">
                            <span class="salary">
    UGX 
    <?php 
        if (strpos($job['salary'], '-') !== false) {
            // Salary is a range, print as is
            echo htmlspecialchars($job['salary']);
        } else {
            // Salary is a single number, format it
            echo number_format((float)$job['salary']);
        }
    ?>
</span>

                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="apply-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-jobs">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>No jobs available right now</h3>
                    <p>Please check back later or adjust your search criteria.</p>
                    <a href="jobs.php" class="browse-jobs-btn">Browse Jobs</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="recent-applications">
            <h3>Job Alerts</h3>
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $note): ?>
                    <div class="application-item">
                        <div class="application-info">
                            <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                            <p><?php echo htmlspecialchars($note['message']); ?> • 
                                <small><?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?></small>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-light);">No new job alerts at the moment.</p>
            <?php endif; ?>
        </div>

        <div id="notifications-container">
            <!-- Notifications will appear here -->
        </div>

    </div>

    <script>
        function toggleSidebar() {
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
        }

        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Application Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($status_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($status_counts); ?>,
                        backgroundColor: [
                            '#ffc107', // Pending
                            '#0dcaf0', // Reviewed
                            '#198754', // Shortlisted
                            '#dc3545', // Rejected
                            '#20c997'  // Hired
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Monthly Application Trends Chart
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trends_labels); ?>,
                    datasets: [{
                        label: 'Applications',
                        data: <?php echo json_encode($trends_data); ?>,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4CAF50',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
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
        });

        function fetchNotifications() {
  fetch('get_notifications.php')
    .then(res => {
      // console.log('Fetch response status:', res.status);
      return res.json();
    })
    .then(data => {
      const container = document.getElementById('notifications-container');
      container.innerHTML = '';  // Clear existing notifications

      if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align:center; color: var(--text-light);">No new job alerts at the moment.</p>';
        return;
      }

      data.forEach(notif => {
        // Create notification item similar to your design
        const notifDiv = document.createElement('div');
        notifDiv.classList.add('application-item');

        const infoDiv = document.createElement('div');
        infoDiv.classList.add('application-info');

        const title = document.createElement('h4');
        title.textContent = notif.title || 'Notification';

        const message = document.createElement('p');
        message.innerHTML = `${notif.message} • <small>${new Date(notif.created_at).toLocaleString()}</small>`;

        infoDiv.appendChild(title);
        infoDiv.appendChild(message);
        notifDiv.appendChild(infoDiv);

        container.appendChild(notifDiv);
      });
    })
    .catch(err => {
      console.error('Error fetching notifications:', err);
      const container = document.getElementById('notifications-container');
      container.innerHTML = '<p style="text-align:center; color: var(--danger-color);">Failed to load job alerts.</p>';
    });
}


        // Fetch every 5 seconds
        setInterval(fetchNotifications, 5000);

        // Fetch immediately on page load
        fetchNotifications();
    </script>
</body>
</html>
