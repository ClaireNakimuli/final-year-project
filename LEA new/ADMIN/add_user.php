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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $status = 'active';

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $status])) {
                    $success = "User added successfully!";
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = "Error adding user";
                }
            }
        } catch (PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR - Add New User</title>
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
            max-width: 800px;
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
        
        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-green);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input::placeholder {
            color: var(--text-light);
        }
        
        .form-group input:invalid {
            border-color: var(--danger);
        }
        
        .form-group input:valid {
            border-color: var(--success);
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
            margin-top: 30px;
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
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 2px solid rgba(220, 53, 69, 0.2);
        }
        
        .form-info {
            background: rgba(13, 202, 240, 0.1);
            border: 2px solid rgba(13, 202, 240, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            color: var(--info);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
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
            <a href="USERS.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Users
            </a>
            
            <div class="header">
                <h1>
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h1>
                <p>Create a new user account for the LAIR job portal</p>
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
                <div class="form-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Note:</strong> All fields marked with an asterisk (*) are required. 
                        The user will be created with an "Active" status by default.
                    </div>
                </div>
                
                <form method="POST" action="" id="addUserForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i>
                                Username *
                            </label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required placeholder="Enter unique username">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required placeholder="Enter valid email address">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password *
                        </label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" 
                                   required placeholder="Enter secure password" minlength="6">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-id-card"></i>
                                Full Name *
                            </label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                   required placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   placeholder="Enter phone number (optional)">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        Create User Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
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
        
        // Form validation and enhancement
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const email = document.getElementById('email').value;
            
            // Basic password strength validation
            if (password.length < 6) {
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
        
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                e.target.value = !value[2] ? value[1] : !value[3] ? value[1] + '-' + value[2] : value[1] + '-' + value[2] + '-' + value[3];
            }
        });
    </script>
</body>
</html> 