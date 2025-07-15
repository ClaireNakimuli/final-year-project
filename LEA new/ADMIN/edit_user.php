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
$user = null;

// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header('Location: USERS.php');
    exit();
}

// Fetch user data with error handling
try {
    $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: USERS.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
    $user = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $new_password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($email) || empty($full_name)) {
        $error = "Username, email, and full name are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if username or email already exists (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists.";
            } else {
                // Update user
                if (!empty($new_password)) {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, status=?, password=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$username, $email, $full_name, $phone, $status, $hashed_password, $user_id]);
                } else {
                    // Update without password
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, status=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$username, $email, $full_name, $phone, $status, $user_id]);
                }
                
                $success = "User updated successfully!";
                // Update local user data
                $user['username'] = $username;
                $user['email'] = $email;
                $user['full_name'] = $full_name;
                $user['phone'] = $phone;
                $user['status'] = $status;
            }
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR - Edit User</title>
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
        
        .user-info {
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
        .form-group select {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-green);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input::placeholder {
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
        
        .status-badge.pending {
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
            .user-info { grid-template-columns: 1fr; }
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
                    <i class="fas fa-user-edit"></i>
                    Edit User
                </h1>
                <p>Update user information and account settings</p>
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
                <div class="user-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Joined</div>
                            <div class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge <?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">User ID</div>
                            <div class="info-value">#<?php echo $user['id']; ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="" id="editUserForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i>
                                Username *
                            </label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required placeholder="Enter unique username">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required placeholder="Enter valid email address">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-id-card"></i>
                                Full Name *
                            </label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   required placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                   placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-toggle-on"></i>
                            Account Status
                        </label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            New Password
                        </label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" 
                                   placeholder="Enter new password (optional)" minlength="6">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                        <div class="password-note">Leave blank to keep current password</div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Update User
                        </button>
                        <a href="USERS.php" class="cancel-btn">
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
        
        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const email = document.getElementById('email').value;
            
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