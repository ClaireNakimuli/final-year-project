<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

$sql = "SELECT j.*, 
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite 
        FROM jobs j 
        LEFT JOIN favorites f ON j.id = f.job_id AND f.user_id = :user_id
        WHERE j.status = 'active'";

$params = [':user_id' => $user_id];

if (!empty($search)) {
    $sql .= " AND (j.title LIKE :search OR j.company LIKE :search OR j.description LIKE :search OR j.tags LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($type)) {
    $sql .= " AND j.type = :type";
    $params[':type'] = $type;
}

if (!empty($location)) {
    $sql .= " AND j.location LIKE :location";
    $params[':location'] = "%$location%";
}

$sql .= " ORDER BY j.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($jobs);
