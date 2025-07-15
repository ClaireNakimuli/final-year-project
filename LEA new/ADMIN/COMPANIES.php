<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Create companies table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100),
    location VARCHAR(255),
    website VARCHAR(255),
    description TEXT,
    size VARCHAR(50),
    founded YEAR,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if (!$conn->exec($create_table)) {
        // Don't die, just log the error and continue
        error_log("Warning: Could not create companies table");
    }
} catch (PDOException $e) {
    // Don't die, just log the error and continue
    error_log("Warning: Error creating companies table: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $name = $_POST['name'];
            $industry = $_POST['industry'];
            $location = $_POST['location'];
            $website = $_POST['website'];
            $description = $_POST['description'];
            $size = $_POST['size'];
            $founded = $_POST['founded'];
            $status = $_POST['status'];

            try {
                $stmt = $conn->prepare("INSERT INTO companies (name, industry, location, website, description, size, founded, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $industry, $location, $website, $description, $size, $founded, $status])) {
                    $success_message = "Company added successfully!";
                } else {
                    $error_message = "Error adding company.";
                }
            } catch (PDOException $e) {
                $error_message = "Error adding company: " . $e->getMessage();
            }
            break;

        case 'update':
            $company_id = $_POST['company_id'];
            $name = $_POST['name'];
            $industry = $_POST['industry'];
            $location = $_POST['location'];
            $website = $_POST['website'];
            $description = $_POST['description'];
            $size = $_POST['size'];
            $founded = $_POST['founded'];
            $status = $_POST['status'];

            try {
                $stmt = $conn->prepare("UPDATE companies SET name = ?, industry = ?, location = ?, website = ?, description = ?, size = ?, founded = ?, status = ? WHERE id = ?");
                
                if ($stmt->execute([$name, $industry, $location, $website, $description, $size, $founded, $status, $company_id])) {
                    $success_message = "Company updated successfully!";
                } else {
                    $error_message = "Error updating company.";
                }
            } catch (PDOException $e) {
                $error_message = "Error updating company: " . $e->getMessage();
            }
            break;
    }
}

// Fetch all companies
try {
    // Check if companies table exists before querying
    $table_check = $conn->query("SHOW TABLES LIKE 'companies'");
    if ($table_check->rowCount() > 0) {
        $companies_result = $conn->query("SELECT * FROM companies ORDER BY created_at DESC");
        $companies_data = [];
        if ($companies_result) {
            while ($row = $companies_result->fetch(PDO::FETCH_ASSOC)) {
                $companies_data[] = $row;
            }
        }
    } else {
        $companies_data = [];
    }
} catch (PDOException $e) {
    $error_message = "Error fetching companies: " . $e->getMessage();
    $companies_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Companies Management</title>
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
        
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            margin-bottom: 30px;
        }
        
        .search-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        
        .company-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 30px;
            border-radius: 25px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            cursor: pointer;
            padding: 15px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .form-header:hover {
            background: rgba(6, 66, 57, 0.1);
        }
        
        .form-header h2 {
            color: var(--primary-green);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .form-header i {
            color: var(--accent-green);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .form-content {
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        .form-content.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #e74c3c 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, var(--warning) 0%, #ffb84d 100%);
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }
        
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .company-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .company-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 66, 57, 0.25);
        }
        
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .company-industry {
            color: var(--text-medium);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .company-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .company-details {
            margin: 20px 0;
        }
        
        .company-detail {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: var(--text-medium);
            font-size: 0.95rem;
        }
        
        .company-detail i {
            width: 20px;
            margin-right: 10px;
            color: var(--accent-green);
        }
        
        .company-description {
            color: var(--text-medium);
            line-height: 1.6;
            margin: 15px 0;
            padding: 15px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 10px;
            border-left: 4px solid var(--accent-green);
        }
        
        .company-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-border);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1.5px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border-color: rgba(25, 135, 84, 0.3);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-color: rgba(220, 53, 69, 0.3);
        }
        
        .alert-info {
            background: rgba(13, 202, 240, 0.1);
            color: var(--info);
            border-color: rgba(13, 202, 240, 0.3);
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--accent-green);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-medium);
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .companies-grid { grid-template-columns: 1fr; }
            .search-container { flex-direction: column; }
            .search-box { min-width: 100%; }
            .form-grid { grid-template-columns: 1fr; }
            .company-actions { flex-direction: column; }
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
                <h1><i class="fas fa-building"></i> Companies Management</h1>
                <p>Manage and monitor company profiles with advanced analytics</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <?php
            try {
                // Check if companies table exists before running statistics queries
                $table_check = $conn->query("SHOW TABLES LIKE 'companies'");
                if ($table_check->rowCount() > 0) {
                    $total_companies = $conn->query("SELECT COUNT(*) as count FROM companies")->fetch(PDO::FETCH_ASSOC)['count'];
                    $active_companies = $conn->query("SELECT COUNT(*) as count FROM companies WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['count'];
                    $pending_companies = $conn->query("SELECT COUNT(*) as count FROM companies WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'];
                    $inactive_companies = $conn->query("SELECT COUNT(*) as count FROM companies WHERE status = 'inactive'")->fetch(PDO::FETCH_ASSOC)['count'];
                } else {
                    $total_companies = $active_companies = $pending_companies = $inactive_companies = 0;
                }
            } catch (PDOException $e) {
                $total_companies = $active_companies = $pending_companies = $inactive_companies = 0;
            }
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-number"><?php echo $total_companies; ?></div>
                    <div class="stat-label">Total Companies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo $active_companies; ?></div>
                    <div class="stat-label">Active Companies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo $pending_companies; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-pause-circle"></i></div>
                    <div class="stat-number"><?php echo $inactive_companies; ?></div>
                    <div class="stat-label">Inactive Companies</div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search companies by name, industry, or location...">
                        <i class="fas fa-search"></i>
                    </div>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select class="filter-select" id="sizeFilter">
                        <option value="">All Sizes</option>
                        <option value="1-10">1-10 employees</option>
                        <option value="11-50">11-50 employees</option>
                        <option value="51-200">51-200 employees</option>
                        <option value="201-500">201-500 employees</option>
                        <option value="501-1000">501-1000 employees</option>
                        <option value="1000+">1000+ employees</option>
                    </select>
                </div>
            </div>

            <!-- Add Company Form -->
            <div class="company-form">
                <div class="form-header" onclick="toggleForm()">
                    <h2><i class="fas fa-plus-circle"></i> Add New Company</h2>
                    <i class="fas fa-chevron-down" id="formToggleIcon"></i>
                </div>
                <div class="form-content" id="formContent">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-building"></i> Company Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="industry"><i class="fas fa-industry"></i> Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry" required>
                            </div>

                            <div class="form-group">
                                <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>

                            <div class="form-group">
                                <label for="website"><i class="fas fa-globe"></i> Website</label>
                                <input type="url" class="form-control" id="website" name="website">
                            </div>

                            <div class="form-group">
                                <label for="size"><i class="fas fa-users"></i> Company Size</label>
                                <select class="form-control" id="size" name="size" required>
                                    <option value="">Select size</option>
                                    <option value="1-10">1-10 employees</option>
                                    <option value="11-50">11-50 employees</option>
                                    <option value="51-200">51-200 employees</option>
                                    <option value="201-500">201-500 employees</option>
                                    <option value="501-1000">501-1000 employees</option>
                                    <option value="1000+">1000+ employees</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="founded"><i class="fas fa-calendar-alt"></i> Founded Year</label>
                                <input type="number" class="form-control" id="founded" name="founded" min="1800" max="<?php echo date('Y'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea class="form-control" id="description" name="description" required placeholder="Describe the company, its mission, and key highlights..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Company
                        </button>
                    </form>
                </div>
            </div>

            <!-- Companies Grid -->
            <div class="companies-grid" id="companiesGrid">
                <?php if (!empty($companies_data)): ?>
                    <?php foreach ($companies_data as $company): ?>
                        <div class="company-card" data-name="<?php echo strtolower(htmlspecialchars($company['name'])); ?>" 
                             data-industry="<?php echo strtolower(htmlspecialchars($company['industry'])); ?>" 
                             data-location="<?php echo strtolower(htmlspecialchars($company['location'])); ?>"
                             data-status="<?php echo htmlspecialchars($company['status']); ?>"
                             data-size="<?php echo htmlspecialchars($company['size']); ?>">
                            <div class="company-header">
                                <div>
                                    <div class="company-name"><?php echo htmlspecialchars($company['name']); ?></div>
                                    <div class="company-industry"><?php echo htmlspecialchars($company['industry']); ?></div>
                                </div>
                                <div class="company-status status-<?php echo $company['status']; ?>">
                                    <?php echo ucfirst($company['status']); ?>
                                </div>
                            </div>
                            
                            <div class="company-details">
                                <div class="company-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($company['location']); ?></span>
                                </div>
                                <div class="company-detail">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($company['size']); ?> employees</span>
                                </div>
                                <div class="company-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Founded <?php echo htmlspecialchars($company['founded']); ?></span>
                                </div>
                                <?php if ($company['website']): ?>
                                    <div class="company-detail">
                                        <i class="fas fa-globe"></i>
                                        <span><a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" style="color: var(--accent-green); text-decoration: none;"><?php echo htmlspecialchars($company['website']); ?></a></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="company-description">
                                <?php echo nl2br(htmlspecialchars($company['description'])); ?>
                            </div>
                            
                            <div class="company-actions">
                                <button class="btn btn-edit" onclick="editCompany(<?php echo $company['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger" onclick="deleteCompany(<?php echo $company['id']; ?>, '<?php echo htmlspecialchars($company['name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1; text-align: center;">
                        <i class="fas fa-info-circle"></i>
                        No companies found. Add your first company above!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(5px);">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 20px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 20px;"></i>
            <h3 style="color: var(--text-dark); margin-bottom: 15px;">Confirm Delete</h3>
            <p style="color: var(--text-medium); margin-bottom: 25px;">Are you sure you want to delete <strong id="deleteCompanyName"></strong>? This action cannot be undone.</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="closeDeleteModal()" style="padding: 10px 20px; border: 2px solid var(--gray-border); background: white; color: var(--text-dark); border-radius: 10px; cursor: pointer;">Cancel</button>
                <button onclick="confirmDelete()" style="padding: 10px 20px; border: none; background: var(--danger); color: white; border-radius: 10px; cursor: pointer;">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteCompanyId = null;
        
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function toggleForm() {
            const formContent = document.getElementById('formContent');
            const toggleIcon = document.getElementById('formToggleIcon');
            
            formContent.classList.toggle('active');
            toggleIcon.style.transform = formContent.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
        
        function deleteCompany(id, name) {
            deleteCompanyId = id;
            document.getElementById('deleteCompanyName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteCompanyId = null;
        }
        
        function confirmDelete() {
            if (deleteCompanyId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="company_id" value="${deleteCompanyId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterCompanies);
        document.getElementById('statusFilter').addEventListener('change', filterCompanies);
        document.getElementById('sizeFilter').addEventListener('change', filterCompanies);
        
        function filterCompanies() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const sizeFilter = document.getElementById('sizeFilter').value;
            const cards = document.querySelectorAll('.company-card');
            
            cards.forEach(card => {
                const name = card.dataset.name;
                const industry = card.dataset.industry;
                const location = card.dataset.location;
                const status = card.dataset.status;
                const size = card.dataset.size;
                
                const matchesSearch = name.includes(searchTerm) || industry.includes(searchTerm) || location.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesSize = !sizeFilter || size === sizeFilter;
                
                if (matchesSearch && matchesStatus && matchesSize) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html> 