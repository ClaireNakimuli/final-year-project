<?php
session_start();
require_once 'DATABASE/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$period = $_GET['period'] ?? 'monthly';

try {
    switch ($period) {
        case 'monthly':
            $sql = "SELECT DATE_FORMAT(created_at, '%b %Y') as period, COUNT(*) as count 
                    FROM jobs 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                    GROUP BY period 
                    ORDER BY created_at";
            break;
        case 'quarterly':
            $sql = "SELECT CONCAT('Q', QUARTER(created_at), ' ', YEAR(created_at)) as period, COUNT(*) as count 
                    FROM jobs 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) 
                    GROUP BY period 
                    ORDER BY created_at";
            break;
        case 'yearly':
            $sql = "SELECT YEAR(created_at) as period, COUNT(*) as count 
                    FROM jobs 
                    GROUP BY period 
                    ORDER BY period";
            break;
        default:
            $sql = "SELECT DATE_FORMAT(created_at, '%b %Y') as period, COUNT(*) as count 
                    FROM jobs 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                    GROUP BY period 
                    ORDER BY created_at";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $labels = [];
    $data = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['period'];
        $data[] = (int)$row['count'];
    }
    
    // If no data, provide placeholder
    if (empty($labels)) {
        $labels = ['No Data'];
        $data = [0];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $labels,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 