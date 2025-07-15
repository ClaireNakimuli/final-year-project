<?php
session_start();
require_once '../includes/config.php';

// Check if setup is already completed with error handling
try {
    $check_setup = $conn->query("SELECT * FROM settings WHERE id = 1");
    if ($check_setup->rowCount() > 0) {
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, continue with setup
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize variables with empty values
    $system_name = isset($_POST['system_name']) ? trim($_POST['system_name']) : '';
    $admin_username = isset($_POST['admin_username']) ? trim($_POST['admin_username']) : '';
    $admin_email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validation
    if (empty($system_name) || empty($admin_username) || empty($admin_email) || empty($admin_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($admin_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($admin_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Create settings record
            $stmt = $conn->prepare("INSERT INTO settings (system_name, created_at) VALUES (?, NOW())");
            if (!$stmt->execute([$system_name])) {
                throw new Exception("Error inserting settings");
            }

            // Create admin user
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt->execute([$admin_username, $admin_email, $hashed_password])) {
                throw new Exception("Error inserting admin");
            }

            // Commit transaction
            $conn->commit();
            
            $success = "Setup completed successfully! Redirecting to login...";
            header("refresh:3;url=login.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Setup failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color1: #FFBFC3;
            --color2: #EBC1C5;
            --color3: #DAC0C7;
            --color4: #AAC3B2;
            --color5: #B8F4DE;
            --text-dark: #222;
            --text-medium: #333;
        }
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            height: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color1) 0%, var(--color2) 20%, var(--color3) 50%, var(--color4) 80%, var(--color5) 100%);
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 25px;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .setup-container h2 {
            background: linear-gradient(90deg, var(--color4), var(--color2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: var(--text-dark);
            background-clip: text;
            color: var(--text-dark);
            font-weight: 700;
            text-align: center;
        }
        .form-label {
            color: var(--text-medium);
            font-weight: 600;
        }
        .form-control {
            background: rgba(255,255,255,0.95);
            color: var(--text-dark);
            border: 1.5px solid var(--color3);
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: var(--color4);
            box-shadow: 0 0 0 2px var(--color5)33;
            color: var(--text-dark);
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--color4), var(--color5));
            border: none;
            padding: 10px 0;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            margin-top: 0.5rem;
            color: var(--text-dark);
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, var(--color5), var(--color4));
            color: var(--text-dark);
        }
        .alert {
            border-radius: 10px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .alert-danger {
            background-color: #ffd6d6;
            border-color: #ffbfc3;
        }
        .alert-success {
            background-color: #d6fff2;
            border-color: #b8f4de;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h2 class="mb-4">System Setup</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger w-100"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success w-100"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="needs-validation w-100" novalidate>
            <div class="mb-3">
                <label for="system_name" class="form-label">System Name</label>
                <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo htmlspecialchars($system_name ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="admin_username" class="form-label">Admin Username</label>
                <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($admin_username ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="admin_email" class="form-label">Admin Email</label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($admin_email ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="admin_password" class="form-label">Admin Password</label>
                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Complete Setup</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 