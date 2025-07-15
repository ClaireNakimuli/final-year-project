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

// (mark as read/delete handling is commented out)

try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications
        WHERE type = 'job_alert'
          AND (
              target_type = 'all_users'
              OR (target_type = 'specific_users' AND target_id = ?)
          )
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $job_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching job alerts: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Job Alerts - LEA</title>
    <style>
        /* Your original CSS here - unchanged */
        /* ... omitted for brevity, keep your CSS from before ... */
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">🔔 Job Alerts</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="alerts-container" id="alerts-container">
            <!-- Initial static content (fallback) -->
            <?php if (count($job_alerts) > 0): ?>
                <?php foreach ($job_alerts as $alert): ?>
                    <div class="alert-box">
                        <strong><?= htmlspecialchars($alert['title']) ?></strong><br>
                        <p><?= nl2br(htmlspecialchars($alert['message'])) ?></p>
                        <small>Date: <?= htmlspecialchars($alert['created_at']) ?></small>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-alerts">
                    <h3>🔔 No Job Alerts</h3>
                    <p>You haven't received any job alerts yet. New jobs matching your preferences will appear here.</p>
                    <a href="job-search.php" class="browse-btn">🔍 Browse Jobs</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
    // Fetch job alerts every 5 seconds and update container
    async function fetchJobAlerts() {
        try {
            const response = await fetch('USERS/get_notifications.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();

            const container = document.getElementById('alerts-container');
            container.innerHTML = ''; // Clear existing alerts

            if (data.length === 0) {
                container.innerHTML = `
                    <div class="no-alerts">
                        <h3>🔔 No Job Alerts</h3>
                        <p>You haven't received any job alerts yet. New jobs matching your preferences will appear here.</p>
                        <a href="job-search.php" class="browse-btn">🔍 Browse Jobs</a>
                    </div>
                `;
                return;
            }

            data.forEach(alert => {
                const alertBox = document.createElement('div');
                alertBox.className = 'alert-box';
                alertBox.innerHTML = `
                    <strong>${escapeHtml(alert.title)}</strong><br>
                    <p>${escapeHtml(alert.message).replace(/\n/g, '<br>')}</p>
                    <small>Date: ${new Date(alert.created_at).toLocaleString()}</small>
                    <hr>
                `;
                container.appendChild(alertBox);
            });
        } catch (error) {
            console.error('Error fetching job alerts:', error);
        }
    }

    // Simple HTML escaping to avoid XSS in JS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Initial fetch on page load
    fetchJobAlerts();

    // Poll every 5 seconds
    setInterval(fetchJobAlerts, 5000);
</script>
</body>
</html>
