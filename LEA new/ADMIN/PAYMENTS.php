<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}
$admin_username = $_SESSION['admin_username'];

include '../includes/config.php';

// Create payments table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_type ENUM('subscription', 'job_posting', 'featured_job', 'resume_access') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if (!$conn->exec($create_table)) {
        error_log("Warning: Could not create payments table");
    }
} catch (PDOException $e) {
    error_log("Warning: Error creating payments table: " . $e->getMessage());
}

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    try {
        $stmt = $conn->prepare("UPDATE payments SET status = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $notes, $payment_id])) {
            $success_message = "Payment status updated successfully!";
        } else {
            $error_message = "Error updating status.";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Fetch payment statistics
try {
    // Check if payments table exists before running statistics queries
    $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($table_check->rowCount() > 0) {
        $stats = [
            'total_revenue' => $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0,
            'pending_payments' => $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0,
            'monthly_revenue' => $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0,
            'total_transactions' => $conn->query("SELECT COUNT(*) as count FROM payments")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0
        ];
    } else {
        $stats = [
            'total_revenue' => 0,
            'pending_payments' => 0,
            'monthly_revenue' => 0,
            'total_transactions' => 0
        ];
    }
} catch (PDOException $e) {
    $stats = [
        'total_revenue' => 0,
        'pending_payments' => 0,
        'monthly_revenue' => 0,
        'total_transactions' => 0
    ];
}

// Fetch recent payments with user/company details
try {
    // Check if required tables exist before running the complex query
    $payments_check = $conn->query("SHOW TABLES LIKE 'payments'");
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    $companies_check = $conn->query("SHOW TABLES LIKE 'companies'");
    
    if ($payments_check->rowCount() > 0) {
        if ($users_check->rowCount() > 0 && $companies_check->rowCount() > 0) {
            $payments_query = "SELECT p.*, 
                              COALESCE(u.full_name, c.name) as payer_name,
                              CASE 
                                WHEN p.user_id IS NOT NULL THEN 'user'
                                ELSE 'company'
                              END as payer_type
                              FROM payments p
                              LEFT JOIN users u ON p.user_id = u.id
                              LEFT JOIN companies c ON p.company_id = c.id
                              ORDER BY p.created_at DESC
                              LIMIT 50";
        } else {
            // If related tables don't exist, just select from payments
            $payments_query = "SELECT p.*, 
                              'Unknown' as payer_name,
                              'unknown' as payer_type
                              FROM payments p
                              ORDER BY p.created_at DESC
                              LIMIT 50";
        }
        
        $payments_result = $conn->query($payments_query);
        $payments_data = [];
        if ($payments_result) {
            while ($row = $payments_result->fetch(PDO::FETCH_ASSOC)) {
                $payments_data[] = $row;
            }
        }
    } else {
        $payments_data = [];
    }
} catch (PDOException $e) {
    $error_message = "Error fetching payments: " . $e->getMessage();
    $payments_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAIR Payments Management</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            padding: 25px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--accent-green), var(--accent-green-light));
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 66, 57, 0.25);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--accent-green);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-medium);
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .payments-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(6, 66, 57, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .table-header h3 {
            color: var(--primary-green);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-border);
        }
        
        th {
            background: rgba(6, 66, 57, 0.05);
            color: var(--primary-green);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: rgba(6, 66, 57, 0.02);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .status-completed { background: rgba(25, 135, 84, 0.1); color: var(--success); }
        .status-failed { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .status-refunded { background: rgba(13, 202, 240, 0.1); color: var(--info); }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #ffb84d 100%);
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
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
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .table-header { flex-direction: column; gap: 15px; }
            table { font-size: 0.9rem; }
            th, td { padding: 10px; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
                <h1><i class="fas fa-credit-card"></i> Payments Management</h1>
                <p>Monitor and manage payment transactions</p>
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

            <!-- Payment Statistics -->
            <?php
            try {
                $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
                if ($table_check->rowCount() > 0) {
                    $total_payments = $conn->query("SELECT COUNT(*) as count FROM payments")->fetch(PDO::FETCH_ASSOC)['count'];
                    $completed_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['count'];
                    $pending_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'];
                    $total_amount = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                } else {
                    $total_payments = $completed_payments = $pending_payments = $total_amount = 0;
                }
            } catch (PDOException $e) {
                $total_payments = $completed_payments = $pending_payments = $total_amount = 0;
            }
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="stat-number"><?php echo number_format($total_payments); ?></div>
                    <div class="stat-label">Total Payments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo number_format($completed_payments); ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo number_format($pending_payments); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-number">UGX <?php echo number_format($total_amount); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="payments-table">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Payment Transactions</h3>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User/Company</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments_data)): ?>
                            <?php foreach ($payments_data as $payment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['transaction_id'] ?: 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($payment['payer_name'] ?: 'N/A'); ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($payment['amount']); ?> <?php echo $payment['currency']; ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['payment_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="updateStatus(<?php echo $payment['id']; ?>)">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-medium);">
                                    <i class="fas fa-info-circle"></i> No payment transactions found. Payments will appear here once transactions are processed.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        function updateStatus(paymentId) {
            const newStatus = prompt('Enter new status (pending/completed/failed/refunded):');
            if (newStatus && ['pending', 'completed', 'failed', 'refunded'].includes(newStatus.toLowerCase())) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="payment_id" value="${paymentId}">
                    <input type="hidden" name="status" value="${newStatus.toLowerCase()}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 