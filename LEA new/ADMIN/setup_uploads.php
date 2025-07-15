<?php
// Database connection
require_once '../includes/config.php';

// Define the base upload directory (in the project root)
$base_upload_dir = dirname(__DIR__) . "/uploads";
$documents_dir = $base_upload_dir . "/documents";

echo "Base upload directory: " . $base_upload_dir . "\n";
echo "Documents directory: " . $documents_dir . "\n";

// Function to check and set directory permissions
function setupDirectory($dir) {
    echo "Setting up directory: " . $dir . "\n";
    
    // Check if parent directory exists and is writable
    $parent_dir = dirname($dir);
    if (!file_exists($parent_dir)) {
        echo "Parent directory does not exist: " . $parent_dir . "\n";
        return false;
    }
    
    if (!is_writable($parent_dir)) {
        echo "Parent directory is not writable: " . $parent_dir . "\n";
        echo "Current permissions: " . substr(sprintf('%o', fileperms($parent_dir)), -4) . "\n";
        return false;
    }
    
    if (!file_exists($dir)) {
        echo "Creating directory: " . $dir . "\n";
        if (!@mkdir($dir, 0777, true)) {
            $error = error_get_last();
            echo "Failed to create directory. Error: " . $error['message'] . "\n";
            return false;
        }
    }
    
    echo "Setting permissions on: " . $dir . "\n";
    if (!@chmod($dir, 0777)) {
        $error = error_get_last();
        echo "Failed to set permissions. Error: " . $error['message'] . "\n";
        return false;
    }
    
    if (!is_writable($dir)) {
        echo "Directory is not writable after setup: " . $dir . "\n";
        echo "Current permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
        return false;
    }
    
    echo "Directory setup successful: " . $dir . "\n";
    return true;
}

// Create and set up base upload directory
echo "\nSetting up base upload directory...\n";
if (!setupDirectory($base_upload_dir)) {
    die("\nFailed to set up base upload directory.\nPlease run these commands in terminal:\n" .
        "cd " . dirname($base_upload_dir) . "\n" .
        "mkdir -p uploads\n" .
        "chmod -R 777 uploads\n" .
        "chown -R daemon:daemon uploads\n");
}

// Create and set up documents directory
echo "\nSetting up documents directory...\n";
if (!setupDirectory($documents_dir)) {
    die("\nFailed to set up documents directory.\nPlease run these commands in terminal:\n" .
        "cd " . dirname($documents_dir) . "\n" .
        "mkdir -p documents\n" .
        "chmod -R 777 documents\n" .
        "chown -R daemon:daemon documents\n");
}

// Create .htaccess to protect the uploads directory
$htaccess_content = "Options -Indexes\n";
$htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$\">\n";
$htaccess_content .= "Order Deny,Allow\n";
$htaccess_content .= "Deny from all\n";
$htaccess_content .= "</FilesMatch>\n";
$htaccess_content .= "<FilesMatch \"\\.(pdf)$\">\n";
$htaccess_content .= "Order Allow,Deny\n";
$htaccess_content .= "Allow from all\n";
$htaccess_content .= "</FilesMatch>\n";

echo "\nCreating .htaccess file...\n";
if (!@file_put_contents($base_upload_dir . "/.htaccess", $htaccess_content)) {
    $error = error_get_last();
    die("Failed to create .htaccess file.\nError: " . $error['message'] . "\n");
}

// Create a test file to verify write permissions
$test_file = $documents_dir . "/test.txt";
echo "\nTesting write permissions...\n";
if (!@file_put_contents($test_file, "Test file created successfully.")) {
    $error = error_get_last();
    die("Failed to create test file.\nError: " . $error['message'] . "\n");
}
@unlink($test_file); // Clean up test file

echo "\nUpload directories have been set up successfully!\n";
echo "Directory structure:\n";
echo $base_upload_dir . "\n";
echo "└── documents/\n";
echo "    └── [user_id]/\n";
echo "        └── [files]\n";
?> 