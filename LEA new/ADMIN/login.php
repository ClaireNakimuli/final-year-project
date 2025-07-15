<?php
require_once '../includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = '<i class="fas fa-exclamation-triangle me-2"></i>Please enter both username and password!';
    } else {
        $stmt = $conn->prepare('SELECT * FROM admin WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (password_verify($password, $row['password'])) {
                session_start();
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                header('Location: DASHBOARD.php');
                exit();
            } else {
                $error = '<i class="fas fa-times-circle me-2"></i>Incorrect password!';
            }
        } else {
            $error = '<i class="fas fa-user-times me-2"></i>User not found!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LEA System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e40af;
            --primary-blue-light: #3b82f6;
            --primary-blue-dark: #1e3a8a;
            --secondary-purple: #7c3aed;
            --secondary-purple-light: #a855f7;
            --accent-orange: #f59e0b;
            --accent-green: #10b981;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--secondary-purple) 0%, var(--secondary-purple-light) 50%, var(--primary-blue) 100%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            color: var(--gray-800);
        }

        /* Animated background elements */
        body::before,
        body::after {
            content: '';
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 70%, transparent 100%);
            animation: float 8s ease-in-out infinite;
            z-index: 1;
        }

        body::before {
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        body::after {
            bottom: -150px;
            right: -150px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.7;
            }
            50% { 
                transform: translateY(-20px) rotate(180deg) scale(1.1);
                opacity: 1;
            }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 32px;
            padding: 60px 50px;
            box-shadow: var(--shadow-2xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            animation: fadeInUp 0.8s ease-out;
            position: relative;
            z-index: 10;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--secondary-purple), var(--primary-blue), var(--accent-orange));
            border-radius: 32px 32px 0 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header-section {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
        }

        .logo-icon {
            font-size: 4rem;
            color: var(--secondary-purple);
            margin-bottom: 15px;
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: logoGlow 3s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes logoPulse {
            0%, 100% { 
                transform: scale(1) rotate(0deg);
                filter: drop-shadow(0 0 20px rgba(124, 58, 237, 0.3));
            }
            50% { 
                transform: scale(1.1) rotate(5deg);
                filter: drop-shadow(0 0 30px rgba(124, 58, 237, 0.5));
            }
        }

        @keyframes logoGlow {
            0%, 100% { 
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(1);
            }
            50% { 
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.2);
            }
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--secondary-purple), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-label {
            color: var(--gray-700);
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .form-control {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            padding: 15px 20px 15px 50px;
            font-size: 1rem;
            color: var(--gray-800);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--secondary-purple);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
            background: var(--white);
            outline: none;
            transform: translateY(-2px);
        }

        .form-control:focus + .input-icon {
            color: var(--secondary-purple);
        }

        .form-control::placeholder {
            color: var(--gray-400);
            font-weight: 400;
        }

        .btn-login {
            width: 100%;
            padding: 16px 30px;
            background: linear-gradient(135deg, var(--secondary-purple), var(--secondary-purple-light));
            color: var(--white);
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            letter-spacing: -0.01em;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
            color: var(--white);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .alert {
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: var(--secondary-purple);
        }

        /* Enhanced responsive design */
        @media (max-width: 768px) {
            .login-container {
                padding: 50px 30px;
                margin: 20px;
                border-radius: 24px;
            }

            .main-title {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .logo-icon {
                font-size: 3.5rem;
            }

            .form-control {
                padding: 14px 18px 14px 45px;
                font-size: 0.95rem;
            }

            .btn-login {
                padding: 15px 25px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 25px;
            }

            .main-title {
                font-size: 1.8rem;
            }

            .logo-icon {
                font-size: 3rem;
            }

            .form-control {
                padding: 12px 16px 12px 40px;
                font-size: 0.9rem;
            }

            .btn-login {
                padding: 14px 20px;
                font-size: 0.95rem;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="login-container loading">
        <!-- Header Section -->
        <div class="header-section">
            <div class="logo-container">
                <div class="logo-glow"></div>
                <i class="fas fa-user-shield logo-icon"></i>
            </div>
            <h1 class="main-title">Admin Login</h1>
            <p class="subtitle">Access Administrative Dashboard</p>
        </div>

        <!-- Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Enter username or email" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter password" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login to Dashboard
            </button>
        </form>

        <!-- Back Link -->
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced form validation and user experience
        document.addEventListener('DOMContentLoaded', function() {
        // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Add focus effects to form controls
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });

                control.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Add loading state to button
            const loginButton = document.querySelector('.btn-login');
            const form = document.querySelector('form');

            form.addEventListener('submit', function() {
                loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
                loginButton.disabled = true;
            });

            // Add ripple effect to button
            loginButton.addEventListener('click', function(e) {
                const ripple = document.createElement('div');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                    left: ${e.offsetX}px;
                    top: ${e.offsetY}px;
                    width: 100px;
                    height: 100px;
                    margin-left: -50px;
                    margin-top: -50px;
                `;
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });

            // Add ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Auto-focus on username field
            document.getElementById('username').focus();
        });
    </script>
</body>
</html> 