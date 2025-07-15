<?php
// Sidebar component with liquid glassy effect
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Super Admin';

// Fetch system name from settings table
$system_name = 'LAIR'; // Default fallback
try {
    require_once '../includes/config.php';
    $stmt = $conn->prepare("SELECT system_name FROM settings LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $system_name = $row['system_name'] ?: 'LAIR';
    }
} catch (Exception $e) {
    // Keep default if there's an error
    $system_name = 'LAIR';
}
?>
<style>
    :root {
        --primary-green: #064239;
        --primary-green-light: #0a5a4a;
        --primary-green-dark: #04352c;
        --accent-green: #0d9488;
        --accent-green-light: #14b8a6;
        --white: #ffffff;
        --gray-light: #f8f9fa;
        --gray-border: #e9ecef;
        --text-dark: #212529;
        --text-medium: #495057;
        --text-light: #6c757d;
        --success: #198754;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #0dcaf0;
    }
    
    .sidebar {
        width: 300px;
        height: 100vh;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border-right: 1.5px solid rgba(255, 255, 255, 0.25);
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        overflow-y: auto;
        transition: all 0.3s ease;
        box-shadow: 4px 0 20px rgba(6, 66, 57, 0.1);
    }
    
    .sidebar-header {
        padding: 30px 25px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(6, 66, 57, 0.1);
        background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
        color: var(--white);
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent);
        pointer-events: none;
    }
    
    .sidebar-logo {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    
    .sidebar-logo i {
        color: var(--accent-green-light);
        text-shadow: 0 0 20px rgba(20, 184, 166, 0.5);
    }
    
    .sidebar-subtitle {
        font-size: 0.9rem;
        opacity: 0.9;
        font-weight: 500;
    }
    
    .sidebar-nav {
        padding: 20px 0;
    }
    
    .nav-section {
        margin-bottom: 30px;
    }
    
    .nav-section-title {
        padding: 0 25px 10px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid rgba(6, 66, 57, 0.1);
        margin-bottom: 15px;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        padding: 12px 25px;
        color: var(--text-medium);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        font-weight: 500;
        border-left: 3px solid transparent;
    }
    
    .nav-item:hover {
        background: rgba(6, 66, 57, 0.05);
        color: var(--primary-green);
        border-left-color: var(--accent-green);
        transform: translateX(5px);
    }
    
    .nav-item.active {
        background: linear-gradient(90deg, rgba(6, 66, 57, 0.1), transparent);
        color: var(--primary-green);
        border-left-color: var(--accent-green);
        font-weight: 600;
    }
    
    .nav-item i {
        width: 20px;
        margin-right: 12px;
        font-size: 1.1rem;
        text-align: center;
    }
    
    .nav-item .nav-text {
        flex: 1;
    }
    
    .nav-item .nav-badge {
        background: var(--accent-green);
        color: var(--white);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: auto;
    }
    
    .sidebar-footer {
        padding: 20px 25px;
        border-top: 1px solid rgba(6, 66, 57, 0.1);
        margin-top: auto;
    }
    
    .admin-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: rgba(6, 66, 57, 0.05);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .admin-profile:hover {
        background: rgba(6, 66, 57, 0.1);
        transform: translateY(-2px);
    }
    
    .admin-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: var(--white);
        box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
    }
    
    .admin-info {
        flex: 1;
    }
    
    .admin-name {
        font-weight: 600;
        color: var(--primary-green);
        font-size: 0.95rem;
    }
    
    .admin-role {
        font-size: 0.8rem;
        color: var(--text-light);
        margin-top: 2px;
    }
    
    .logout-btn {
        background: none;
        border: none;
        color: var(--text-light);
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger);
        transform: scale(1.1);
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }
        
        .sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(6, 66, 57, 0.2);
        }
    }
    
    /* Scrollbar styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar::-webkit-scrollbar-track {
        background: rgba(6, 66, 57, 0.05);
    }
    
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(6, 66, 57, 0.2);
        border-radius: 3px;
    }
    
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(6, 66, 57, 0.3);
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-crown"></i>
            <span><?php echo htmlspecialchars($system_name); ?></span>
        </div>
        <div class="sidebar-subtitle">Admin Portal</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="DASHBOARD.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'DASHBOARD.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="ANALYTICS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'ANALYTICS.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span class="nav-text">Analytics</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <a href="USERS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'USERS.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="nav-text">Users</span>
            </a>
            <a href="JOBS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'JOBS.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i>
                <span class="nav-text">Jobs</span>
            </a>
            <a href="COMPANIES.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'COMPANIES.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span class="nav-text">Companies</span>
            </a>
            <a href="APPLICATIONS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'APPLICATIONS.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span class="nav-text">Applications</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Finance</div>
            <a href="PAYMENTS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'PAYMENTS.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span class="nav-text">Payments</span>
            </a>
            <a href="REPORTS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'REPORTS.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span class="nav-text">Reports</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <a href="NOTIFICATIONS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'NOTIFICATIONS.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span class="nav-text">Notifications</span>
            </a>
            <a href="SECURITY.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'SECURITY.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i>
                <span class="nav-text">Security</span>
            </a>
            <a href="SYSTEM_SETTINGS.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'SYSTEM_SETTINGS.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </a>
            <a href="LOCALIZATION.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'LOCALIZATION.php' ? 'active' : ''; ?>">
                <i class="fas fa-globe"></i>
                <span class="nav-text">Localization</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="PROFILE.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'PROFILE.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span class="nav-text">Profile</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($admin_username ?? 'Admin'); ?></div>
                <div class="admin-role">Super Administrator</div>
            </div>
            <button class="logout-btn" onclick="logout()" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>
</div>

<script>
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    // Add active class based on current page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href === currentPage) {
                item.classList.add('active');
            }
        });
    });
</script> 