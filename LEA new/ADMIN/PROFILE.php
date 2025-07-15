<?php
session_start();
require_once '../includes/config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];

// Initialize variables
$error = '';
$success = '';

// Fetch current admin data with error handling
try {
    $stmt = $conn->prepare('SELECT username, email, full_name FROM admin WHERE id = ?');
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_data) {
        $username = $admin_data['username'];
        $email = $admin_data['email'];
        $full_name = $admin_data['full_name'];
    } else {
        // Redirect if admin not found
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching admin data: " . $e->getMessage();
    $username = $email = $full_name = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_full_name = trim($_POST['full_name']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_username) || empty($new_email) || empty($new_full_name)) {
        $error = 'All fields except password are required.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check for unique username/email
            $stmt = $conn->prepare('SELECT id FROM admin WHERE (username = ? OR email = ?) AND id != ?');
            $stmt->execute([$new_username, $new_email, $admin_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already taken.';
            } else {
                // Update admin info
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE admin SET username=?, email=?, full_name=?, password=?, updated_at=NOW() WHERE id=?');
                    $stmt->execute([$new_username, $new_email, $new_full_name, $hashed_password, $admin_id]);
                } else {
                    $stmt = $conn->prepare('UPDATE admin SET username=?, email=?, full_name=?, updated_at=NOW() WHERE id=?');
                    $stmt->execute([$new_username, $new_email, $new_full_name, $admin_id]);
                }
                
                $success = 'Profile updated successfully!';
                $_SESSION['admin_username'] = $new_username;
                $username = $new_username;
                $email = $new_email;
                $full_name = $new_full_name;
            }
        } catch (PDOException $e) {
            $error = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR - Admin Profile</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 30px;
        }
        
        .profile-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--white);
            font-weight: 700;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 20px;
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3);
            position: relative;
        }
        
        .profile-avatar::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
        }
        
        .profile-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .profile-subtitle {
            color: var(--text-medium);
            font-size: 1rem;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--primary-green);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-label i {
            color: var(--accent-green);
            font-size: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control::placeholder {
            color: var(--text-light);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 50px;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .password-toggle-btn:hover {
            color: var(--accent-green);
            background: rgba(13, 148, 136, 0.1);
        }
        
        .password-note {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
            font-style: italic;
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
            width: 100%;
            margin-top: 20px;
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
        
        .submit-btn:active {
            transform: translateY(-1px);
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
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 2px solid rgba(220, 53, 69, 0.2);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(6, 66, 57, 0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 0; 
                padding: 20px;
            }
            .mobile-menu-btn { display: block; }
            .profile-container { 
                padding: 30px 25px; 
                margin: 20px;
            }
            .profile-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .profile-container { 
                padding: 25px 20px; 
                margin: 10px;
            }
            .profile-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-crown"></i>
                </div>
                <h1 class="profile-title">
                    <i class="fas fa-user-shield"></i>
                    Admin Profile
                </h1>
                <p class="profile-subtitle">Manage your account details and preferences</p>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value">12.8K</div>
                    <div class="stat-label">Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">2.1K</div>
                    <div class="stat-label">Jobs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">45.2K</div>
                    <div class="stat-label">Applications</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="profileForm" autocomplete="off">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i>
                        Full Name
                    </label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                           required placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required placeholder="Enter unique username">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                           required placeholder="Enter your email address">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        New Password
                    </label>
                    <div class="password-toggle">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter new password (optional)" minlength="6">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                    <div class="password-note">Leave blank to keep your current password</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm New Password
                    </label>
                    <div class="password-toggle">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" minlength="6">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId === 'password' ? 'passwordIcon' : 'confirmPasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const email = document.getElementById('email').value;
            
            // Password confirmation validation
            if (password && password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            // Password strength validation
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            // Email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
        
        // Real-time form validation
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.style.borderColor = 'var(--success)';
                }
            });
        });
        
        // Password confirmation real-time validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword) {
                if (password === confirmPassword) {
                    this.style.borderColor = 'var(--success)';
                } else {
                    this.style.borderColor = 'var(--danger)';
                }
            } else {
                this.style.borderColor = 'var(--gray-border)';
            }
        });
    </script>
</body>
</html> 