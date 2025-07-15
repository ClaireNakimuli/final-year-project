<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Create applications table if it doesn't exist
// First check if users and jobs tables exist
try {
    // Check if users table exists
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    $users_exists = $users_check->rowCount() > 0;
    
    // Check if jobs table exists
    $jobs_check = $conn->query("SHOW TABLES LIKE 'jobs'");
    $jobs_exists = $jobs_check->rowCount() > 0;
    
    // Create applications table with conditional foreign keys
    if ($users_exists && $jobs_exists) {
        $create_table = "CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            job_id INT NOT NULL,
            status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
            application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    } else {
        // Create table without foreign keys if referenced tables don't exist
        $create_table = "CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            job_id INT NOT NULL,
            status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
            application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
    
    if (!$conn->exec($create_table)) {
        // Don't die, just log the error and continue
        error_log("Warning: Could not create applications table");
    }
} catch (PDOException $e) {
    // Don't die, just log the error and continue
    error_log("Warning: Error creating applications table: " . $e->getMessage());
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    try {
        $stmt = $conn->prepare("UPDATE applications SET status = ?, notes = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $notes, $application_id])) {
            $success_message = "Application status updated successfully!";
        } else {
            $error_message = "Error updating status.";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Fetch all applications with user and job details
try {
    // Check if all required tables exist before running the complex query
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    $jobs_check = $conn->query("SHOW TABLES LIKE 'jobs'");
    $applications_check = $conn->query("SHOW TABLES LIKE 'applications'");
    
    if ($users_check->rowCount() > 0 && $jobs_check->rowCount() > 0 && $applications_check->rowCount() > 0) {
        $query = "SELECT a.*, 
                  u.username, u.email, u.full_name,
                  j.title as job_title, j.company as company_name
                  FROM applications a
                  JOIN users u ON a.user_id = u.id
                  JOIN jobs j ON a.job_id = j.id
                  ORDER BY a.application_date DESC";
    } else {
        // If tables don't exist, just select from applications table
        $query = "SELECT * FROM applications ORDER BY application_date DESC";
    }
    
    $applications_result = $conn->query($query);
    $applications_data = [];
    if ($applications_result) {
        while ($row = $applications_result->fetch(PDO::FETCH_ASSOC)) {
            $applications_data[] = $row;
        }
    }
} catch (PDOException $e) {
    $error_message = "Error fetching applications: " . $e->getMessage();
    $applications_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🦁 LAIR Applications Management</title>
    <style>
        :root {
            --primary-green: #064239;
            --primary-green-light: #0a5a4a;
            --primary-green-dark: #04352c;
            --white: #ffffff;
            --gray-light: #f8f9fa;
            --gray-border: #e9ecef;
            --text-dark: #212529;
            --text-medium: #495057;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, var(--primary-green) 0%, var(--primary-green) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
        }
        .main-content {
            margin-left: 300px;
            flex: 1;
            padding: 30px;
            min-height: 100vh;
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
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 25px;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        .application-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            position: relative;
            overflow: hidden;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
        }
        .application-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--primary-green), var(--primary-green-light));
        }
        .application-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 30px 60px rgba(6, 66, 57, 0.2);
        }
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .applicant-info {
            flex: 1;
        }
        .applicant-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        .job-title {
            color: var(--primary-green-light);
            font-size: 1.1rem;
            font-weight: 600;
            margin: 5px 0;
        }
        .company-name {
            color: var(--text-medium);
            font-size: 1rem;
            font-weight: 500;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1.5px solid var(--gray-border);
            background: var(--white);
            white-space: nowrap;
        }
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
            border-color: #ffeaa7;
        }
        .status-reviewed { 
            background: #cce5ff; 
            color: #004085; 
            border-color: #b3d9ff;
        }
        .status-shortlisted { 
            background: #d4edda; 
            color: #155724; 
            border-color: #c3e6cb;
        }
        .status-rejected { 
            background: #f8d7da; 
            color: #721c24; 
            border-color: #f5c6cb;
        }
        .status-hired { 
            background: #d1e7dd; 
            color: #0f5132; 
            border-color: #badbcc;
        }
        .application-details {
            margin: 20px 0;
        }
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: var(--text-medium);
            font-size: 0.95rem;
        }
        .detail-item span {
            margin-left: 10px;
            word-break: break-word;
        }
        .application-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1.5px solid var(--gray-border);
        }
        .action-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-medium);
        }
        .btn {
            padding: 12px 20px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1.5px solid var(--gray-border);
            background: var(--white);
            font-family: inherit;
        }
        .btn-primary {
            background: var(--primary-green);
            color: var(--white);
            border-color: var(--primary-green);
            min-width: 100px;
        }
        .btn-primary:hover {
            background: var(--primary-green-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 66, 57, 0.3);
        }
        .btn-outline {
            background: var(--white);
            color: var(--text-dark);
        }
        .btn-outline:hover {
            background: var(--gray-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        select.btn-outline {
            background: var(--white);
            color: var(--text-dark);
            min-width: 120px;
        }
        textarea.btn-outline {
            background: var(--white);
            color: var(--text-dark);
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
            min-width: 200px;
        }
        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            background: var(--white);
            border: 1.5px solid var(--gray-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-info {
            background: #cce5ff;
            color: #004085;
            border-color: #b3d9ff;
        }
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-green);
            border: 1.5px solid var(--primary-green-light);
            padding: 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--white);
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.3);
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
            .main-content { 
                margin-left: 0; 
                padding: 20px;
            }
            .mobile-menu-btn { display: block; }
            .applications-grid { 
                grid-template-columns: 1fr; 
                gap: 20px;
            }
            .header h1 { font-size: 2rem; }
            .action-form { 
                flex-direction: column; 
                align-items: stretch;
            }
            .application-header {
                flex-direction: column;
                gap: 15px;
            }
            .status-badge {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>📝 Applications Management</h1>
                <p>✨ Review and manage job applications</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="applications-grid">
                <?php if (count($applications_data) > 0): ?>
                    <?php foreach ($applications_data as $app): ?>
                        <div class="application-card">
                            <div class="application-header">
                                <div class="applicant-info">
                                    <div class="applicant-name">
                                        <?php echo isset($app['full_name']) ? htmlspecialchars($app['full_name']) : 'User ID: ' . htmlspecialchars($app['user_id']); ?>
                                    </div>
                                    <div class="job-title">
                                        <?php echo isset($app['job_title']) ? htmlspecialchars($app['job_title']) : 'Job ID: ' . htmlspecialchars($app['job_id']); ?>
                                    </div>
                                    <div class="company-name">
                                        <?php echo isset($app['company_name']) ? htmlspecialchars($app['company_name']) : 'Company info not available'; ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </div>
                            </div>
                            
                            <div class="application-details">
                                <div class="detail-item">📧 <span><?php echo isset($app['email']) ? htmlspecialchars($app['email']) : 'Email not available'; ?></span></div>
                                <div class="detail-item">📅 <span>Applied: <?php echo date('M d, Y', strtotime($app['application_date'])); ?></span></div>
                                <?php if ($app['notes']): ?>
                                    <div class="detail-item">📝 <span><?php echo nl2br(htmlspecialchars($app['notes'])); ?></span></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="application-actions">
                                <form method="POST" action="" class="action-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="status-<?php echo $app['id']; ?>">Status</label>
                                        <select name="status" id="status-<?php echo $app['id']; ?>" class="btn btn-outline">
                                            <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                            <option value="shortlisted" <?php echo $app['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                            <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="hired" <?php echo $app['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notes-<?php echo $app['id']; ?>">Notes</label>
                                        <textarea name="notes" id="notes-<?php echo $app['id']; ?>" placeholder="Add notes..." class="btn btn-outline"><?php echo htmlspecialchars($app['notes'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1; text-align: center;">
                        <i class="fas fa-info-circle"></i>
                        No applications found. Applications will appear here once users start applying for jobs.
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
    </script>
</body>
</html> 