<?php
require_once '../includes/config.php';

// SQL to create security_settings table
$sql = "CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_password_length INT NOT NULL DEFAULT 8,
    require_special_chars TINYINT(1) NOT NULL DEFAULT 0,
    require_numbers TINYINT(1) NOT NULL DEFAULT 0,
    require_uppercase TINYINT(1) NOT NULL DEFAULT 0,
    max_login_attempts INT NOT NULL DEFAULT 5,
    lockout_duration INT NOT NULL DEFAULT 30,
    session_timeout INT NOT NULL DEFAULT 30,
    enable_2fa TINYINT(1) NOT NULL DEFAULT 0,
    enable_captcha TINYINT(1) NOT NULL DEFAULT 0,
    ip_whitelist TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "Security settings table created successfully!";
} else {
    echo "Error creating security settings table: " . $conn->error;
}

// Close connection
$conn->close();
?> 