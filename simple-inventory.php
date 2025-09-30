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

// Get inventory directly from database
try {
    $pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
    
    $stmt = $pdo->query("
        SELECT * FROM inventory 
        ORDER BY name ASC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Smart Restaurant System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .inventory-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .item-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        .item-name {
            font-weight: bold;
            color: #6f42c1;
        }
        .stock-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .stock-good { background: #d4edda; color: #155724; }
        .stock-low { background: #fff3cd; color: #856404; }
        .stock-out { background: #f8d7da; color: #721c24; }
        .nav-bar {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
        .price-display {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        .low-stock-alert {
            border-left: 4px solid #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }
        .out-of-stock-alert {
            border-left: 4px solid #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>📦 Inventory Management</h1>
            <p>Track stock levels and manage menu items</p>
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="simple-orders.php">📋 Orders</a>
                <a href="simple-reservations.php">📅 Reservations</a>
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
            <h3>Inventory Items</h3>
            
            <?php if (empty($items)): ?>
                <div class="text-center">
                    <p>No inventory items found.</p>
                    <p>Add inventory items to track stock levels and manage your menu.</p>
                </div>
            <?php else: ?>
                <div class="inventory-grid">
                    <?php foreach ($items as $item): ?>
                    <?php 
                    $stockLevel = 'good';
                    $cardClass = 'inventory-card';
                    
                    if ($item['current_stock'] == 0) {
                        $stockLevel = 'out';
                        $cardClass = 'inventory-card out-of-stock-alert';
                    } elseif ($item['current_stock'] <= $item['minimum_stock']) {
                        $stockLevel = 'low';
                        $cardClass = 'inventory-card low-stock-alert';
                    }
                    ?>
                    <div class="<?= $cardClass ?>">
                        <div class="item-header">
                            <div class="item-name"><?= e($item['name']) ?></div>
                            <div class="stock-badge stock-<?= $stockLevel ?>">
                                <?= $item['current_stock'] ?> <?= e($item['unit_of_measure']) ?>
                            </div>
                        </div>
                        
                        <?php if ($item['description']): ?>
                        <div style="margin-bottom: 15px; color: #666; font-size: 0.9em;">
                            <?= e(substr($item['description'], 0, 100)) ?><?= strlen($item['description']) > 100 ? '...' : '' ?>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <div class="price-display">KES <?= number_format($item['selling_price'], 0) ?></div>
                                <small style="color: #666;">Selling Price</small>
                            </div>
                            <div>
                                <div style="font-weight: bold;">KES <?= number_format($item['unit_cost'], 2) ?></div>
                                <small style="color: #666;">Cost Price</small>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px; text-align: center;">
                            <div>
                                <strong><?= $item['current_stock'] ?></strong><br>
                                <small style="color: #666;">Current</small>
                            </div>
                            <div>
                                <strong><?= $item['minimum_stock'] ?></strong><br>
                                <small style="color: #666;">Min Stock</small>
                            </div>
                            <div>
                                <strong><?= $item['maximum_stock'] ?></strong><br>
                                <small style="color: #666;">Max Stock</small>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong>Category:</strong> <?= e($item['category']) ?><br>
                            <strong>SKU:</strong> <?= e($item['sku']) ?><br>
                            <strong>Status:</strong> <?= e($item['status']) ?>
                        </div>
                        
                        <?php if ($item['supplier_name']): ?>
                        <div style="padding: 8px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em;">
                            <strong>Supplier:</strong> <?= e($item['supplier_name']) ?><br>
                            <?php if ($item['supplier_contact']): ?>
                            <strong>Contact:</strong> <?= e($item['supplier_contact']) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($item['expiry_date']): ?>
                        <div style="margin-top: 10px; font-size: 0.9em; color: #dc3545;">
                            <strong>Expires:</strong> <?= date('M j, Y', strtotime($item['expiry_date'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Inventory Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; text-align: center;">
                <div>
                    <h2 style="color: #6f42c1;"><?= count($items) ?></h2>
                    <p>Total Items</p>
                </div>
                <div>
                    <h2 style="color: #28a745;"><?= count(array_filter($items, fn($i) => $i['current_stock'] > $i['minimum_stock'])) ?></h2>
                    <p>Well Stocked</p>
                </div>
                <div>
                    <h2 style="color: #ffc107;"><?= count(array_filter($items, fn($i) => $i['current_stock'] <= $i['minimum_stock'] && $i['current_stock'] > 0)) ?></h2>
                    <p>Low Stock</p>
                </div>
                <div>
                    <h2 style="color: #dc3545;"><?= count(array_filter($items, fn($i) => $i['current_stock'] == 0)) ?></h2>
                    <p>Out of Stock</p>
                </div>
            </div>
        </div>

        <?php 
        $lowStockItems = array_filter($items, fn($i) => $i['current_stock'] <= $i['minimum_stock']);
        if (!empty($lowStockItems)): 
        ?>
        <div class="card" style="border-left: 4px solid #ffc107;">
            <h3>⚠️ Stock Alerts</h3>
            <p>The following items need attention:</p>
            <ul>
                <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
                <li>
                    <strong><?= e($item['name']) ?></strong> - 
                    Only <?= $item['current_stock'] ?> <?= e($item['unit_of_measure']) ?> left
                    (Min: <?= $item['minimum_stock'] ?>)
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($lowStockItems) > 5): ?>
            <p><em>... and <?= count($lowStockItems) - 5 ?> more items</em></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-20">
            <a href="inventory.php" class="btn btn-primary">🚀 Advanced Inventory Management</a>
            <a href="manage.php" class="btn btn-outline">📊 Full Dashboard</a>
        </div>
    </div>
</body>
</html>
