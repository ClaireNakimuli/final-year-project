<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle favorite/unfavorite action
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    $action = $_POST['action'];
    
    if ($action === 'favorite') {
        $sql = "INSERT INTO favorites (user_id, job_id) VALUES (:user_id, :job_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($action === 'unfavorite') {
        $sql = "DELETE FROM favorites WHERE user_id = :user_id AND job_id = :job_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
    exit();
}

// Initialize search and filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build the SQL query
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

// Prepare and execute the query
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search - LEA</title>
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

        .search-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .filter-select {
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            background: var(--white);
        }

        .search-btn {
            background: linear-gradient(to right, #02486b 0%, #02486b 100%);
            color: var(--white);
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: opacity 0.3s ease;
        }

        .search-btn:hover {
            opacity: 0.9;
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .search-form {
                grid-template-columns: 1fr;
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
        <div class="search-section">
            <form class="search-form" method="GET" action="">
                <input type="text" name="search" class="search-input" placeholder="🔍 Search jobs, companies, or keywords..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="type" class="filter-select">
                    <option value="">🌐 All Types</option>
                    <option value="Full-time" <?php echo $type === 'Full-time' ? 'selected' : ''; ?>>⏰ Full-time</option>
                    <option value="Part-time" <?php echo $type === 'Part-time' ? 'selected' : ''; ?>>🕐 Part-time</option>
                    <option value="Contract" <?php echo $type === 'Contract' ? 'selected' : ''; ?>>📝 Contract</option>
                    <option value="Remote" <?php echo $type === 'Remote' ? 'selected' : ''; ?>>🏠 Remote</option>
                </select>
                <input type="text" name="location" class="search-input" placeholder="📍 Location" value="<?php echo htmlspecialchars($location); ?>">
                <button type="submit" class="search-btn">🔍 Search</button>
            </form>
        </div>

        <div class="jobs-container">
            <?php
            if (count($jobs) > 0) {
                foreach ($jobs as $job) {
                    ?>
                    <div class="job-card">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <input type="hidden" name="action" value="<?php echo $job['is_favorite'] ? 'unfavorite' : 'favorite'; ?>">
                            <button type="submit" class="favorite-btn" title="<?php echo $job['is_favorite'] ? 'Remove from favorites' : 'Add to favorites'; ?>">
                                <?php echo $job['is_favorite'] ? '❤️' : '🤍'; ?>
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
                                <span>📅 <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
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
                    <h3>🔍 No Jobs Found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <script>
    async function fetchJobs() {
        const container = document.querySelector('.jobs-container');
        try {
            const search = encodeURIComponent(document.querySelector('input[name="search"]').value);
            const type = encodeURIComponent(document.querySelector('select[name="type"]').value);
            const location = encodeURIComponent(document.querySelector('input[name="location"]').value);

            const response = await fetch(`get_jobs.php?search=${search}&type=${type}&location=${location}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const jobs = await response.json();

            if (jobs.length === 0) {
                container.innerHTML = `
                <div class="no-jobs">
                    <h3>🔍 No Jobs Found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                </div>`;
                return;
            }

            let html = '';
            jobs.forEach(job => {
                const tagsHtml = job.tags ? job.tags.split(',').map(tag => `<span class="tag">🏷️ ${tag.trim()}</span>`).join('') : '';
                const createdDate = new Date(job.created_at).toLocaleDateString();
                html += `
                <div class="job-card">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="job_id" value="${job.id}">
                        <input type="hidden" name="action" value="${job.is_favorite ? 'unfavorite' : 'favorite'}">
                        <button type="submit" class="favorite-btn" title="${job.is_favorite ? 'Remove from favorites' : 'Add to favorites'}">
                            ${job.is_favorite ? '❤️' : '🤍'}
                        </button>
                    </form>
                    <div class="job-header">
                        <h3 class="job-title">${job.title}</h3>
                        <p class="company-name">🏢 ${job.company}</p>
                    </div>
                    <div class="job-details">
                        <div class="detail-item"><span>💼 ${job.type}</span></div>
                        <div class="detail-item"><span>📍 ${job.location}</span></div>
                        <div class="detail-item"><span>⏳ ${job.experience}</span></div>
                        <div class="detail-item"><span>📅 ${createdDate}</span></div>
                    </div>
                    <p class="job-description">${job.description}</p>
                    <div class="job-tags">${tagsHtml}</div>
                    <div class="job-footer">
                        <span class="salary">💰 ${job.salary}</span>
                        <a href="apply-job.php?id=${job.id}" class="apply-btn">✨ Apply Now</a>
                    </div>
                </div>`;
            });

            container.innerHTML = html;
        } catch (error) {
            console.error('Failed to fetch jobs:', error);
        }
    }

    // Update jobs every 15 seconds
    setInterval(fetchJobs, 15000);

    // Update jobs when search form submits (without reload)
    document.querySelector('.search-form').addEventListener('submit', e => {
        e.preventDefault();
        fetchJobs();
    });

    // Initial load after page load
    window.addEventListener('load', fetchJobs);
</script>
</body>
</html> 