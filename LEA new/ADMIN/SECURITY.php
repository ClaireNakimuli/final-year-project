<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

require_once '../includes/config.php';

// Create security_settings table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_password_length INT NOT NULL DEFAULT 8,
    require_special_chars TINYINT(1) NOT NULL DEFAULT 0,
    require_numbers TINYINT(1) NOT NULL DEFAULT 0,
    require_uppercase TINYINT(1) NOT NULL DEFAULT 0,
    max_login_attempts INT NOT NULL DEFAULT 5,
    lockout_duration INT NOT NULL DEFAULT 30,
    session_timeout INT NOT NULL DEFAULT 30,
    enable_2fa TINYINT(1) NOT NULL DEFAULT 0,
    enable_captcha TINYINT(1) NOT NULL DEFAULT 0,
    ip_whitelist TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if (!$conn->exec($create_table_sql)) {
        error_log("Warning: Could not create security_settings table");
    }
} catch (PDOException $e) {
    error_log("Warning: Error creating security_settings table: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min_password_length = (int)$_POST['min_password_length'];
    $require_special_chars = isset($_POST['require_special_chars']) ? 1 : 0;
    $require_numbers = isset($_POST['require_numbers']) ? 1 : 0;
    $require_uppercase = isset($_POST['require_uppercase']) ? 1 : 0;
    $max_login_attempts = (int)$_POST['max_login_attempts'];
    $lockout_duration = (int)$_POST['lockout_duration'];
    $session_timeout = (int)$_POST['session_timeout'];
    $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
    $enable_captcha = isset($_POST['enable_captcha']) ? 1 : 0;
    $ip_whitelist = trim($_POST['ip_whitelist']);
    
    try {
        // Check if security settings exist
        $check_stmt = $conn->prepare("SELECT id FROM security_settings LIMIT 1");
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE security_settings SET 
                    min_password_length = ?, require_special_chars = ?, require_numbers = ?, 
                    require_uppercase = ?, max_login_attempts = ?, lockout_duration = ?, 
                    session_timeout = ?, enable_2fa = ?, enable_captcha = ?, ip_whitelist = ?, 
                    updated_at = NOW() 
                    WHERE id = 1");
            $stmt->execute([$min_password_length, $require_special_chars, $require_numbers, 
                          $require_uppercase, $max_login_attempts, $lockout_duration, 
                          $session_timeout, $enable_2fa, $enable_captcha, $ip_whitelist]);
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO security_settings (
                    min_password_length, require_special_chars, require_numbers, 
                    require_uppercase, max_login_attempts, lockout_duration, 
                    session_timeout, enable_2fa, enable_captcha, ip_whitelist, 
                    created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$min_password_length, $require_special_chars, $require_numbers, 
                          $require_uppercase, $max_login_attempts, $lockout_duration, 
                          $session_timeout, $enable_2fa, $enable_captcha, $ip_whitelist]);
        }
        
        $_SESSION['success_message'] = "Security settings updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating security settings: " . $e->getMessage();
    }
    
    header("Location: SECURITY.php");
    exit();
}

// Get current security settings with error handling
try {
    // Check if security_settings table exists before running queries
    $table_check = $conn->query("SHOW TABLES LIKE 'security_settings'");
    if ($table_check->rowCount() > 0) {
        $stmt = $conn->prepare("SELECT * FROM security_settings LIMIT 1");
        $stmt->execute();
        $security = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $security = null;
    }
} catch (PDOException $e) {
    $security = null;
}

// If no settings exist, use default values
if (!$security) {
    $security = [
        'min_password_length' => 8,
        'require_special_chars' => 0,
        'require_numbers' => 0,
        'require_uppercase' => 0,
        'max_login_attempts' => 5,
        'lockout_duration' => 30,
        'session_timeout' => 30,
        'enable_2fa' => 0,
        'enable_captcha' => 0,
        'ip_whitelist' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Security Management</title>
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
        
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .security-card {
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
        
        .security-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .security-card:hover {
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
        
        .security-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .status-secure {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        
        .status-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .security-item:last-child {
            border-bottom: none;
        }
        
        .security-item-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .security-item-value {
            color: var(--text-medium);
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
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
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #ffb84d 100%);
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
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
            .security-grid { grid-template-columns: 1fr; }
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
                <h1><i class="fas fa-shield-alt"></i> Security Management</h1>
                <p>Monitor and manage system security settings</p>
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

            <div class="security-grid">
                <!-- System Security Status -->
                <div class="security-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <div class="card-title">System Security Status</div>
                    </div>
                    
                    <div class="security-status status-secure">
                        <i class="fas fa-check-circle"></i>
                        System Security: Secure
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-lock"></i>
                            SSL Certificate
                        </div>
                        <div class="security-item-value">Valid</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-firewall"></i>
                            Firewall Status
                        </div>
                        <div class="security-item-value">Active</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-virus-slash"></i>
                            Malware Protection
                        </div>
                        <div class="security-item-value">Enabled</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-clock"></i>
                            Last Security Scan
                        </div>
                        <div class="security-item-value"><?php echo date('M d, Y H:i'); ?></div>
                    </div>
                </div>

                <!-- User Security -->
                <div class="security-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-users-shield"></i>
                        </div>
                        <div class="card-title">User Security</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-user-lock"></i>
                            Two-Factor Authentication
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-primary" onclick="toggle2FA()">
                                <i class="fas fa-toggle-on"></i> Enable
                            </button>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-key"></i>
                            Password Policy
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-warning" onclick="updatePasswordPolicy()">
                                <i class="fas fa-edit"></i> Configure
                            </button>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-ban"></i>
                            Failed Login Attempts
                        </div>
                        <div class="security-item-value">5 attempts</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-hourglass-half"></i>
                            Session Timeout
                        </div>
                        <div class="security-item-value">30 minutes</div>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="security-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="card-title">Access Control</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-user-tie"></i>
                            Admin Users
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-primary" onclick="manageAdmins()">
                                <i class="fas fa-users-cog"></i> Manage
                            </button>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-user-slash"></i>
                            Suspended Users
                        </div>
                        <div class="security-item-value">0 users</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-eye"></i>
                            Login Monitoring
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-warning" onclick="viewLoginLogs()">
                                <i class="fas fa-list"></i> View Logs
                            </button>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-map-marker-alt"></i>
                            IP Restrictions
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-primary" onclick="configureIPRestrictions()">
                                <i class="fas fa-cog"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Security Logs -->
                <div class="security-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-title">Security Logs</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-exclamation-triangle"></i>
                            Security Alerts
                        </div>
                        <div class="security-item-value">0 today</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-sign-in-alt"></i>
                            Failed Logins
                        </div>
                        <div class="security-item-value">2 today</div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-download"></i>
                            Export Logs
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-primary" onclick="exportLogs()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-item-label">
                            <i class="fas fa-trash"></i>
                            Clear Logs
                        </div>
                        <div class="security-item-value">
                            <button class="btn btn-danger" onclick="clearLogs()">
                                <i class="fas fa-trash"></i> Clear
                            </button>
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
        
        function toggle2FA() {
            if (confirm('Do you want to enable Two-Factor Authentication for all users?')) {
                // Add 2FA toggle functionality
                alert('Two-Factor Authentication has been enabled.');
            }
        }
        
        function updatePasswordPolicy() {
            const minLength = prompt('Enter minimum password length (8-20):', '8');
            const requireSpecial = confirm('Require special characters?');
            const requireNumbers = confirm('Require numbers?');
            
            if (minLength) {
                alert('Password policy updated successfully!');
            }
        }
        
        function manageAdmins() {
            alert('Admin management feature will be implemented here.');
        }
        
        function viewLoginLogs() {
            alert('Login logs will be displayed here.');
        }
        
        function configureIPRestrictions() {
            alert('IP restriction configuration will be implemented here.');
        }
        
        function exportLogs() {
            alert('Security logs will be exported.');
        }
        
        function clearLogs() {
            if (confirm('Are you sure you want to clear all security logs? This action cannot be undone.')) {
                alert('Security logs have been cleared.');
            }
        }
    </script>
</body>
</html> 