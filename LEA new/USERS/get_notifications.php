<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, title, message, created_at 
    FROM notifications 
    WHERE (user_id = ? OR user_id = 0) 
    AND is_read = 0 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);
