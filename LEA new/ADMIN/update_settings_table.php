<?php
require_once '../includes/config.php';

// SQL to alter the settings table
$sql = "ALTER TABLE settings
ADD COLUMN IF NOT EXISTS smtp_host VARCHAR(255) DEFAULT NULL AFTER system_name,
ADD COLUMN IF NOT EXISTS smtp_port VARCHAR(10) DEFAULT NULL AFTER smtp_host,
ADD COLUMN IF NOT EXISTS smtp_username VARCHAR(255) DEFAULT NULL AFTER smtp_port,
ADD COLUMN IF NOT EXISTS smtp_password VARCHAR(255) DEFAULT NULL AFTER smtp_username,
ADD COLUMN IF NOT EXISTS smtp_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls' AFTER smtp_password,
ADD COLUMN IF NOT EXISTS from_email VARCHAR(255) DEFAULT NULL AFTER smtp_encryption,
ADD COLUMN IF NOT EXISTS from_name VARCHAR(255) DEFAULT NULL AFTER from_email,
ADD COLUMN IF NOT EXISTS sms_provider ENUM('twilio', 'nexmo', 'custom') DEFAULT NULL AFTER from_name,
ADD COLUMN IF NOT EXISTS sms_api_key VARCHAR(255) DEFAULT NULL AFTER sms_provider,
ADD COLUMN IF NOT EXISTS sms_sender_id VARCHAR(50) DEFAULT NULL AFTER sms_api_key,
ADD COLUMN IF NOT EXISTS timezone VARCHAR(100) DEFAULT 'UTC' AFTER sms_sender_id";

if ($conn->multi_query($sql)) {
    echo "Settings table updated successfully!";
} else {
    echo "Error updating settings table: " . $conn->error;
}

// Close connection
$conn->close();
?> 