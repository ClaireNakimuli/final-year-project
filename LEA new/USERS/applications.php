<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's applications with job details
$sql = "SELECT a.*, j.title, j.company, j.type, j.location, j.salary, 
        ud.file_name as cv_name, ud.uploaded_at as cv_uploaded_at,
        a.notes as cover_letter
        FROM applications a
        INNER JOIN jobs j ON a.job_id = j.id
        LEFT JOIN user_documents ud ON ud.user_id = a.user_id AND ud.document_type = 'cv'
        WHERE a.user_id = :user_id
        ORDER BY a.application_date DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - LEA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #02486b;
            --primary-blue-light: #036fa3;
            --primary-blue-dark: #013a56;
            --white: #fff;
            --gray-light: #f8fafc;
            --gray-lighter: #f1f5f9;
            --gray-border: #e2e8f0;
            --gray-medium: #64748b;
            --text-dark: #1e293b;
            --text-medium: #475569;
            --text-light: #64748b;
            --success-green: #10b981;
            --warning-yellow: #f59e0b;
            --info-blue: #3b82f6;
            --danger-red: #ef4444;
            --sidebar-width: 280px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            font-size: 14px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
            background: transparent;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-border);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-medium);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .applications-grid {
            display: grid;
            gap: 1.5rem;
        }

        .application-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .application-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-blue-light);
        }

        .application-card:hover::before {
            transform: scaleX(1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-lighter);
        }

        .job-info {
            flex: 1;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .company-name {
            color: var(--text-medium);
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-name i {
            color: var(--primary-blue-light);
        }

        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            min-width: 120px;
            text-align: center;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: currentColor;
            opacity: 0.1;
            border-radius: inherit;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning-yellow) 0%, #fbbf24 100%);
            color: #92400e;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .status-reviewed {
            background: linear-gradient(135deg, var(--info-blue) 0%, #60a5fa 100%);
            color: #1e40af;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .status-shortlisted {
            background: linear-gradient(135deg, var(--success-green) 0%, #34d399 100%);
            color: #065f46;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .status-rejected {
            background: linear-gradient(135deg, var(--danger-red) 0%, #f87171 100%);
            color: #991b1b;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .status-hired {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #064e3b;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: var(--gray-lighter);
            border-radius: 12px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            color: var(--white);
            font-size: 1rem;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1rem;
        }

        .cv-info {
            background: linear-gradient(135deg, var(--gray-lighter) 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-border);
            position: relative;
        }

        .cv-info::before {
            content: '📄';
            position: absolute;
            top: -10px;
            left: 20px;
            background: var(--white);
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .cv-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cv-date {
            font-size: 0.875rem;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cover-letter {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #fbbf24;
            position: relative;
        }

        .cover-letter::before {
            content: '✉️';
            position: absolute;
            top: -10px;
            left: 20px;
            background: var(--white);
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .cover-letter h3 {
            color: var(--primary-blue);
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cover-letter p {
            color: var(--text-dark);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .application-date {
            font-size: 0.875rem;
            color: var(--text-medium);
            text-align: right;
            padding: 1rem;
            background: var(--gray-lighter);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            font-weight: 500;
        }

        .no-applications {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 2px dashed var(--gray-border);
        }

        .no-applications-icon {
            font-size: 4rem;
            color: var(--gray-medium);
            margin-bottom: 1.5rem;
        }

        .no-applications h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .no-applications p {
            color: var(--text-medium);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            color: var(--white);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(0);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-border);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-medium);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .job-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .status-badge {
                align-self: flex-start;
            }
            
            .job-details {
                grid-template-columns: 1fr;
            }
            
            .application-card {
                padding: 1.5rem;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid var(--primary-blue);
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fade in animation */
        .application-card {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">My Applications</h1>
                <p class="page-subtitle">Track the status of your job applications and stay updated on your career progress</p>
            </div>

            <?php if (count($applications) > 0): ?>
                <!-- Stats Summary -->
                <div class="stats-summary">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($applications); ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($applications, function($app) { return in_array($app['status'], ['shortlisted', 'hired']); })); ?></div>
                        <div class="stat-label">Interviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($applications, function($app) { return $app['status'] === 'pending'; })); ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($applications, function($app) { return $app['status'] === 'hired'; })); ?></div>
                        <div class="stat-label">Hired</div>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="applications-grid">
                    <?php foreach ($applications as $index => $application): ?>
                        <div class="application-card" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="job-header">
                                <div class="job-info">
                                    <h2 class="job-title"><?php echo htmlspecialchars($application['title']); ?></h2>
                                    <div class="company-name">
                                        <i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($application['company']); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </div>
                            </div>

                            <div class="job-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Job Type</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($application['type']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Location</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($application['location']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Salary</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($application['salary']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($application['cv_name']): ?>
                                <div class="cv-info">
                                    <div class="cv-name">
                                        <i class="fas fa-file-alt"></i>
                                        <?php echo htmlspecialchars($application['cv_name']); ?>
                                    </div>
                                    <div class="cv-date">
                                        <i class="fas fa-calendar"></i>
                                        Uploaded: <?php echo date('M d, Y', strtotime($application['cv_uploaded_at'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($application['cover_letter'])): ?>
                                <div class="cover-letter">
                                    <h3>
                                        <i class="fas fa-envelope"></i>
                                        Cover Letter
                                    </h3>
                                    <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="application-date">
                                <i class="fas fa-calendar-alt"></i>
                                Applied on: <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-applications">
                    <div class="no-applications-icon">📋</div>
                    <h3>You haven't applied for any jobs yet</h3>
                    <p>Start your job search journey and apply for positions that match your skills and career goals. Your applications will appear here for easy tracking.</p>
                    <a href="dashboard.php" class="btn">
                        <i class="fas fa-search"></i>
                        Browse Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        }

        // Add smooth scrolling and enhanced interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to application cards
            const cards = document.querySelectorAll('.application-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add click effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>
</body>
</html> 