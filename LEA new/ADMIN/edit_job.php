<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

// Database connection
require_once '../includes/config.php';

$success = $error = '';
$job = null;

// Get job ID from URL parameter
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    header('Location: JOBS.php');
    exit();
}

// Fetch job data
try {
    $stmt = $conn->prepare("SELECT id, title, company, type, location, experience, salary, description, requirements, tags, status, created_at FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header('Location: JOBS.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching job: " . $e->getMessage();
    header('Location: JOBS.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $company = trim($_POST['company']);
    $type = $_POST['type'];
    $location = trim($_POST['location']);
    $experience = trim($_POST['experience']);
    $salary = trim($_POST['salary']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $tags = trim($_POST['tags']);
    $status = $_POST['status'];

    // Validate input
    if (empty($title) || empty($company) || empty($location) || empty($experience) || empty($description)) {
        $error = "Title, company, location, experience, and description are required.";
    } else {
        // Update job
        try {
            $stmt = $conn->prepare("UPDATE jobs SET title=?, company=?, type=?, location=?, experience=?, salary=?, description=?, requirements=?, tags=?, status=?, updated_at=NOW() WHERE id=?");
            
            if ($stmt->execute([$title, $company, $type, $location, $experience, $salary, $description, $requirements, $tags, $status, $job_id])) {
                $success = "Job updated successfully!";
                // Update local job data
                $job['title'] = $title;
                $job['company'] = $company;
                $job['type'] = $type;
                $job['location'] = $location;
                $job['experience'] = $experience;
                $job['salary'] = $salary;
                $job['description'] = $description;
                $job['requirements'] = $requirements;
                $job['tags'] = $tags;
                $job['status'] = $status;
            } else {
                $error = "Error updating job.";
            }
        } catch (PDOException $e) {
            $error = "Error updating job: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR - Edit Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1000px;
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
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header p {
            color: var(--text-medium);
            font-size: 1.1rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            color: var(--primary-green);
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.1);
        }
        
        .back-btn:hover {
            background: var(--accent-green);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3);
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .job-info {
            background: rgba(6, 66, 57, 0.05);
            border: 1px solid rgba(6, 66, 57, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--white);
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--primary-green);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--primary-green);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-group label i {
            color: var(--accent-green);
            font-size: 1rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-green);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-light);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-group textarea.description {
            min-height: 150px;
        }
        
        .form-group textarea.requirements {
            min-height: 200px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.active {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border: 1px solid rgba(25, 135, 84, 0.2);
        }
        
        .status-badge.inactive {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .status-badge.draft {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
            border: 1px solid rgba(255, 193, 7, 0.2);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.4);
        }
        
        .cancel-btn {
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-medium);
            border: 2px solid var(--gray-border);
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .cancel-btn:hover {
            background: var(--gray-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInDown 0.5s ease-out;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border: 2px solid rgba(25, 135, 84, 0.2);
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 2px solid rgba(220, 53, 69, 0.2);
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
        
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .form-row { grid-template-columns: 1fr; }
            .form-container { padding: 25px; }
            .header { padding: 25px; }
            .header h1 { font-size: 2rem; }
            .button-group { flex-direction: column; }
            .job-info { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 480px) {
            .container { padding: 0 15px; }
            .form-container { padding: 20px; }
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
            <a href="JOBS.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Jobs
            </a>
            
            <div class="header">
                <h1>
                    <i class="fas fa-edit"></i>
                    Edit Job
                </h1>
                <p>Update job posting information and requirements</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <div class="job-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Posted</div>
                            <div class="info-value"><?php echo date('M j, Y', strtotime($job['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge <?php echo $job['status']; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Job ID</div>
                            <div class="info-value">#<?php echo $job['id']; ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="" id="editJobForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">
                                <i class="fas fa-briefcase"></i>
                                Job Title *
                            </label>
                            <input type="text" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($job['title']); ?>" 
                                   required placeholder="Enter job title">
                        </div>

                        <div class="form-group">
                            <label for="company">
                                <i class="fas fa-building"></i>
                                Company *
                            </label>
                            <input type="text" id="company" name="company" 
                                   value="<?php echo htmlspecialchars($job['company']); ?>" 
                                   required placeholder="Enter company name">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">
                                <i class="fas fa-clock"></i>
                                Job Type *
                            </label>
                            <select id="type" name="type" required>
                                <option value="Full-time" <?php echo $job['type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo $job['type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contract" <?php echo $job['type'] === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="Remote" <?php echo $job['type'] === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="location">
                                <i class="fas fa-map-marker-alt"></i>
                                Location *
                            </label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($job['location']); ?>" 
                                   required placeholder="Enter job location">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="experience">
                                <i class="fas fa-user-tie"></i>
                                Experience Level *
                            </label>
                            <input type="text" id="experience" name="experience" 
                                   value="<?php echo htmlspecialchars($job['experience']); ?>" 
                                   required placeholder="e.g., Entry Level, Mid-Level, Senior">
                        </div>

                        <div class="form-group">
                            <label for="salary">
                                <i class="fas fa-money-bill-wave"></i>
                                Salary Range
                            </label>
                            <input type="text" id="salary" name="salary" 
                                   value="<?php echo htmlspecialchars($job['salary']); ?>" 
                                   placeholder="e.g., $50,000 - $70,000">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-toggle-on"></i>
                            Job Status
                        </label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $job['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $job['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="draft" <?php echo $job['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-align-left"></i>
                            Job Description *
                        </label>
                        <textarea id="description" name="description" class="description" 
                                  required placeholder="Enter detailed job description..."><?php echo htmlspecialchars($job['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="requirements">
                            <i class="fas fa-list-check"></i>
                            Requirements & Qualifications
                        </label>
                        <textarea id="requirements" name="requirements" class="requirements" 
                                  placeholder="Enter job requirements and qualifications..."><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tags">
                            <i class="fas fa-tags"></i>
                            Tags
                        </label>
                        <input type="text" id="tags" name="tags" 
                               value="<?php echo htmlspecialchars($job['tags']); ?>" 
                               placeholder="Enter tags separated by commas (e.g., PHP, MySQL, JavaScript)">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Update Job
                        </button>
                        <a href="JOBS.php" class="cancel-btn">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Form validation
        document.getElementById('editJobForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const company = document.getElementById('company').value.trim();
            const location = document.getElementById('location').value.trim();
            const experience = document.getElementById('experience').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!title || !company || !location || !experience || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (description.length < 50) {
                e.preventDefault();
                alert('Job description should be at least 50 characters long.');
                return false;
            }
        });
        
        // Real-time form validation
        document.querySelectorAll('input[required], textarea[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.style.borderColor = 'var(--success)';
                }
            });
        });
        
        // Character counter for textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const maxLength = this.getAttribute('maxlength');
                if (maxLength) {
                    const remaining = maxLength - this.value.length;
                    // You can add a character counter display here if needed
                }
            });
        });
        
        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html> 