<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

// Database connection
require_once '../includes/config.php';

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = $_POST['user_id'];
    
    try {
        // Delete user documents first
        $delete_docs = $conn->prepare("DELETE FROM user_documents WHERE user_id = ?");
        $delete_docs->execute([$user_id]);
        
        // Delete user applications
        $delete_apps = $conn->prepare("DELETE FROM applications WHERE user_id = ?");
        $delete_apps->execute([$user_id]);
        
        // Delete user
        $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        
        if ($delete_user->execute([$user_id])) {
            $success = "User deleted successfully.";
        } else {
            $error = "Error deleting user.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_document':
                $user_id = $_POST['user_id'];
                $document_type = $_POST['document_type'];
                $file = $_FILES['document'];
                
                // Validate file type
                $allowed_types = ['application/pdf'];
                if (!in_array($file['type'], $allowed_types)) {
                    $error = "Only PDF files are allowed.";
                    break;
                }
                
                // Create upload directory if it doesn't exist
                $upload_dir = dirname(__DIR__) . "/uploads/documents/{$user_id}/";
                if (!file_exists($upload_dir)) {
                    if (!@mkdir($upload_dir, 0777, true)) {
                        $error = "Failed to create upload directory. Please run setup_uploads.php first.";
                        break;
                    }
                    if (!@chmod($upload_dir, 0777)) {
                        $error = "Failed to set directory permissions. Please run setup_uploads.php first.";
                        break;
                    }
                }
                
                // Check if directory is writable
                if (!is_writable($upload_dir)) {
                    $error = "Upload directory is not writable. Please run setup_uploads.php first.";
                    break;
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . basename($file['name']);
                $filepath = $upload_dir . $filename;
                
                // Check if we can write to the directory
                if (!is_writable(dirname($filepath))) {
                    $error = "Cannot write to upload directory. Please run setup_uploads.php first.";
                    break;
                }
                
                if (@move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Update database with relative path
                    $relative_path = "uploads/documents/{$user_id}/" . $filename;
                    
                    try {
                        // First, check if the user_documents table exists
                        $check_table = $conn->query("SHOW TABLES LIKE 'user_documents'");
                        if ($check_table->rowCount() == 0) {
                            // Create the table if it doesn't exist
                            $create_table = "CREATE TABLE IF NOT EXISTS user_documents (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT NOT NULL,
                                document_type ENUM('cv', 'academic', 'certification') NOT NULL,
                                file_path VARCHAR(255) NOT NULL,
                                file_name VARCHAR(255) NOT NULL,
                                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES users(id)
                            )";
                            if (!$conn->exec($create_table)) {
                                $error = "Error creating user_documents table.";
                                if (file_exists($filepath)) {
                                    @unlink($filepath);
                                }
                                break;
                            }
                        }
                        
                        // Insert document record
                        $stmt = $conn->prepare("INSERT INTO user_documents (user_id, document_type, file_path, file_name, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
                        
                        if ($stmt->execute([$user_id, $document_type, $relative_path, $file['name']])) {
                            // Update user's document path
                            $column = $document_type . "_path";
                            
                            // Check if the column exists in users table
                            $check_column = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
                            if ($check_column->rowCount() == 0) {
                                // Add the column if it doesn't exist
                                $alter_table = "ALTER TABLE users ADD COLUMN $column VARCHAR(255)";
                                if (!$conn->exec($alter_table)) {
                                    $error = "Error adding column to users table.";
                                    if (file_exists($filepath)) {
                                        @unlink($filepath);
                                    }
                                    break;
                                }
                            }
                            
                            $update_stmt = $conn->prepare("UPDATE users SET $column = ? WHERE id = ?");
                            if (!$update_stmt->execute([$relative_path, $user_id])) {
                                $error = "Error updating user record.";
                                if (file_exists($filepath)) {
                                    @unlink($filepath);
                                }
                                break;
                            }
                            $success = "Document uploaded successfully.";
                        } else {
                            $error = "Error saving document record.";
                            if (file_exists($filepath)) {
                                @unlink($filepath);
                            }
                        }
                    } catch (PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                        if (file_exists($filepath)) {
                            @unlink($filepath);
                        }
                    }
                } else {
                    $error = "Error uploading file. Please run setup_uploads.php first.";
                }
                break;
        }
    }
}

// Fetch users with their documents
$users_query = "SELECT u.*, 
    (SELECT file_path FROM user_documents WHERE user_id = u.id AND document_type = 'cv' ORDER BY uploaded_at DESC LIMIT 1) as latest_cv,
    (SELECT file_path FROM user_documents WHERE user_id = u.id AND document_type = 'academic' ORDER BY uploaded_at DESC LIMIT 1) as latest_academic,
    (SELECT file_path FROM user_documents WHERE user_id = u.id AND document_type = 'certification' ORDER BY uploaded_at DESC LIMIT 1) as latest_certification
    FROM users u ORDER BY u.created_at DESC";
$users_result = $conn->query($users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🦁 LAIR User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #064239;
            --primary-green-light: #0a5a4a;
            --primary-green-dark: #04352c;
            --white: #ffffff;
            --gray-light: #f8f9fa;
            --gray-border: #e9ecef;
            --text-dark: #212529;
            --text-medium: #495057;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, var(--primary-green) 0%, var(--primary-green) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
        }
        .main-content {
            margin-left: 300px;
            flex: 1;
            padding: 30px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 1s ease-out;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 30px;
            border-radius: 25px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
        }
        .header h1 {
            color: var(--primary-green);
            font-size: 3rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .header p {
            color: var(--text-medium);
            font-size: 1.2rem;
        }
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
        }
        .search-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1.5px solid var(--gray-border);
            border-radius: 15px;
            font-size: 1rem;
            background: var(--white);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(6, 66, 57, 0.1);
        }
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-medium);
            font-size: 1.1rem;
        }
        .new-user-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: var(--primary-green);
            color: var(--white);
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            border: 1.5px solid var(--primary-green);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.2);
        }
        .new-user-btn:hover {
            transform: translateY(-2px);
            background: var(--primary-green-light);
            box-shadow: 0 8px 25px rgba(6, 66, 57, 0.3);
            color: var(--white);
        }
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .user-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(6, 66, 57, 0.2);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            border: 2px solid var(--primary-green-light);
        }
        .user-details h3 {
            font-size: 1.2rem;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        .user-details p {
            color: var(--text-medium);
            font-size: 0.9rem;
        }
        .user-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .user-status.active {
            background: var(--primary-green);
            color: var(--white);
        }
        .user-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .user-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .user-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-danger {
            background: #dc3545;
            color: var(--white);
            border: 1.5px solid #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        .btn-edit {
            background: var(--primary-green);
            color: var(--white);
            border: 1.5px solid var(--primary-green);
        }
        .btn-edit:hover {
            background: var(--primary-green-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 66, 57, 0.3);
        }
        .documents-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-border);
        }
        .documents-section h4 {
            color: var(--primary-green);
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background: var(--gray-light);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .document-item:hover {
            background: var(--white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .document-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .document-icon {
            font-size: 1.2rem;
            color: var(--primary-green);
            width: 20px;
            text-align: center;
        }
        .document-name {
            font-size: 0.9rem;
            color: var(--text-medium);
        }
        .document-actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 5px 15px;
            border: none;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--primary-green);
            color: var(--white);
            border: 1.5px solid var(--primary-green);
        }
        .action-btn:hover {
            transform: translateY(-2px);
            background: var(--primary-green-light);
            box-shadow: 0 5px 15px rgba(6, 66, 57, 0.2);
        }
        .upload-form {
            display: none;
            margin-top: 10px;
            padding: 15px;
            background: var(--gray-light);
            border-radius: 10px;
        }
        .upload-form.active {
            display: block;
        }
        .upload-form input[type="file"] {
            margin-bottom: 10px;
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            background: var(--white);
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-green);
            border: 1.5px solid var(--primary-green-light);
            padding: 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--white);
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.3);
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .users-grid { grid-template-columns: 1fr; }
            .search-container { flex-direction: column; }
            .search-box { min-width: 100%; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-users"></i> User Management</h1>
                <p>Manage user accounts and documents</p>
                <a href="add_user.php" class="new-user-btn">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="search-section">
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search users by name or email..." onkeyup="filterUsers()">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>

            <div class="users-grid" id="usersGrid">
                <?php while ($user = $users_result->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="user-card" data-name="<?php echo strtolower(htmlspecialchars($user['full_name'])); ?>" data-email="<?php echo strtolower(htmlspecialchars($user['email'])); ?>">
                        <div class="user-header">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-details">
                                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                            <span class="user-status <?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>

                        <div class="user-actions">
                            <button class="btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>

                        <div class="documents-section">
                            <h4><i class="fas fa-file-alt"></i> Documents</h4>
                            
                            <!-- CV Document -->
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-icon"><i class="fas fa-file-pdf"></i></span>
                                    <span class="document-name">CV</span>
                                </div>
                                <div class="document-actions">
                                    <?php if ($user['latest_cv']): ?>
                                        <a href="<?php echo htmlspecialchars($user['latest_cv']); ?>" target="_blank" class="action-btn">View</a>
                                    <?php endif; ?>
                                    <button class="action-btn" onclick="toggleUploadForm('cv_<?php echo $user['id']; ?>')">Upload</button>
                                </div>
                            </div>
                            <form class="upload-form" id="cv_<?php echo $user['id']; ?>" action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_document">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="document_type" value="cv">
                                <input type="file" name="document" accept=".pdf" required>
                                <button type="submit" class="action-btn">Submit</button>
                            </form>

                            <!-- Academic Documents -->
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-icon"><i class="fas fa-graduation-cap"></i></span>
                                    <span class="document-name">Academic Documents</span>
                                </div>
                                <div class="document-actions">
                                    <?php if ($user['latest_academic']): ?>
                                        <a href="<?php echo htmlspecialchars($user['latest_academic']); ?>" target="_blank" class="action-btn">View</a>
                                    <?php endif; ?>
                                    <button class="action-btn" onclick="toggleUploadForm('academic_<?php echo $user['id']; ?>')">Upload</button>
                                </div>
                            </div>
                            <form class="upload-form" id="academic_<?php echo $user['id']; ?>" action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_document">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="document_type" value="academic">
                                <input type="file" name="document" accept=".pdf" required>
                                <button type="submit" class="action-btn">Submit</button>
                            </form>

                            <!-- Certifications -->
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-icon"><i class="fas fa-certificate"></i></span>
                                    <span class="document-name">Certifications</span>
                                </div>
                                <div class="document-actions">
                                    <?php if ($user['latest_certification']): ?>
                                        <a href="<?php echo htmlspecialchars($user['latest_certification']); ?>" target="_blank" class="action-btn">View</a>
                                    <?php endif; ?>
                                    <button class="action-btn" onclick="toggleUploadForm('certification_<?php echo $user['id']; ?>')">Upload</button>
                                </div>
                            </div>
                            <form class="upload-form" id="certification_<?php echo $user['id']; ?>" action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_document">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="document_type" value="certification">
                                <input type="file" name="document" accept=".pdf" required>
                                <button type="submit" class="action-btn">Submit</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 15px; max-width: 400px; width: 90%;">
            <h3 style="color: #dc3545; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
            <p style="margin-bottom: 20px;">Are you sure you want to delete user: <strong id="deleteUserName"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.9rem; margin-bottom: 20px;"><i class="fas fa-info-circle"></i> This action cannot be undone and will delete all user data including documents and applications.</p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" style="padding: 10px 20px; border: 1px solid #ccc; background: white; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button onclick="confirmDelete()" style="padding: 10px 20px; border: none; background: #dc3545; color: white; border-radius: 8px; cursor: pointer;">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteUserId = null;

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');
            
            userCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const email = card.getAttribute('data-email');
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function editUser(userId) {
            // Redirect to edit user page or open edit modal
            window.location.href = `edit_user.php?id=${userId}`;
        }

        function deleteUser(userId, userName) {
            deleteUserId = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteUserId = null;
        }

        function confirmDelete() {
            if (deleteUserId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${deleteUserId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleUploadForm(formId) {
            const form = document.getElementById(formId);
            form.classList.toggle('active');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html> 