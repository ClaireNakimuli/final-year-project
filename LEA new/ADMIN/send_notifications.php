<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
include '../includes/config.php';  // your DB connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
} else {
    die("Invalid request");
}
$stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
$stmt->execute([$notification_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    die("Notification not found.");
}
$recipient_type = $notification['recipient_type'];
$user_query = "";

switch ($recipient_type) {
    case 'all_users':
        $user_query = "SELECT id FROM users";
        break;
    case 'job_seekers':
        $user_query = "SELECT id FROM users WHERE user_type = 'job_seeker'";
        break;
    case 'employers':
        $user_query = "SELECT id FROM users WHERE user_type = 'employer'";
        break;
    case 'specific_users':
        // Implement logic here for specific users (optional)
        $user_query = "SELECT id FROM users WHERE 0"; // no users for now
        break;
    default:
        die("Invalid recipient type.");
}

$users = $conn->query($user_query)->fetchAll(PDO::FETCH_ASSOC);

if (!$users) {
    die("No users found for the recipient type.");
}
$insert_stmt = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id, status) VALUES (?, ?, 'unread')");

$sent_count = 0;
foreach ($users as $user) {
    try {
        $insert_stmt->execute([$user['id'], $notification_id]);
        $sent_count++;
    } catch (Exception $e) {
        // Optionally log error or continue on failure
        continue;
    }
}
$update_stmt = $conn->prepare("UPDATE notifications SET status = 'sent', sent_count = ? WHERE id = ?");
$update_stmt->execute([$sent_count, $notification_id]);
echo "Notification sent to {$sent_count} users successfully.";
