<?php
require_once '../includes/config.php';

function createJobAlert($job_id) {
    global $conn;
    
    // Get all active users
    $sql = "SELECT id FROM users WHERE status = 'active'";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($user = $result->fetch_assoc()) {
            // Create alert for each user
            $stmt = $conn->prepare("INSERT INTO job_alerts (user_id, job_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user['id'], $job_id);
            $stmt->execute();
        }
    }
}

// Example usage:
// When a new job is posted, call:
// createJobAlert($new_job_id);
?> 