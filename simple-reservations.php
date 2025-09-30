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

// Get reservations directly from database
try {
    $pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
    
    $stmt = $pdo->query("
        SELECT r.*, u.username 
        FROM reservations r 
        LEFT JOIN accounts u ON r.customer_id = u.id 
        ORDER BY r.reservation_date DESC, r.reservation_time DESC 
        LIMIT 20
    ");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $reservations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Smart Restaurant System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .reservations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .reservation-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .reservation-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        .guest-name {
            font-weight: bold;
            color: #28a745;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-seated { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .nav-bar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .today-highlight {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>📅 Reservations Management</h1>
            <p>Manage table bookings and guest reservations</p>
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="simple-orders.php">📋 Orders</a>
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
            <h3>Recent Reservations</h3>
            
            <?php if (empty($reservations)): ?>
                <div class="text-center">
                    <p>No reservations found.</p>
                    <p>Reservations will appear here once guests start booking tables.</p>
                </div>
            <?php else: ?>
                <div class="reservations-grid">
                    <?php foreach ($reservations as $reservation): ?>
                    <?php 
                    $isToday = date('Y-m-d', strtotime($reservation['reservation_date'])) === date('Y-m-d');
                    $cardClass = $isToday ? 'reservation-card today-highlight' : 'reservation-card';
                    ?>
                    <div class="<?= $cardClass ?>">
                        <div class="reservation-header">
                            <div class="guest-name"><?= e($reservation['customer_name']) ?></div>
                            <div class="status-badge status-<?= e($reservation['status']) ?>">
                                <?= ucfirst($reservation['status']) ?>
                            </div>
                        </div>
                        
                        <div>
                            <strong>Date:</strong> <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                            <strong>Time:</strong> <?= date('H:i', strtotime($reservation['reservation_time'])) ?><br>
                            <strong>Party Size:</strong> <?= $reservation['party_size'] ?> people<br>
                            <?php if ($reservation['table_number']): ?>
                            <strong>Table:</strong> <?= $reservation['table_number'] ?><br>
                            <?php endif; ?>
                            <strong>Phone:</strong> <?= e($reservation['customer_phone']) ?><br>
                            <?php if ($reservation['customer_email']): ?>
                            <strong>Email:</strong> <?= e($reservation['customer_email']) ?><br>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($isToday): ?>
                        <div style="margin-top: 10px;">
                            <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">
                                📅 TODAY
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($reservation['special_requests']): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <small><strong>Special Requests:</strong> <?= e($reservation['special_requests']) ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 10px; font-size: 0.8em; color: #666;">
                            Created: <?= date('M j, H:i', strtotime($reservation['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Quick Stats</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; text-align: center;">
                <div>
                    <h2 style="color: #28a745;"><?= count($reservations) ?></h2>
                    <p>Total Reservations</p>
                </div>
                <div>
                    <h2 style="color: #17a2b8;"><?= count(array_filter($reservations, fn($r) => date('Y-m-d', strtotime($r['reservation_date'])) === date('Y-m-d'))) ?></h2>
                    <p>Today</p>
                </div>
                <div>
                    <h2 style="color: #ffc107;"><?= count(array_filter($reservations, fn($r) => $r['status'] === 'pending')) ?></h2>
                    <p>Pending</p>
                </div>
                <div>
                    <h2 style="color: #28a745;"><?= count(array_filter($reservations, fn($r) => $r['status'] === 'confirmed')) ?></h2>
                    <p>Confirmed</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-20">
            <a href="reservations.php" class="btn btn-primary">🚀 Advanced Reservations Management</a>
            <a href="manage.php" class="btn btn-outline">📊 Full Dashboard</a>
        </div>
    </div>
</body>
</html>
