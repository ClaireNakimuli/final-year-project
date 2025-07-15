<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

require_once '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = trim($_POST['system_name']);
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_port = trim($_POST['smtp_port']);
    $smtp_username = trim($_POST['smtp_username']);
    $smtp_password = trim($_POST['smtp_password']);
    $smtp_encryption = trim($_POST['smtp_encryption']);
    $from_email = trim($_POST['from_email']);
    $from_name = trim($_POST['from_name']);
    
    $sms_provider = trim($_POST['sms_provider']);
    $sms_api_key = trim($_POST['sms_api_key']);
    $sms_sender_id = trim($_POST['sms_sender_id']);
    
    $timezone = trim($_POST['timezone']);
    
    try {
        // Check if settings already exist
        $check_stmt = $conn->prepare("SELECT id FROM settings LIMIT 1");
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE settings SET 
                    system_name = ?, smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, 
                    smtp_encryption = ?, from_email = ?, from_name = ?, sms_provider = ?, sms_api_key = ?, 
                    sms_sender_id = ?, timezone = ?, updated_at = NOW() 
                    WHERE id = 1");
            $stmt->execute([$system_name, $smtp_host, $smtp_port, $smtp_username, $smtp_password, 
                          $smtp_encryption, $from_email, $from_name, $sms_provider, $sms_api_key, 
                          $sms_sender_id, $timezone]);
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO settings (
                    system_name, smtp_host, smtp_port, smtp_username, smtp_password, 
                    smtp_encryption, from_email, from_name, sms_provider, sms_api_key, 
                    sms_sender_id, timezone, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$system_name, $smtp_host, $smtp_port, $smtp_username, $smtp_password,
                          $smtp_encryption, $from_email, $from_name, $sms_provider, $sms_api_key,
                          $sms_sender_id, $timezone]);
        }
        
        $_SESSION['success_message'] = "System settings updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating settings: " . $e->getMessage();
    }
    
    header("Location: SYSTEM_SETTINGS.php");
    exit();
}

// Get current settings with error handling
try {
    $stmt = $conn->prepare("SELECT * FROM settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $settings = null;
}

// Get list of timezones
$timezones = DateTimeZone::listIdentifiers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR System Settings</title>
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .settings-card {
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
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .settings-card:hover {
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
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent-green);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .switch-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .switch-text {
            color: var(--text-dark);
            font-weight: 500;
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
            .settings-grid { grid-template-columns: 1fr; }
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
                <h1><i class="fas fa-cogs"></i> System Settings</h1>
                <p>Configure and manage system preferences</p>
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

            <div class="settings-grid">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="card-title">General Settings</div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div class="form-group">
                            <label for="site_name"><i class="fas fa-tag"></i> Site Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'LAIR Job Portal'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description"><i class="fas fa-align-left"></i> Site Description</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email"><i class="fas fa-envelope"></i> Admin Email</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone"><i class="fas fa-clock"></i> Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="Africa/Kampala" <?php echo ($settings['timezone'] ?? '') === 'Africa/Kampala' ? 'selected' : ''; ?>>Africa/Kampala</option>
                                <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save General Settings
                        </button>
                    </form>
                </div>

                <!-- Email Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-envelope-open"></i>
                        </div>
                        <div class="card-title">Email Settings</div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_email">
                        
                        <div class="form-group">
                            <label for="smtp_host"><i class="fas fa-server"></i> SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port"><i class="fas fa-network-wired"></i> SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_username"><i class="fas fa-user"></i> SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_password"><i class="fas fa-lock"></i> SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_encryption"><i class="fas fa-shield-alt"></i> Encryption</label>
                            <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Email Settings
                        </button>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="card-title">Security Settings</div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_security">
                        
                        <div class="switch-label">
                            <span class="switch-text">Enable Two-Factor Authentication</span>
                            <label class="switch">
                                <input type="checkbox" name="two_factor_auth" <?php echo ($settings['two_factor_auth'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="switch-label">
                            <span class="switch-text">Require Email Verification</span>
                            <label class="switch">
                                <input type="checkbox" name="email_verification" <?php echo ($settings['email_verification'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="switch-label">
                            <span class="switch-text">Enable CAPTCHA</span>
                            <label class="switch">
                                <input type="checkbox" name="enable_captcha" <?php echo ($settings['enable_captcha'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="session_timeout"><i class="fas fa-hourglass-half"></i> Session Timeout (minutes)</label>
                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?>" min="5" max="1440">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_login_attempts"><i class="fas fa-exclamation-triangle"></i> Max Login Attempts</label>
                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Security Settings
                        </button>
                    </form>
                </div>

                <!-- Job Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="card-title">Job Settings</div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_job_settings">
                        
                        <div class="form-group">
                            <label for="job_expiry_days"><i class="fas fa-calendar-times"></i> Job Expiry Days</label>
                            <input type="number" class="form-control" id="job_expiry_days" name="job_expiry_days" value="<?php echo htmlspecialchars($settings['job_expiry_days'] ?? '30'); ?>" min="7" max="365">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_jobs_per_company"><i class="fas fa-building"></i> Max Jobs per Company</label>
                            <input type="number" class="form-control" id="max_jobs_per_company" name="max_jobs_per_company" value="<?php echo htmlspecialchars($settings['max_jobs_per_company'] ?? '10'); ?>" min="1" max="100">
                        </div>
                        
                        <div class="switch-label">
                            <span class="switch-text">Auto-approve Job Postings</span>
                            <label class="switch">
                                <input type="checkbox" name="auto_approve_jobs" <?php echo ($settings['auto_approve_jobs'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="switch-label">
                            <span class="switch-text">Allow Job Applications</span>
                            <label class="switch">
                                <input type="checkbox" name="allow_applications" <?php echo ($settings['allow_applications'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Job Settings
                        </button>
                    </form>
                </div>
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