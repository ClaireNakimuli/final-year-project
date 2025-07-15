<?php
session_start();
require_once '../includes/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Fetch system name from settings table
$system_name = 'LAIR System'; // Default fallback
try {
    $settings_sql = "SELECT system_name FROM settings LIMIT 1";
    $settings_result = $conn->query($settings_sql);
    if ($settings_result && $settings_result->rowCount() > 0) {
        $settings = $settings_result->fetch(PDO::FETCH_ASSOC);
        $system_name = $settings['system_name'] ?: 'LAIR System';
    }
} catch (Exception $e) {
    // Keep default name if there's an error
    $system_name = 'LAIR System';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get security settings
    $security_sql = "SELECT * FROM security_settings LIMIT 1";
    $security_result = $conn->query($security_sql);
    $security = $security_result ? $security_result->fetch(PDO::FETCH_ASSOC) : null;

    // Check if user exists and is active
    $sql = "SELECT id, username, password, status, full_name FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $user = $result;
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            $error = "Your account is not active. Please contact the administrator.";
        } else {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();

                // Update last login timestamp
                $update_sql = "UPDATE users SET updated_at = NOW() WHERE id = :id";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                $update_stmt->execute();

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid username or password";
            }
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($system_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #02486b;
            --primary-blue-light: #036fa3;
            --white: #fff;
            --gray-light: #f5f6fa;
            --gray-border: #e0e0e0;
            --text-dark: #222;
            --text-medium: #444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--white);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(2,72,107,0.2);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: fadeInUp 0.5s ease-out;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .login-header p {
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        .form-control {
            background: var(--gray-light);
            border: 1.5px solid var(--gray-border);
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(2,72,107,0.1);
            transform: translateY(-2px);
        }

        .form-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: var(--primary-blue-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2,72,107,0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            font-weight: 500;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-medium);
        }

        .form-group .form-control {
            padding-left: 45px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 2rem;
            }
        }

        .login-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-border);
        }

        .forgot-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-blue-light);
            transform: translateY(-2px);
        }

        .register-link {
            color: var(--text-medium);
            font-size: 0.95rem;
        }

        .register-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: var(--primary-blue-light);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo $system_name; ?></h1>
            <p>Welcome Back! Please login to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required 
                       placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-login">✨ Login</button>
        </form>

        <div class="login-footer">
            <a href="forgot-password.php" class="forgot-link">🔑 Forgot Password?</a>
            <div class="register-link">
                Don't have an account? <a href="register.php">✨ Register Now</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 