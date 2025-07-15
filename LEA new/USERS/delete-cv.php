<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if CV ID is provided
if (!isset($_GET['id'])) {
    header('Location: upload-cv.php');
    exit();
}

$cv_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get CV information
$sql = "SELECT * FROM user_documents WHERE id = ? AND user_id = ? AND document_type = 'cv'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cv_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: upload-cv.php');
    exit();
}

$cv = $result->fetch_assoc();

// Delete file from server
if (file_exists($cv['file_path'])) {
    unlink($cv['file_path']);
}

// Delete from database
$delete_sql = "DELETE FROM user_documents WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("ii", $cv_id, $user_id);

if ($stmt->execute()) {
    header('Location: upload-cv.php?success=1');
} else {
    header('Location: upload-cv.php?error=1');
}
exit();
?> 