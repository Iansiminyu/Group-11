<?php
require_once 'src/bootstrap.php';

// Simple authentication check
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$userId = $sessionService->getUserId();
$error = '';
$success = '';

// Get orders directly from database
try {
    $pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
    
    $stmt = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN accounts u ON o.customer_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 20
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Smart Restaurant System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .order-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        .order-number {
            font-weight: bold;
            color: #667eea;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-preparing { background: #d1ecf1; color: #0c5460; }
        .nav-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>📋 Orders Management</h1>
            <p>Manage customer orders and track their status</p>
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="simple-reservations.php">📅 Reservations</a>
                <a href="simple-inventory.php">📦 Inventory</a>
                <a href="manage.php">🚀 Full Management</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= e($success) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Recent Orders</h3>
            
            <?php if (empty($orders)): ?>
                <div class="text-center">
                    <p>No orders found.</p>
                    <p>Orders will appear here once customers start placing them.</p>
                </div>
            <?php else: ?>
                <div class="orders-grid">
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number"><?= e($order['order_number']) ?></div>
                            <div class="status-badge status-<?= e($order['status']) ?>">
                                <?= ucfirst($order['status']) ?>
                            </div>
                        </div>
                        
                        <div>
                            <strong>Customer:</strong> <?= e($order['customer_name'] ?? 'Guest') ?><br>
                            <strong>Total:</strong> KES <?= number_format($order['total_amount'], 0) ?><br>
                            <strong>Date:</strong> <?= date('M j, Y H:i', strtotime($order['created_at'])) ?><br>
                            <?php if ($order['table_number']): ?>
                            <strong>Table:</strong> <?= $order['table_number'] ?><br>
                            <?php endif; ?>
                            <?php if ($order['customer_phone']): ?>
                            <strong>Phone:</strong> <?= e($order['customer_phone']) ?><br>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <span class="status-badge status-<?= e($order['payment_status']) ?>">
                                Payment: <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </div>
                        
                        <?php if ($order['special_instructions']): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <small><strong>Notes:</strong> <?= e($order['special_instructions']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Quick Stats</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; text-align: center;">
                <div>
                    <h2 style="color: #667eea;"><?= count($orders) ?></h2>
                    <p>Total Orders</p>
                </div>
                <div>
                    <h2 style="color: #28a745;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'completed')) ?></h2>
                    <p>Completed</p>
                </div>
                <div>
                    <h2 style="color: #ffc107;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'pending')) ?></h2>
                    <p>Pending</p>
                </div>
                <div>
                    <h2 style="color: #17a2b8;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'preparing')) ?></h2>
                    <p>Preparing</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-20">
            <a href="orders.php" class="btn btn-primary">🚀 Advanced Orders Management</a>
            <a href="manage.php" class="btn btn-outline">📊 Full Dashboard</a>
        </div>
    </div>
</body>
</html>
