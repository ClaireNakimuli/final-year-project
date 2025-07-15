<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Create notifications table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('general', 'job_alert', 'application_update', 'system', 'promotional') DEFAULT 'general',
    recipient_type ENUM('all_users', 'job_seekers', 'employers', 'specific_users') DEFAULT 'all_users',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if (!$conn->exec($sql)) {
        error_log("Warning: Could not create notifications table");
    }
} catch (PDOException $e) {
    error_log("Warning: Error creating notifications table: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_notification'])) {
        // Sanitize input data
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $type = trim($_POST['type']);
        $recipient_type = trim($_POST['recipient_type']);
        $priority = trim($_POST['priority']);
        
        try {
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, recipient_type, priority) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $message, $type, $recipient_type, $priority]);
            $success_message = "Notification sent successfully!";
        } catch (PDOException $e) {
            $error_message = "Error sending notification: " . $e->getMessage();
        }
    }
}


// Get notification statistics with error handling
try {
    // Check if notifications table exists before running queries
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->rowCount() > 0) {
        $total_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $active_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $today_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE DATE(created_at) = CURDATE()")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $user_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE target_type = 'user'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    } else {
        $total_notifications = $active_notifications = $today_notifications = $user_notifications = 0;
    }
} catch (PDOException $e) {
    $total_notifications = $active_notifications = $today_notifications = $user_notifications = 0;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_notification' && isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notification_id]);
            
            // Optional: set a success message to show after redirect or refresh
            $success_message = "Notification deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting notification: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Notifications Management</title>
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
            max-width: 1400px;
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
            font-size: 3rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: var(--text-medium);
            font-size: 1.2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--accent-green);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-medium);
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .notification-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 30px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            cursor: pointer;
            padding: 15px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .form-header:hover {
            background: rgba(6, 66, 57, 0.1);
        }
        
        .form-header h3 {
            color: var(--primary-green);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-header i {
            color: var(--accent-green);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .form-content {
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        .form-content.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-green-light) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #e74c3c 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }
        
        .notifications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .notification-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 66, 57, 0.25);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .notification-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .notification-type {
            color: var(--text-medium);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .notification-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-sent { background: rgba(25, 135, 84, 0.1); color: var(--success); }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .status-failed { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        
        .notification-content {
            color: var(--text-medium);
            line-height: 1.6;
            margin: 15px 0;
            padding: 15px;
            background: rgba(6, 66, 57, 0.05);
            border-radius: 10px;
            border-left: 4px solid var(--accent-green);
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-border);
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1.5px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border-color: rgba(25, 135, 84, 0.3);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-color: rgba(220, 53, 69, 0.3);
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
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .notifications-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
            .notification-actions { flex-direction: column; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
            <div class="header">
                <h1><i class="fas fa-bell"></i> Notifications Management</h1>
                <p>Send and manage system notifications</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Notification Statistics -->
            <?php
            try {
                $total_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                $sent_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'sent'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                $pending_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                $failed_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'failed'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                $total_notifications = $sent_notifications = $pending_notifications = $failed_notifications = 0;
            }
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-number"><?php echo number_format($total_notifications); ?></div>
                    <div class="stat-label">Total Notifications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo number_format($sent_notifications); ?></div>
                    <div class="stat-label">Sent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo number_format($pending_notifications); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-number"><?php echo number_format($failed_notifications); ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>

            <!-- Send Notification Form -->
            <div class="notification-form">
                <div class="form-header" onclick="toggleForm()">
                    <h3><i class="fas fa-plus-circle"></i> Send New Notification</h3>
                    <i class="fas fa-chevron-down" id="formToggleIcon"></i>
                </div>
                <div class="form-content" id="formContent">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="send_notification">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title"><i class="fas fa-heading"></i> Notification Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="form-group">
                                <label for="type"><i class="fas fa-tag"></i> Notification Type</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">Select type</option>
                                    <option value="general">General</option>
                                    <option value="job_alert">Job Alert</option>
                                    <option value="application_update">Application Update</option>
                                    <option value="system">System</option>
                                    <option value="promotional">Promotional</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="recipient_type"><i class="fas fa-users"></i> Recipient Type</label>
                                <select class="form-control" id="recipient_type" name="recipient_type" required>
                                    <option value="">Select recipient type</option>
                                    <option value="all_users">All Users</option>
                                    <option value="job_seekers">Job Seekers</option>
                                    <option value="employers">Employers</option>
                                    <option value="specific_users">Specific Users</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority"><i class="fas fa-exclamation"></i> Priority</label>
                                <select class="form-control" id="priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message"><i class="fas fa-align-left"></i> Message Content</label>
                            <textarea class="form-control" id="message" name="message" required placeholder="Enter your notification message..."></textarea>
                        </div>

                        <button type="submit" name="create_notification" class="btn btn-primary">Send Notification</button>
                            <i class="fas fa-paper-plane"></i> Send Notification
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notifications Grid -->
            <div class="notifications-grid" id="notificationsGrid">
                <?php
                try {
                    // Create notifications table if it doesn't exist
                    $create_table = "CREATE TABLE IF NOT EXISTS notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        message TEXT NOT NULL,
                        type ENUM('general', 'job_alert', 'application_update', 'system', 'promotional') DEFAULT 'general',
                        recipient_type ENUM('all_users', 'job_seekers', 'employers', 'specific_users') DEFAULT 'all_users',
                        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                        sent_count INT DEFAULT 0,
                        failed_count INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    if (!$conn->exec($create_table)) {
                        error_log("Warning: Could not create notifications table");
                    }
                    
                    $notifications_query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20";
                    $notifications = $conn->query($notifications_query);
                    
                    if ($notifications && $notifications->rowCount() > 0):
                        while ($notification = $notifications->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="notification-card">
                        <div class="notification-header">
                            <div>
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-type"><?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?></div>
                            </div>
                            <div class="notification-status status-<?php echo $notification['status']; ?>">
                                <?php echo ucfirst($notification['status']); ?>
                            </div>
                        </div>
                        
                        <div class="notification-content">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <div class="notification-meta">
                            <div>
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                            </div>
                            <div class="notification-actions">
                                <button class="btn btn-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                        endwhile;
                    else:
                ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1; text-align: center;">
                        <i class="fas fa-info-circle"></i>
                        No notifications found. Send your first notification above!
                    </div>
                <?php 
                    endif;
                } catch (PDOException $e) {
                ?>
                    <div class="alert alert-danger" style="grid-column: 1 / -1; text-align: center;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading notifications: <?php echo htmlspecialchars($e->getMessage()); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function toggleForm() {
            const formContent = document.getElementById('formContent');
            const toggleIcon = document.getElementById('formToggleIcon');
            
            formContent.classList.toggle('active');
            toggleIcon.style.transform = formContent.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
        
        function deleteNotification(id) {
            if (confirm('Are you sure you want to delete this notification?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_notification">
                    <input type="hidden" name="notification_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 