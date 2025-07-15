<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle unfavorite action
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    $action = $_POST['action'];
    
    if ($action === 'unfavorite') {
        $sql = "DELETE FROM favorites WHERE user_id = :user_id AND job_id = :job_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get user's favorited jobs
$sql = "SELECT j.*, f.created_at as favorited_at 
        FROM jobs j 
        INNER JOIN favorites f ON j.id = f.job_id 
        WHERE f.user_id = :user_id AND j.status = 'active'
        ORDER BY f.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $saved_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs - LEA</title>
    <style>
        :root {
            --primary-color: #02486b;
            --secondary-color: #036fa3;
            --accent-color: #2196F3;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: var(--text-dark);
            font-size: 1.5em;
        }

        .jobs-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .job-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .job-card:hover {
            transform: translateY(-5px);
        }

        .favorite-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
        }

        .job-header {
            margin-bottom: 15px;
            padding-right: 40px;
        }

        .job-title {
            font-size: 1.2em;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .company-name {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .job-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-light);
            font-size: 0.9em;
        }

        .job-description {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }

        .tag {
            background: var(--light-gray);
            color: var(--text-light);
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .salary {
            color: var(--primary-color);
            font-weight: 600;
        }

        .apply-btn {
            background: linear-gradient(to right, #02486b 0%, #02486b 100%);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: opacity 0.3s ease;
        }

        .apply-btn:hover {
            opacity: 0.9;
        }

        .no-jobs {
            text-align: center;
            padding: 40px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .no-jobs h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .no-jobs p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            background: linear-gradient(to right, #02486b 0%, #02486b 100%);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .browse-btn:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .jobs-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">❤️ Saved Jobs</h1>
        </div>

        <div class="jobs-container">
            <?php
            if (count($saved_jobs) > 0) {
                foreach ($saved_jobs as $job) {
                    ?>
                    <div class="job-card">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <input type="hidden" name="action" value="unfavorite">
                            <button type="submit" class="favorite-btn" title="Remove from favorites">
                                ❤️
                            </button>
                        </form>
                        <div class="job-header">
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p class="company-name">🏢 <?php echo htmlspecialchars($job['company']); ?></p>
                        </div>
                        <div class="job-details">
                            <div class="detail-item">
                                <span>💼 <?php echo htmlspecialchars($job['type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>📍 <?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>⏳ <?php echo htmlspecialchars($job['experience']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>📅 Saved on <?php echo date('M j, Y', strtotime($job['favorited_at'])); ?></span>
                            </div>
                        </div>
                        <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                        <div class="job-tags">
                            <?php
                            if (!empty($job['tags'])) {
                                $tags = explode(',', $job['tags']);
                                foreach($tags as $tag) {
                                    echo '<span class="tag">🏷️ ' . htmlspecialchars(trim($tag)) . '</span>';
                                }
                            }
                            ?>
                        </div>
                        <div class="job-footer">
                            <span class="salary">💰 <?php echo htmlspecialchars($job['salary']); ?></span>
                            <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="apply-btn">✨ Apply Now</a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-jobs">
                    <h3>❤️ No Saved Jobs Yet</h3>
                    <p>Start saving jobs you're interested in by clicking the heart icon on any job listing.</p>
                    <a href="job-search.php" class="browse-btn">🔍 Browse Jobs</a>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
        }
    </script>
</body>
</html> 