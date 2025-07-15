<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user information
$sql = "SELECT * FROM users WHERE id = :user_id";
try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}

// Initialize default values for user data
$user = array_merge([
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'bio' => '',
    'email_notifications' => 0,
    'application_updates' => 0,
    'job_alerts' => 0
], $user ?: []);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format";
        } else {
            // Check if email is already taken by another user
            $sql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $error_message = "Email is already taken";
            } else {
                // Update profile
                $sql = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, bio = :bio WHERE id = :user_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':bio', $bio, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully";
                    // Refresh user data
                    $sql = "SELECT * FROM users WHERE id = :user_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Error updating profile";
                }
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success_message = "Password updated successfully";
            } else {
                $error_message = "Error updating password";
            }
        }
    } elseif (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $application_updates = isset($_POST['application_updates']) ? 1 : 0;
        $job_alerts = isset($_POST['job_alerts']) ? 1 : 0;

        // Update notification preferences
        $sql = "UPDATE users SET 
                email_notifications = :email_notifications,
                application_updates = :application_updates,
                job_alerts = :job_alerts
                WHERE id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email_notifications', $email_notifications, PDO::PARAM_INT);
        $stmt->bindParam(':application_updates', $application_updates, PDO::PARAM_INT);
        $stmt->bindParam(':job_alerts', $job_alerts, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $success_message = "Notification preferences updated successfully";
            // Refresh user data
            $sql = "SELECT * FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Error updating notification preferences";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - LEA</title>
    <style>
        :root {
            --primary-color: #02486b;
            --secondary-color: #036fa3;
            --accent-color: #2196F3;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --success-color: #28a745;
            --error-color: #dc3545;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .page-title {
            color: var(--text-dark);
            font-size: 1.5em;
        }

        .settings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .settings-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .section-title {
            color: var(--text-dark);
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }

        .btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: var(--secondary-color);
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .settings-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">⚙️ Settings</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Profile Settings -->
            <div class="settings-section">
                <h2 class="section-title">👤 Profile Information</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>

            <!-- Password Settings -->
            <div class="settings-section">
                <h2 class="section-title">🔒 Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password" class="btn">Update Password</button>
                </form>
            </div>

            <!-- Notification Settings -->
            <div class="settings-section">
                <h2 class="section-title">🔔 Notification Preferences</h2>
                <form method="POST" action="">
                    <div class="checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                               <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                        <label for="email_notifications">Email Notifications</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="application_updates" name="application_updates"
                               <?php echo $user['application_updates'] ? 'checked' : ''; ?>>
                        <label for="application_updates">Application Status Updates</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="job_alerts" name="job_alerts"
                               <?php echo $user['job_alerts'] ? 'checked' : ''; ?>>
                        <label for="job_alerts">Job Alerts</label>
                    </div>
                    <button type="submit" name="update_notifications" class="btn">Save Preferences</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
        }
    </script>
</body>
</html> 