<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload";
    } else {
        $file = $_FILES['cv'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];

        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed = array('pdf', 'doc', 'docx');

        if (!in_array($file_ext, $allowed)) {
            $error = "Only PDF and Word documents are allowed";
        } elseif ($file_size > 5242880) { // 5MB max
            $error = "File size must be less than 5MB";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/cvs/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $new_file_name = uniqid('cv_') . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                // Insert into database
                $sql = "INSERT INTO user_documents (user_id, document_type, file_path, file_name) VALUES (:user_id, 'cv', :file_path, :file_name)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
                $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    $success = "CV uploaded successfully!";
                } else {
                    $error = "Error saving CV information. Please try again.";
                    // Delete uploaded file if database insert fails
                    unlink($file_path);
                }
            } else {
                $error = "Error uploading file. Please try again.";
            }
        }
    }
}

// Get user's existing CVs
$sql = "SELECT * FROM user_documents WHERE user_id = :user_id AND document_type = 'cv' ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Upload CV - LAIR</title>
    <style>
        /* Your existing styles here */
        :root {
            --primary-blue: #02486b;
            --primary-blue-light: #036fa3;
            --white: #fff;
            --gray-light: #f5f6fa;
            --gray-border: #e0e0e0;
            --text-dark: #222;
            --text-medium: #444;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gray-light);
            color: var(--text-dark);
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-border);
        }
        .page-title {
            font-size: 1.8rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }
        input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray-border);
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: var(--primary-blue-light);
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .cv-list {
            margin-top: 2rem;
        }
        .cv-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .cv-info {
            flex: 1;
        }
        .cv-name {
            font-weight: 500;
            color: var(--text-dark);
        }
        .cv-date {
            font-size: 0.9rem;
            color: var(--text-medium);
        }
        .cv-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="page-header">
                <h1 class="page-title">Upload CV</h1>
                <p>Upload your CV to apply for jobs</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="cv">Select CV File (PDF or Word Document)</label>
                    <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx" required />
                    <small style="color: var(--text-medium);">Maximum file size: 5MB</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">Upload CV</button>
                    <a href="dashboard.php" class="btn" style="background: var(--gray-border); color: var(--text-dark); margin-left: 1rem;">Back to Dashboard</a>
                </div>
            </form>

            <?php if (count($result) > 0): ?>
                <div class="cv-list">
                    <h2>Your CVs</h2>
                    <?php foreach ($result as $cv): ?>
                        <div class="cv-item">
                            <div class="cv-info">
                                <div class="cv-name"><?php echo htmlspecialchars($cv['file_name']); ?></div>
                                <div class="cv-date">Uploaded: <?php echo date('M d, Y', strtotime($cv['uploaded_at'])); ?></div>
                            </div>
                            <div class="cv-actions">
                                <a href="<?php echo htmlspecialchars($cv['file_path']); ?>" target="_blank" class="btn">View</a>
                                <a href="delete-cv.php?id=<?php echo $cv['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this CV?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
