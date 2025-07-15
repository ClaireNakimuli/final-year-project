<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all active resources
$sql = "SELECT * FROM resources WHERE status = 'active' ORDER BY created_at DESC";
try {
    $result = $conn->query($sql);
    $resources = $result->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error executing statement: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - LEA</title>
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
        }

        .page-title {
            color: var(--text-dark);
            font-size: 1.5em;
        }

        .resources-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .resource-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .resource-card:hover {
            transform: translateY(-5px);
        }

        .resource-header {
            margin-bottom: 15px;
        }

        .resource-title {
            font-size: 1.2em;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .resource-category {
            display: inline-block;
            background: var(--light-gray);
            color: var(--text-light);
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }

        .resource-description {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .resource-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .resource-type {
            color: var(--text-light);
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .download-btn {
            background: linear-gradient(to right, #02486b 0%, #02486b 100%);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .download-btn:hover {
            opacity: 0.9;
        }

        .no-resources {
            text-align: center;
            padding: 40px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            grid-column: 1 / -1;
        }

        .no-resources h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .no-resources p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .category-filter {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-title {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: var(--light-gray);
            color: var(--text-dark);
            padding: 5px 15px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .resources-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">📚 Resources</h1>
        </div>

        <div class="category-filter">
            <h3 class="filter-title">Filter by Category</h3>
            <div class="filter-options">
                <button class="filter-btn active" data-category="all">All</button>
                <button class="filter-btn" data-category="resume">Resume</button>
                <button class="filter-btn" data-category="interview">Interview</button>
                <button class="filter-btn" data-category="career">Career</button>
                <button class="filter-btn" data-category="skills">Skills</button>
            </div>
        </div>

        <div class="resources-container">
            <?php
            if (count($resources) > 0) {
                foreach($resources as $resource) {
                    $file_icon = getFileIcon($resource['file_type']);
                    ?>
                    <div class="resource-card" data-category="<?php echo strtolower($resource['category']); ?>">
                        <div class="resource-header">
                            <h3 class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></h3>
                            <span class="resource-category"><?php echo htmlspecialchars($resource['category']); ?></span>
                        </div>
                        <p class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></p>
                        <div class="resource-footer">
                            <span class="resource-type">
                                <?php echo $file_icon; ?> <?php echo strtoupper($resource['file_type']); ?>
                            </span>
                            <a href="download-resource.php?id=<?php echo $resource['id']; ?>" class="download-btn">
                                ⬇️ Download
                            </a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-resources">
                    <h3>📚 No Resources Available</h3>
                    <p>Check back later for new resources and materials.</p>
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

        // Category filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const category = button.dataset.category;
                const resources = document.querySelectorAll('.resource-card');

                resources.forEach(resource => {
                    if (category === 'all' || resource.dataset.category === category) {
                        resource.style.display = 'block';
                    } else {
                        resource.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
function getFileIcon($file_type) {
    switch(strtolower($file_type)) {
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
        case 'ppt':
        case 'pptx':
            return '📊';
        case 'xls':
        case 'xlsx':
            return '📈';
        case 'zip':
        case 'rar':
            return '📦';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return '🖼️';
        default:
            return '📎';
    }
}
?> 