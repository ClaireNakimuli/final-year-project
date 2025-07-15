<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lea_system';
$socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';

try {
    // Create PDO connection with socket
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4;unix_socket=$socket", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 