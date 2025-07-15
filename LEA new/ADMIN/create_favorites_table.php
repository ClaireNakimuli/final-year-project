<?php
require_once '../includes/config.php';

// Create favorites table
$sql = "CREATE TABLE IF NOT EXISTS favorites (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    job_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_favorite (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Favorites table created successfully";
} else {
    echo "Error creating favorites table: " . $conn->error;
}

$conn->close();
?> 