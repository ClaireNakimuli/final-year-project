<?php
require_once '../includes/config.php';

// Create job_applications table
$sql = "CREATE TABLE IF NOT EXISTS job_applications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    job_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    cv_id INT(11) NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cv_id) REFERENCES user_documents(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Job applications table created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 