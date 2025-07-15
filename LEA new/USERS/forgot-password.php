<?php
session_start();
require_once '../includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT id, username, full_name FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $update_sql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $token, $expires, $user['id']);
        
        if ($update_stmt->execute()) {
            // Send reset email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/lea/users/reset-password.php?token=" . $token;
            $to = $email;
            $subject = "Password Reset Request - LAIR System";
            $message = "Dear " . $user['full_name'] . ",\n\n";
            $message .= "You have requested to reset your password. Click the link below to reset your password:\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you did not request this reset, please ignore this email.\n\n";
            $message .= "Best regards,\nLAIR System Team";
            
            $headers = "From: noreply@lair.com\r\n";
            $headers .= "Reply-To: support@lair.com\r\n";
            
            if (mail($to, $subject, $message, $headers)) {
                $message = "Password reset instructions have been sent to your email.";
            } else {
                $error = "Failed to send reset email. Please try again later.";
            }
        } else {
            $error = "An error occurred. Please try again later.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LAIR System</title>
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

        .forgot-container {
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

        .forgot-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .forgot-header h1 {
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .forgot-header p {
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

        .btn-submit {
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

        .btn-submit:hover {
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
            .forgot-container {
                padding: 30px 20px;
            }

            .forgot-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>🦁 LAIR</h1>
            <p>Reset Your Password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Enter your email address">
            </div>

            <button type="submit" class="btn-submit">🔑 Send Reset Link</button>
        </form>

        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 