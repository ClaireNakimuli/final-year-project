<?php
// User Sidebar component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';

// Initialize stats with default values
$applications_count = 0;
$profile_views = 0;
$saved_jobs_count = 0;
$interviews_count = 0;

// Fetch user statistics if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get applications count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $applications_count = $result['count'];
        
        // Get saved jobs count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $saved_jobs_count = $result['count'];
        
        // Get interviews count (applications with status 'shortlisted' or 'hired')
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE user_id = :user_id AND status IN ('shortlisted', 'hired')");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $interviews_count = $result['count'];
        
        // Profile views (placeholder - you can implement this when you add profile view tracking)
        $profile_views = 0; // This would come from a profile_views table when implemented
        
    } catch (Exception $e) {
        // Keep default values if there's an error
        error_log("Error fetching user stats: " . $e->getMessage());
    }
}

// Fetch system name for header
$system_name = 'LAIR';
try {
    $settings_sql = "SELECT system_name FROM settings LIMIT 1";
    $settings_result = $conn->query($settings_sql);
    if ($settings_result && $settings_result->rowCount() > 0) {
        $settings = $settings_result->fetch(PDO::FETCH_ASSOC);
        $system_name = $settings['system_name'] ?: 'LAIR';
    }
} catch (Exception $e) {
    // Keep default name if there's an error
    $system_name = 'LAIR';
}
?>
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

.sidebar {
    width: var(--sidebar-width);
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
    padding: 2rem;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.sidebar-header {
    text-align: center;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h2 {
    color: var(--white);
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: 800;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.sidebar-header p {
    color: var(--white);
    opacity: 0.9;
    font-size: 0.9rem;
}

.user-profile {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    text-align: center;
    color: var(--white);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.user-profile:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.user-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.sidebar-menu li {
    margin-bottom: 0.5rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    color: var(--white);
    opacity: 0.9;
    text-decoration: none;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-menu a:hover, .sidebar-menu a.active {
    background: rgba(255, 255, 255, 0.2);
    color: var(--white);
    opacity: 1;
    transform: translateX(5px);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.sidebar-menu a span {
    margin-left: 1rem;
    font-size: 0.95rem;
}

.sidebar-stats {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 1rem;
    color: var(--white);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-stats h4 {
    color: var(--white);
    font-size: 1.2rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-item span:first-child {
    color: var(--white);
    opacity: 0.9;
    font-size: 0.9rem;
}

.stat-item span:last-child {
    color: var(--white);
    font-weight: 600;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 1.25rem;
    left: 1.25rem;
    z-index: 1001;
    background: var(--primary-blue);
    border: 1.5px solid var(--primary-blue-light);
    padding: 0.75rem;
    border-radius: 0.75rem;
    cursor: pointer;
    font-size: 1.5rem;
    color: var(--white);
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(2,72,107,0.07);
}

.mobile-menu-btn:hover {
    background: var(--primary-blue-light);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(2,72,107,0.2);
}

.mobile-menu-btn:active {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.open {
        transform: translateX(0);
    }

    .mobile-menu-btn {
        display: block;
    }
}
</style>

<button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><?php echo htmlspecialchars($system_name); ?></h2>
        <p>User Dashboard</p>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">👤</div>
        <h4>Welcome Back!</h4>
        <p>Job Seeker</p>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active" onclick="setActiveMenu(this)">🏠 <span>Dashboard</span></a></li>
        <li><a href="job-search.php" onclick="setActiveMenu(this)">🔍 <span>Job Search</span></a></li>
        <li><a href="applications.php" onclick="setActiveMenu(this)">📋 <span>My Applications</span></a></li>
        <li><a href="profile.php" onclick="setActiveMenu(this)">💼 <span>My Profile</span></a></li>
        <li><a href="saved-jobs.php" onclick="setActiveMenu(this)">🔖 <span>Saved Jobs</span></a></li>
        <li><a href="analytics.php" onclick="setActiveMenu(this)">📊 <span>Analytics</span></a></li>
        <li><a href="job-alerts.php" onclick="setActiveMenu(this)">🎯 <span>Job Alerts</span></a></li>
        <li><a href="resources.php" onclick="setActiveMenu(this)">📚 <span>Resources</span></a></li>
        <li><a href="settings.php" onclick="setActiveMenu(this)">⚙️ <span>Settings</span></a></li>
        <li><a href="support.php" onclick="setActiveMenu(this)">📞 <span>Support</span></a></li>
        <li><a href="logout.php" onclick="setActiveMenu(this)">🚪 <span>Logout</span></a></li>
    </ul>

    <div class="sidebar-stats">
        <h4>📈 Your Stats</h4>
        <div class="stat-item">
            <span>Applications:</span>
            <span><?php echo $applications_count; ?></span>
        </div>
        <div class="stat-item">
            <span>Profile Views:</span>
            <span><?php echo $profile_views; ?></span>
        </div>
        <div class="stat-item">
            <span>Saved Jobs:</span>
            <span><?php echo $saved_jobs_count; ?></span>
        </div>
        <div class="stat-item">
            <span>Interviews:</span>
            <span><?php echo $interviews_count; ?></span>
        </div>
    </div>
    
</div>

<script>
function toggleSidebar() {
    try {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) {
            console.error('Sidebar element not found');
            return;
        }
        
        requestAnimationFrame(() => {
            sidebar.classList.toggle('open');
            const isOpen = sidebar.classList.contains('open');
            sidebar.setAttribute('aria-expanded', isOpen);
            
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (menuBtn) {
                menuBtn.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            }
        });
    } catch (error) {
        console.error('Error toggling sidebar:', error);
    }
}

function setActiveMenu(element) {
    try {
        if (!element || !(element instanceof HTMLElement)) {
            console.error('Invalid element provided to setActiveMenu');
            return;
        }

        const menuItems = document.querySelectorAll('.sidebar-menu a');
        if (!menuItems.length) {
            console.error('No menu items found');
            return;
        }

        requestAnimationFrame(() => {
            menuItems.forEach(link => {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            });

            element.classList.add('active');
            element.setAttribute('aria-selected', 'true');

            const href = element.getAttribute('href');
            if (href && href !== '#') {
                window.location.hash = href;
            }
        });
    } catch (error) {
        console.error('Error setting active menu:', error);
    }
}
</script>
