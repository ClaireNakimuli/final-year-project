<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$job_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get job details
$job_sql = "SELECT * FROM jobs WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($job_sql);
$stmt->bindParam(1, $job_id, PDO::PARAM_INT); // 1 refers to the first `?`
$stmt->execute();
$job_result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job_result) {
    header('Location: dashboard.php');
    exit();
}

$job = $job_result;

// Get user's CV documents
$cv_sql = "SELECT * FROM user_documents WHERE user_id = :user_id AND document_type = 'cv' ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($cv_sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$cv_result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all CVs

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cv_id']) || empty($_POST['cv_id'])) {
        $error = "Please select a CV";
    } else {
        // Check if user has already applied
        $check_sql = "SELECT id FROM applications WHERE job_id = :job_id AND user_id = :user_id";
        $stmt = $conn->prepare($check_sql);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $check_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($check_result) {
            $error = "You have already applied for this job";
        } else {
            // Insert application, including CV id and cover letter
            $insert_sql = "INSERT INTO applications (job_id, user_id, cv_id, notes) VALUES (:job_id, :user_id, :cv_id, :notes)";
            $stmt = $conn->prepare($insert_sql);
            $cover_letter = $_POST['cover_letter'] ?? '';
            $cv_id = $_POST['cv_id'];
            $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':cv_id', $cv_id, PDO::PARAM_INT);
            $stmt->bindParam(':notes', $cover_letter, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $success = "Application submitted successfully!";
            } else {
                $error = "Error submitting application. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?> - LEA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-blue: #02486b;
            --primary-blue-light: #036fa3;
            --white: #fff;
            --gray-light: #f5f6fa;
            --gray-border: #e0e0e0;
            --text-dark: #222;
            --text-medium: #444;
            --sidebar-width: 280px;
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
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px;
            min-height: 100vh;
            background: var(--gray-light);
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .job-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-border);
        }
        .job-title {
            font-size: 1.8rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }
        .company-name {
            color: var(--text-medium);
            font-size: 1.2rem;
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
        select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray-border);
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea {
            min-height: 150px;
            resize: vertical;
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
        .upload-cv {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="card">
                <div class="job-header">
                    <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="company-name"><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company']); ?></div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="cv_id">Select CV</label>
                        <select name="cv_id" id="cv_id" required>
                            <option value="">Select a CV</option>
                            <?php foreach ($cv_result as $cv): ?>
                                <option value="<?php echo htmlspecialchars($cv['id']); ?>">
                                    <?php echo htmlspecialchars($cv['document_name'] ?? $cv['file_name']); ?>
                                    (Uploaded: <?php echo date('M d, Y', strtotime($cv['uploaded_at'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cover_letter">Cover Letter</label>
                        <textarea name="cover_letter" id="cover_letter" placeholder="Write your cover letter here..."></textarea>
                    </div>

                    <div class="upload-cv">
                        <p>Don't have a CV uploaded? <a href="upload-cv.php" class="btn">Upload CV</a></p>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">Submit Application</button>
                        <a href="dashboard.php" class="btn" style="background: var(--text-medium); margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
