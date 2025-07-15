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

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];

    if (empty($subject) || empty($message)) {
        $error_message = "Please fill in all required fields";
    } else {
        try {
            $sql = "INSERT INTO support_tickets (user_id, subject, message, category, priority) VALUES (:user_id, :subject, :message, :category, :priority)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $success_message = "Support ticket submitted successfully";
            } else {
                throw new Exception("Error executing statement");
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Get user's tickets
try {
    $sql = "SELECT * FROM support_tickets WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $tickets = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - LEA</title>
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

        .support-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        .new-ticket-section, .tickets-section {
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
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

        .ticket-card {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .ticket-card:last-child {
            border-bottom: none;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .ticket-subject {
            color: var(--text-dark);
            font-size: 1.1em;
            font-weight: bold;
        }

        .ticket-meta {
            display: flex;
            gap: 15px;
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .ticket-message {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
        }

        .status-open { background: #cce5ff; color: #004085; }
        .status-in_progress { background: #fff3cd; color: #856404; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #f8d7da; color: #721c24; }

        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }

        .category-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .category-technical { background: #cce5ff; color: #004085; }
        .category-account { background: #d4edda; color: #155724; }
        .category-billing { background: #fff3cd; color: #856404; }
        .category-other { background: #e2e3e5; color: #383d41; }

        .admin-response {
            background: var(--light-gray);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .support-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">🛟 Support</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="support-container">
            <!-- New Ticket Form -->
            <div class="new-ticket-section">
                <h2 class="section-title">📝 New Support Ticket</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="technical">Technical Issue</option>
                            <option value="account">Account Related</option>
                            <option value="billing">Billing Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" name="submit_ticket" class="btn">Submit Ticket</button>
                </form>
            </div>

            <!-- Tickets List -->
            <div class="tickets-section">
                <h2 class="section-title">📋 Your Support Tickets</h2>
                <?php
                if ($tickets && count($tickets) > 0) {
                    foreach ($tickets as $ticket) {
                        ?>
                        <div class="ticket-card">
                            <div class="ticket-header">
                                <div class="ticket-subject">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                    <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                    <span class="category-badge category-<?php echo $ticket['category']; ?>">
                                        <?php echo ucfirst($ticket['category']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ticket-meta">
                                <span>📅 <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></span>
                                <?php if ($ticket['updated_at'] != $ticket['created_at']): ?>
                                    <span>🔄 Updated: <?php echo date('M j, Y', strtotime($ticket['updated_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ticket-message">
                                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                            </div>
                            <?php if ($ticket['admin_response']): ?>
                                <div class="admin-response">
                                    <strong>Admin Response:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p style="text-align: center; color: var(--text-light);">No support tickets yet</p>';
                }
                ?>
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