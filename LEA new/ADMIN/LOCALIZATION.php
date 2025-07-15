<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Create translations table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(5) NOT NULL,
    key_name VARCHAR(255) NOT NULL,
    translation TEXT NOT NULL,
    context VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (language_code, key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if (!$conn->exec($create_table)) {
        error_log("Warning: Could not create translations table");
    }
} catch (PDOException $e) {
    error_log("Warning: Error creating translations table: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_translation':
                $language_code = trim($_POST['language_code']);
                $key_name = trim($_POST['key_name']);
                $translation = trim($_POST['translation']);
                $context = trim($_POST['context'] ?? '');

                try {
                    $stmt = $conn->prepare("INSERT INTO translations (language_code, key_name, translation, context) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE translation = ?, context = ?");
                    if ($stmt->execute([$language_code, $key_name, $translation, $context, $translation, $context])) {
                        $success_message = "Translation added successfully!";
                    } else {
                        $error_message = "Error adding translation";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error adding translation: " . $e->getMessage();
                }
                break;

            case 'update_translation':
                $id = (int)$_POST['id'];
                $translation = trim($_POST['translation']);
                $context = trim($_POST['context'] ?? '');

                try {
                    $stmt = $conn->prepare("UPDATE translations SET translation = ?, context = ? WHERE id = ?");
                    if ($stmt->execute([$translation, $context, $id])) {
                        $success_message = "Translation updated successfully!";
                    } else {
                        $error_message = "Error updating translation";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error updating translation: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch available languages
$languages = [
    'en' => 'English',
    'sw' => 'Swahili',
    'fr' => 'French',
    'ar' => 'Arabic',
    'zh' => 'Chinese',
    'es' => 'Spanish',
    'pt' => 'Portuguese',
    'hi' => 'Hindi'
];

// Fetch translations with error handling
try {
    // Check if translations table exists before running queries
    $table_check = $conn->query("SHOW TABLES LIKE 'translations'");
    if ($table_check->rowCount() > 0) {
        $language_filter = isset($_GET['language']) ? $_GET['language'] : 'en';
        $context_filter = isset($_GET['context']) ? $_GET['context'] : '';

        $query = "SELECT * FROM translations WHERE language_code = ?";
        $params = [$language_filter];

        if ($context_filter) {
            $query .= " AND context = ?";
            $params[] = $context_filter;
        }

        $query .= " ORDER BY context, key_name";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch unique contexts
        $contexts_stmt = $conn->query("SELECT DISTINCT context FROM translations ORDER BY context");
        $contexts = $contexts_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $translations = [];
        $contexts = [];
    }
} catch (PDOException $e) {
    $translations = [];
    $contexts = [];
    $error_message = "Error fetching translations: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Localization Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
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
        
        .localization-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .localization-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .localization-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .localization-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-green), var(--accent-green-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .card-title {
            color: var(--primary-green);
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .language-selector {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 30px;
        }
        
        .language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .language-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .language-option:hover {
            border-color: var(--accent-green);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.1);
        }
        
        .language-option.active {
            border-color: var(--accent-green);
            background: rgba(13, 148, 136, 0.1);
        }
        
        .language-flag {
            width: 30px;
            height: 20px;
            border-radius: 4px;
            background-size: cover;
            background-position: center;
        }
        
        .language-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .language-code {
            color: var(--text-medium);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-green-light) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 135, 84, 0.4);
        }
        
        .translation-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid var(--gray-border);
            border-radius: 12px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        
        .translation-item:hover {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.1);
        }
        
        .translation-key {
            flex: 1;
            font-weight: 600;
            color: var(--primary-green);
            font-size: 0.9rem;
        }
        
        .translation-value {
            flex: 2;
            color: var(--text-medium);
        }
        
        .translation-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1.5px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success);
            border-color: rgba(25, 135, 84, 0.3);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-color: rgba(220, 53, 69, 0.3);
        }
        
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--primary-green);
            box-shadow: 0 4px 15px rgba(6, 66, 57, 0.2);
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .localization-grid { grid-template-columns: 1fr; }
            .language-grid { grid-template-columns: 1fr; }
            .translation-item { flex-direction: column; align-items: flex-start; }
            .translation-actions { width: 100%; justify-content: flex-end; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-globe"></i> Localization Management</h1>
                <p>Manage multiple languages and translations</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Language Selector -->
            <div class="language-selector">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <div class="card-title">Select Language</div>
                </div>
                
                <div class="language-grid">
                    <div class="language-option active" onclick="selectLanguage('en')">
                        <div class="language-flag" style="background-image: url('https://flagcdn.com/w40/gb.png');"></div>
                        <div>
                            <div class="language-name">English</div>
                            <div class="language-code">en</div>
                        </div>
                    </div>
                    
                    <div class="language-option" onclick="selectLanguage('sw')">
                        <div class="language-flag" style="background-image: url('https://flagcdn.com/w40/tz.png');"></div>
                        <div>
                            <div class="language-name">Swahili</div>
                            <div class="language-code">sw</div>
                        </div>
                    </div>
                    
                    <div class="language-option" onclick="selectLanguage('fr')">
                        <div class="language-flag" style="background-image: url('https://flagcdn.com/w40/fr.png');"></div>
                        <div>
                            <div class="language-name">French</div>
                            <div class="language-code">fr</div>
                        </div>
                    </div>
                    
                    <div class="language-option" onclick="selectLanguage('ar')">
                        <div class="language-flag" style="background-image: url('https://flagcdn.com/w40/sa.png');"></div>
                        <div>
                            <div class="language-name">Arabic</div>
                            <div class="language-code">ar</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="localization-grid">
                <!-- General Settings -->
                <div class="localization-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="card-title">General Settings</div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div class="form-group">
                            <label for="default_language"><i class="fas fa-flag"></i> Default Language</label>
                            <select class="form-control" id="default_language" name="default_language">
                                <option value="en" selected>English</option>
                                <option value="sw">Swahili</option>
                                <option value="fr">French</option>
                                <option value="ar">Arabic</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_format"><i class="fas fa-calendar"></i> Date Format</label>
                            <select class="form-control" id="date_format" name="date_format">
                                <option value="Y-m-d">YYYY-MM-DD</option>
                                <option value="d/m/Y">DD/MM/YYYY</option>
                                <option value="m/d/Y">MM/DD/YYYY</option>
                                <option value="d-m-Y">DD-MM-YYYY</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_format"><i class="fas fa-clock"></i> Time Format</label>
                            <select class="form-control" id="time_format" name="time_format">
                                <option value="H:i">24-hour</option>
                                <option value="h:i A">12-hour</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="currency"><i class="fas fa-money-bill"></i> Currency</label>
                            <select class="form-control" id="currency" name="currency">
                                <option value="UGX" selected>Ugandan Shilling (UGX)</option>
                                <option value="USD">US Dollar (USD)</option>
                                <option value="EUR">Euro (EUR)</option>
                                <option value="GBP">British Pound (GBP)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone"><i class="fas fa-map-marker-alt"></i> Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="Africa/Kampala" selected>Africa/Kampala</option>
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">America/New_York</option>
                                <option value="Europe/London">Europe/London</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>

                <!-- Translation Management -->
                <div class="localization-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-language"></i>
                        </div>
                        <div class="card-title">Translation Management</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="translation_key"><i class="fas fa-key"></i> Translation Key</label>
                        <input type="text" class="form-control" id="translation_key" placeholder="Enter translation key">
                    </div>
                    
                    <div class="form-group">
                        <label for="translation_value"><i class="fas fa-pen"></i> Translation Value</label>
                        <textarea class="form-control" id="translation_value" placeholder="Enter translation value"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button class="btn btn-success" onclick="addTranslation()">
                            <i class="fas fa-plus"></i> Add Translation
                        </button>
                        <button class="btn btn-primary" onclick="importTranslations()">
                            <i class="fas fa-upload"></i> Import
                        </button>
                        <button class="btn btn-primary" onclick="exportTranslations()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                    
                    <div id="translations_list">
                        <div class="translation-item">
                            <div class="translation-key">welcome_message</div>
                            <div class="translation-value">Welcome to LAIR Job Portal</div>
                            <div class="translation-actions">
                                <button class="btn btn-primary btn-sm" onclick="editTranslation('welcome_message')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTranslation('welcome_message')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="translation-item">
                            <div class="translation-key">job_search</div>
                            <div class="translation-value">Search for Jobs</div>
                            <div class="translation-actions">
                                <button class="btn btn-primary btn-sm" onclick="editTranslation('job_search')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTranslation('job_search')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="translation-item">
                            <div class="translation-key">apply_now</div>
                            <div class="translation-value">Apply Now</div>
                            <div class="translation-actions">
                                <button class="btn btn-primary btn-sm" onclick="editTranslation('apply_now')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTranslation('apply_now')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="translation-item">
                            <div class="translation-key">dashboard</div>
                            <div class="translation-value">Dashboard</div>
                            <div class="translation-actions">
                                <button class="btn btn-primary btn-sm" onclick="editTranslation('dashboard')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTranslation('dashboard')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function selectLanguage(langCode) {
            // Remove active class from all language options
            document.querySelectorAll('.language-option').forEach(option => {
                option.classList.remove('active');
            });
            
            // Add active class to selected language
            event.currentTarget.classList.add('active');
            
            // Load translations for selected language
            loadTranslations(langCode);
        }
        
        function loadTranslations(langCode) {
            // Add AJAX call to load translations for the selected language
            console.log('Loading translations for language:', langCode);
        }
        
        function addTranslation() {
            const key = document.getElementById('translation_key').value;
            const value = document.getElementById('translation_value').value;
            
            if (key && value) {
                // Add translation to the list
                const translationsList = document.getElementById('translations_list');
                const newItem = document.createElement('div');
                newItem.className = 'translation-item';
                newItem.innerHTML = `
                    <div class="translation-key">${key}</div>
                    <div class="translation-value">${value}</div>
                    <div class="translation-actions">
                        <button class="btn btn-primary btn-sm" onclick="editTranslation('${key}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteTranslation('${key}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                translationsList.appendChild(newItem);
                
                // Clear form
                document.getElementById('translation_key').value = '';
                document.getElementById('translation_value').value = '';
                
                alert('Translation added successfully!');
            } else {
                alert('Please enter both key and value.');
            }
        }
        
        function editTranslation(key) {
            const newValue = prompt(`Edit translation for "${key}":`);
            if (newValue) {
                // Find and update the translation value
                const translationItems = document.querySelectorAll('.translation-item');
                translationItems.forEach(item => {
                    const keyElement = item.querySelector('.translation-key');
                    if (keyElement.textContent === key) {
                        item.querySelector('.translation-value').textContent = newValue;
                    }
                });
                alert('Translation updated successfully!');
            }
        }
        
        function deleteTranslation(key) {
            if (confirm(`Are you sure you want to delete the translation for "${key}"?`)) {
                // Find and remove the translation item
                const translationItems = document.querySelectorAll('.translation-item');
                translationItems.forEach(item => {
                    const keyElement = item.querySelector('.translation-key');
                    if (keyElement.textContent === key) {
                        item.remove();
                    }
                });
                alert('Translation deleted successfully!');
            }
        }
        
        function importTranslations() {
            alert('Import translations feature will be implemented here.');
        }
        
        function exportTranslations() {
            alert('Export translations feature will be implemented here.');
        }
    </script>
</body>
</html> 