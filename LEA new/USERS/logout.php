<?php
session_start();
require_once '../includes/config.php';

// Get user's name before destroying the session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - LEA</title>
    <style>
        :root {
            --primary-color: #02486b;
            --secondary-color: #036fa3;
            --accent-color: #2196F3;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --light-gray: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--white);
        }

        .logout-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
        }

        .goodbye-message {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease forwards;
        }

        .user-name {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease 0.3s forwards;
            color: #ffd700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .motivational-message {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease 0.6s forwards;
        }

        .emoji {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0;
            transform: scale(0.5);
            animation: popIn 0.8s ease 0.9s forwards;
        }

        .redirect-message {
            font-size: 1rem;
            opacity: 0;
            animation: fadeIn 0.8s ease 1.2s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes popIn {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .loading-bar {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 2rem auto;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.8s ease 1.2s forwards;
        }

        .loading-bar::after {
            content: '';
            display: block;
            width: 40%;
            height: 100%;
            background: var(--white);
            border-radius: 2px;
            animation: loading 2s ease 1.2s forwards;
        }

        @keyframes loading {
            to {
                transform: translateX(250%);
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="emoji">👋</div>
        <h1 class="goodbye-message">Goodbye!</h1>
        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
        <p class="motivational-message">
            Your journey to success continues! Keep searching, keep applying, and keep believing in yourself. 
            The perfect opportunity is just around the corner. 🌟
        </p>
        <div class="loading-bar"></div>
        <p class="redirect-message">Redirecting to login page...</p>
    </div>

    <script>
        // Redirect to login page after animation
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 4000);
    </script>
</body>
</html> 