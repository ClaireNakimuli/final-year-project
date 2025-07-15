<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <meta http-equiv="refresh" content="2;url=login.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color1: #FFBFC3;
            --color2: #EBC1C5;
            --color3: #DAC0C7;
            --color4: #AAC3B2;
            --color5: #B8F4DE;
            --text-dark: #222;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--color1) 0%, var(--color2) 20%, var(--color3) 50%, var(--color4) 80%, var(--color5) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-container {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logout-emoji {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounce 1.2s infinite alternate;
        }
        .logout-text {
            color: var(--text-dark);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .logout-subtext {
            color: var(--text-dark);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 5px solid var(--color3);
            border-top: 5px solid var(--color4);
            border-radius: 50%;
            margin: 0 auto 1.2rem;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes bounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-emoji">🚪</div>
        <div class="spinner"></div>
        <div class="logout-text">Logging you out...</div>
        <div class="logout-subtext">See you soon! Redirecting to login page.</div>
    </div>
</body>
</html> 