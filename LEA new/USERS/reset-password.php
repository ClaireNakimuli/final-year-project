<?php
session_start();
require_once '../includes/config.php';

$error = '';
$success = '';
$token_valid = false;
$user_id = null;

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Verify token
    $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $token_valid = true;
    } else {
        $error = "Invalid or expired reset token.";
    }
} else {
    $error = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $update_sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            $success = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to reset password. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LAIR System</title>
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

        .reset-container {
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

        .reset-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .reset-header h1 {
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .reset-header p {
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

        .btn-reset {
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
        }

        .btn-reset:hover {
            background: var(--primary-blue-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2,72,107,0.2);
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .back-to-login {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-border);
        }

        .back-to-login a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-login a:hover {
            color: var(--primary-blue-light);
            text-decoration: underline;
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
            .reset-container {
                padding: 30px 20px;
            }

            .reset-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🦁 LAIR</h1>
            <p>Reset Your Password</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm new password">
                </div>

                <button type="submit" class="btn-reset">🔑 Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 