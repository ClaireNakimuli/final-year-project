<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/config.php';

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    $job_id = $_POST['job_id'];
    
    try {
        // Delete job applications first
        $delete_apps = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
        $delete_apps->execute([$job_id]);
        
        // Delete job
        $delete_job = $conn->prepare("DELETE FROM jobs WHERE id = ?");
        
        if ($delete_job->execute([$job_id])) {
            $success = "Job deleted successfully.";
        } else {
            $error = "Error deleting job.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting job: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_job':
                $title       = $_POST['title'];
                $company     = $_POST['company'];
                $type        = $_POST['type'];
                $location    = $_POST['location'];
                $experience  = $_POST['experience'];
                $salary      = $_POST['salary'];
                $description = $_POST['description'];
                $requirements= $_POST['requirements'];
                $tags        = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
                $tags        = array_map('trim', $tags);
                $tags        = array_filter($tags);
                $tags_string = !empty($tags) ? implode(',', $tags) : '';

                try {
                    // Ensure jobs table exists
                    $check_table = $conn->query("SHOW TABLES LIKE 'jobs'");
                    if ($check_table->rowCount() == 0) {
                        $create_table = "CREATE TABLE IF NOT EXISTS jobs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(255) NOT NULL,
                            company VARCHAR(255) NOT NULL,
                            type ENUM('Full-time','Part-time','Contract','Remote') NOT NULL,
                            location VARCHAR(255) NOT NULL,
                            experience VARCHAR(100) NOT NULL,
                            salary VARCHAR(100),
                            description TEXT NOT NULL,
                            requirements TEXT,
                            tags VARCHAR(255),
                            status ENUM('active','inactive','draft') DEFAULT 'active',
                            posted_by INT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                        $conn->exec($create_table);
                    }

                    // Fetch the admin user's ID from the admin table
                    if (isset($_SESSION['admin_username'])) {
                        $stmtUser = $conn->prepare("SELECT id FROM admin WHERE username = ?");
                        $stmtUser->execute([$_SESSION['admin_username']]);
                        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
                        if ($user) {
                            $posted_by = $user['id'];
                        } else {
                            throw new Exception("Logged-in admin not found in admin table.");
                        }
                    } else {
                        throw new Exception("No admin is logged in.");
                    }

                    // Insert new job
                    $stmt = $conn->prepare(
                        "INSERT INTO jobs (title, company, type, location, experience, salary, description, requirements, tags, posted_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt->execute([
                        $title,
                        $company,
                        $type,
                        $location,
                        $experience,
                        $salary,
                        $description,
                        $requirements,
                        $tags_string,
                        $posted_by
                    ])) {
                        $success = "Job posted successfully!";
                    } else {
                        $error = "Error posting job.";
                    }
                } catch (Exception $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all jobs
$jobs_query  = "SELECT * FROM jobs ORDER BY created_at DESC";
$jobs_result = $conn->query($jobs_query);
$jobs_data   = [];
if ($jobs_result) {
    while ($row = $jobs_result->fetch(PDO::FETCH_ASSOC)) {
        $jobs_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🦁 LAIR Job Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --accent-blue: #007bff;
            --accent-purple: #6f42c1;
            --accent-orange: #fd7e14;
            --accent-teal: #20c997;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-light) 50%, var(--primary-green-dark) 100%);
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
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 40px;
            border-radius: 30px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 20px 60px rgba(6, 66, 57, 0.15);
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple), var(--accent-orange), var(--accent-teal));
        }
        .header h1 {
            color: var(--primary-green);
            font-size: 3.5rem;
            margin-bottom: 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header p {
            color: var(--text-medium);
            font-size: 1.3rem;
            font-weight: 500;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 30px rgba(6, 66, 57, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 66, 57, 0.2);
        }
        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-green);
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        .stat-label {
            color: var(--text-medium);
            font-weight: 600;
        }
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 30px;
            border-radius: 25px;
            margin-bottom: 30px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 15px 40px rgba(6, 66, 57, 0.1);
        }
        .search-container {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 15px;
            font-size: 1rem;
            background: var(--white);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(6, 66, 57, 0.1);
        }
        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-medium);
            font-size: 1.2rem;
        }
        .filter-dropdown {
            padding: 15px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 15px;
            background: var(--white);
            color: var(--text-dark);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-dropdown:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        .add-job-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-light));
            color: var(--white);
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(6, 66, 57, 0.3);
        }
        .add-job-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.4);
            color: var(--white);
        }
        .job-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 40px;
            border-radius: 25px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 20px 60px rgba(6, 66, 57, 0.15);
            margin-bottom: 40px;
            display: none;
        }
        .job-form.active {
            display: block;
            animation: slideDown 0.5s ease-out;
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }
        .form-header h2 {
            color: var(--primary-green);
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .close-form {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-medium);
            cursor: pointer;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .close-form:hover {
            background: var(--gray-light);
            color: var(--text-dark);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-green);
            font-weight: 600;
            font-size: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text-dark);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(6, 66, 57, 0.1);
            background: var(--white);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-light));
            color: var(--white);
            box-shadow: 0 8px 25px rgba(6, 66, 57, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: var(--white);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(220, 53, 69, 0.4);
        }
        .btn-edit {
            background: linear-gradient(135deg, var(--accent-blue), #0056b3);
            color: var(--white);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.4);
        }
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 30px;
            animation: fadeInUp 1s ease-out 0.4s both;
        }
        .job-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(6, 66, 57, 0.1);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple), var(--accent-orange), var(--accent-teal));
        }
        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 60px rgba(6, 66, 57, 0.2);
        }
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .job-info {
            flex: 1;
        }
        .job-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .company-name {
            color: var(--text-medium);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .job-type {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: var(--white);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .job-details {
            margin: 20px 0;
        }
        .job-detail {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: var(--text-medium);
            font-size: 0.95rem;
        }
        .job-detail i {
            width: 20px;
            margin-right: 10px;
            color: var(--primary-green);
        }
        .job-description {
            color: var(--text-medium);
            line-height: 1.6;
            margin: 20px 0;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        .job-description.expanded {
            max-height: none;
        }
        .read-more {
            color: var(--primary-green);
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            display: inline-block;
        }
        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .tag {
            background: linear-gradient(135deg, var(--accent-teal), var(--accent-blue));
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .job-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }
        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 30px rgba(6, 66, 57, 0.1);
        }
        .alert-success {
            background: rgba(212, 237, 218, 0.95);
            color: #155724;
            border-color: rgba(195, 230, 203, 0.5);
        }
        .alert-danger {
            background: rgba(248, 215, 218, 0.95);
            color: #721c24;
            border-color: rgba(245, 198, 203, 0.5);
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
            box-shadow: 0 8px 25px rgba(6, 66, 57, 0.3);
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-menu-btn { display: block; }
            .jobs-grid { grid-template-columns: 1fr; }
            .search-container { flex-direction: column; }
            .search-box { min-width: 100%; }
            .header h1 { font-size: 2.5rem; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-briefcase"></i> Job Management</h1>
                <p>Post and manage job listings with ease</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-number"><?php echo count($jobs_data); ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-number"><?php echo count(array_unique(array_column($jobs_data, 'company'))); ?></div>
                    <div class="stat-label">Companies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?php echo count($jobs_data) * 3; ?></div>
                    <div class="stat-label">Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number"><?php echo count($jobs_data) > 0 ? round((count($jobs_data) / 10) * 100, 1) : 0; ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>

            <div class="search-section">
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search jobs by title, company, or location..." onkeyup="filterJobs()">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-dropdown" id="typeFilter" onchange="filterJobs()">
                        <option value="">All Types</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Remote">Remote</option>
                    </select>
                    <button class="add-job-btn" onclick="toggleJobForm()">
                        <i class="fas fa-plus"></i> Post New Job
                    </button>
                </div>
            </div>

            <div class="job-form" id="jobForm">
                <div class="form-header">
                    <h2><i class="fas fa-plus-circle"></i> Post New Job</h2>
                    <button class="close-form" onclick="toggleJobForm()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_job">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-briefcase"></i> Job Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="company"><i class="fas fa-building"></i> Company Name</label>
                            <input type="text" id="company" name="company" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="type"><i class="fas fa-clock"></i> Job Type</label>
                            <select id="type" name="type" class="form-control" required>
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contract">Contract</option>
                                <option value="Remote">Remote</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" id="location" name="location" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="experience"><i class="fas fa-user-tie"></i> Experience Required</label>
                            <input type="text" id="experience" name="experience" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="salary"><i class="fas fa-dollar-sign"></i> Salary Range</label>
                            <input type="text" id="salary" name="salary" class="form-control" placeholder="e.g., $50,000 - $70,000">
                        </div>

                        <div class="form-group full-width">
                            <label for="description"><i class="fas fa-align-left"></i> Job Description</label>
                            <textarea id="description" name="description" class="form-control" required placeholder="Describe the role, responsibilities, and what makes this position exciting..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="requirements"><i class="fas fa-list-check"></i> Requirements</label>
                            <textarea id="requirements" name="requirements" class="form-control" placeholder="List the skills, qualifications, and experience required..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="tags"><i class="fas fa-tags"></i> Skills/Tags (comma-separated)</label>
                            <input type="text" id="tags" name="tags" class="form-control" placeholder="e.g., React, TypeScript, Node.js, AWS, Docker">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Post Job
                    </button>
                </form>
            </div>

            <div class="jobs-grid" id="jobsGrid">
                <?php 
                if (!empty($jobs_data)): 
                    foreach ($jobs_data as $job): 
                ?>
                    <div class="job-card" data-title="<?php echo strtolower(htmlspecialchars($job['title'])); ?>" data-company="<?php echo strtolower(htmlspecialchars($job['company'])); ?>" data-location="<?php echo strtolower(htmlspecialchars($job['location'])); ?>" data-type="<?php echo strtolower(htmlspecialchars($job['type'])); ?>">
                        <div class="job-header">
                            <div class="job-info">
                                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="company-name">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($job['company']); ?>
                                </div>
                            </div>
                            <div class="job-type"><?php echo htmlspecialchars($job['type']); ?></div>
                        </div>
                        
                        <div class="job-details">
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo htmlspecialchars($job['experience']); ?></span>
                            </div>
                            <?php if ($job['salary']): ?>
                                <div class="job-detail">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span><?php echo htmlspecialchars($job['salary']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-description" id="desc_<?php echo $job['id']; ?>">
                            <?php 
                            $description = htmlspecialchars($job['description']);
                            if (strlen($description) > 150) {
                                echo substr($description, 0, 150) . '...';
                                echo '<span class="read-more" onclick="toggleDescription(' . $job['id'] . ')">Read more</span>';
                            } else {
                                echo $description;
                            }
                            ?>
                        </div>
                        
                        <?php if ($job['tags']): ?>
                            <div class="job-tags">
                                <?php foreach (explode(',', $job['tags']) as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="job-actions">
                            <button class="btn btn-edit" onclick="editJob(<?php echo $job['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <div class="no-jobs" style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(255, 255, 255, 0.95); border-radius: 25px; color: var(--text-medium);">
                        <i class="fas fa-briefcase" style="font-size: 3rem; color: var(--primary-green); margin-bottom: 20px;"></i>
                        <h3>No jobs posted yet</h3>
                        <p>Start by posting your first job listing!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 40px; border-radius: 20px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <h3 style="color: #dc3545; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Confirm Delete
            </h3>
            <p style="margin-bottom: 20px; font-size: 1.1rem;">Are you sure you want to delete job: <strong id="deleteJobTitle"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.95rem; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-info-circle"></i> This action cannot be undone and will delete all related applications.
            </p>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" style="padding: 12px 25px; border: 2px solid #ccc; background: white; border-radius: 12px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Cancel</button>
                <button onclick="confirmDelete()" style="padding: 12px 25px; border: none; background: #dc3545; color: white; border-radius: 12px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteJobId = null;

        function filterJobs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            
            jobCards.forEach(card => {
                const title = card.getAttribute('data-title');
                const company = card.getAttribute('data-company');
                const location = card.getAttribute('data-location');
                const type = card.getAttribute('data-type');
                
                const matchesSearch = title.includes(searchTerm) || company.includes(searchTerm) || location.includes(searchTerm);
                const matchesType = !typeFilter || type === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function toggleJobForm() {
            const form = document.getElementById('jobForm');
            form.classList.toggle('active');
        }

        function toggleDescription(jobId) {
            const descElement = document.getElementById('desc_' + jobId);
            const readMoreBtn = descElement.querySelector('.read-more');
            
            if (descElement.classList.contains('expanded')) {
                descElement.classList.remove('expanded');
                readMoreBtn.textContent = 'Read more';
            } else {
                descElement.classList.add('expanded');
                readMoreBtn.textContent = 'Read less';
            }
        }

        function editJob(jobId) {
            // Redirect to edit job page or open edit modal
            window.location.href = `edit_job.php?id=${jobId}`;
        }

        function deleteJob(jobId, jobTitle) {
            deleteJobId = jobId;
            document.getElementById('deleteJobTitle').textContent = jobTitle;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteJobId = null;
        }

        function confirmDelete() {
            if (deleteJobId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="job_id" value="${deleteJobId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html> 