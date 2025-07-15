<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: USERS/dashboard.php');
    exit();
}

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: ADMIN/DASHBOARD.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEA System - Welcome</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            color: var(--gray-800);
        }

        /* Enhanced animated background elements */
        body::before,
        body::after {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 70%, transparent 100%);
            animation: float 8s ease-in-out infinite;
            z-index: 1;
        }

        body::before {
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        body::after {
            bottom: -200px;
            right: -200px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.7;
            }
            50% { 
                transform: translateY(-30px) rotate(180deg) scale(1.1);
                opacity: 1;
            }
        }

        /* Additional floating elements */
        .floating-element {
            position: fixed;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: floatElement 6s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes floatElement {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }
            50% { 
                transform: translateY(-20px) rotate(90deg);
                opacity: 0.6;
            }
        }

        .main-container {
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 32px;
            padding: 80px 60px;
            box-shadow: var(--shadow-2xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            animation: fadeInUp 1s ease-out;
            position: relative;
            z-index: 10;
            overflow: hidden;
        }

        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-purple), var(--accent-orange));
            border-radius: 32px 32px 0 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header-section {
            text-align: center;
            margin-bottom: 80px;
            position: relative;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .logo-icon {
            font-size: 5rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: logoGlow 3s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes logoPulse {
            0%, 100% { 
                transform: scale(1) rotate(0deg);
                filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3));
            }
            50% { 
                transform: scale(1.1) rotate(5deg);
                filter: drop-shadow(0 0 30px rgba(59, 130, 246, 0.5));
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
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple), var(--accent-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .subtitle {
            color: var(--gray-600);
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 15px;
            letter-spacing: -0.01em;
        }

        .description {
            color: var(--gray-500);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
            font-weight: 400;
        }

        .login-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 60px;
        }

        .login-card {
            background: var(--white);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gray-100);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-purple));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(124, 58, 237, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .login-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-2xl);
            border-color: var(--primary-blue);
        }

        .login-card:hover::before {
            transform: scaleX(1);
        }

        .login-card:hover::after {
            opacity: 1;
        }

        .card-icon {
            font-size: 4rem;
            margin-bottom: 30px;
            display: block;
            transition: all 0.4s ease;
        }

        .user-card .card-icon {
            color: var(--primary-blue);
        }

        .admin-card .card-icon {
            color: var(--secondary-purple);
        }

        .login-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
        }

        .card-title {
            color: var(--gray-800);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.01em;
        }

        .card-description {
            color: var(--gray-600);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 35px;
            font-weight: 400;
        }

        .btn-login {
            padding: 18px 45px;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            letter-spacing: -0.01em;
        }

        .btn-user {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-light));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--secondary-purple), var(--secondary-purple-light));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            color: var(--white);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .features-section {
            margin-top: 80px;
            text-align: center;
            position: relative;
        }

        .features-title {
            color: var(--gray-800);
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 50px;
            letter-spacing: -0.02em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .feature-item {
            text-align: center;
            padding: 30px 20px;
            background: var(--gray-50);
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            background: var(--white);
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            font-size: 2.8rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .feature-item:hover .feature-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 5px 15px rgba(59, 130, 246, 0.3));
        }

        .feature-text {
            color: var(--gray-700);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Enhanced responsive design */
        @media (max-width: 1024px) {
            .main-container {
                padding: 60px 40px;
                max-width: 900px;
            }

            .main-title {
                font-size: 3rem;
            }

            .login-options {
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 50px 30px;
                margin: 20px;
                border-radius: 24px;
            }

            .main-title {
                font-size: 2.5rem;
            }

            .subtitle {
                font-size: 1.3rem;
            }

            .description {
                font-size: 1.1rem;
            }

            .login-options {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .login-card {
                padding: 40px 30px;
            }

            .card-icon {
                font-size: 3.5rem;
            }

            .card-title {
                font-size: 1.8rem;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 30px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 40px 20px;
            }

            .main-title {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1.1rem;
            }

            .description {
                font-size: 1rem;
            }

            .login-card {
                padding: 30px 20px;
            }

            .btn-login {
                padding: 15px 35px;
                font-size: 1.1rem;
            }

            .features-title {
                font-size: 2rem;
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
    <!-- Floating background elements -->
    <div class="floating-element" style="top: 10%; left: 10%; animation-delay: 1s;"></div>
    <div class="floating-element" style="top: 20%; right: 15%; animation-delay: 2s;"></div>
    <div class="floating-element" style="bottom: 30%; left: 20%; animation-delay: 3s;"></div>
    <div class="floating-element" style="bottom: 10%; right: 10%; animation-delay: 4s;"></div>

    <div class="main-container loading">
        <!-- Header Section -->
        <div class="header-section">
            <div class="logo-container">
                <div class="logo-glow"></div>
                <i class="fas fa-briefcase logo-icon"></i>
            </div>
            <h1 class="main-title">LEA System</h1>
            <p class="subtitle">Labor & Employment Administration</p>
            <p class="description">
                Welcome to the comprehensive Labor and Employment Administration system. 
                Streamline your hiring process, manage job applications, and track employment opportunities 
                with our advanced platform designed for modern workforce management.
            </p>
        </div>

        <!-- Login Options -->
        <div class="login-options">
            <!-- User Login Card -->
            <div class="login-card user-card" onclick="window.location.href='USERS/login.php'">
                <i class="fas fa-user-tie card-icon"></i>
                <h3 class="card-title">Job Seeker</h3>
                <p class="card-description">
                    Access job opportunities, manage your applications, upload your CV, 
                    and track your employment journey with our comprehensive platform.
                </p>
                <a href="USERS/login.php" class="btn-login btn-user">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login as User
                </a>
            </div>

            <!-- Admin Login Card -->
            <div class="login-card admin-card" onclick="window.location.href='ADMIN/login.php'">
                <i class="fas fa-user-shield card-icon"></i>
                <h3 class="card-title">Administrator</h3>
                <p class="card-description">
                    Manage the system, review applications, handle user accounts, 
                    and oversee all administrative functions with full control.
                </p>
                <a href="ADMIN/login.php" class="btn-login btn-admin">
                    <i class="fas fa-cog me-2"></i>
                    Admin Login
                </a>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <h3 class="features-title">System Features</h3>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-search feature-icon"></i>
                    <div class="feature-text">Job Search</div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-file-upload feature-icon"></i>
                    <div class="feature-text">CV Upload</div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <div class="feature-text">Analytics</div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell feature-icon"></i>
                    <div class="feature-text">Notifications</div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users feature-icon"></i>
                    <div class="feature-text">User Management</div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <div class="feature-text">Security</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced user experience with smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add staggered animation to feature items
            const featureItems = document.querySelectorAll('.feature-item');
            featureItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
                item.style.animation = 'fadeInUp 0.6s ease-out forwards';
                item.style.opacity = '0';
            });

            // Add click effects to login cards
            const loginCards = document.querySelectorAll('.login-card');
            
            loginCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-12px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });

                // Add ripple effect on click
                card.addEventListener('click', function(e) {
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

            // Smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';

            // Add loading animation
            window.addEventListener('load', function() {
                document.body.style.opacity = '1';
            });
        });

        // Add parallax effect to floating elements
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelectorAll('.floating-element');
            
            parallax.forEach(element => {
                const speed = 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Add intersection observer for feature items
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature items
        document.querySelectorAll('.feature-item').forEach(item => {
            observer.observe(item);
        });
    </script>
</body>
</html> 