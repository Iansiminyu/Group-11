<?php
require_once 'src/bootstrap.php';

use SmartRestaurant\Models\Order;
use SmartRestaurant\Models\Reservation;
use SmartRestaurant\Models\Inventory;

// Check if user is authenticated
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $sessionService->getCurrentUser();

// Get recent data for dashboard
$orderModel = new Order();
$reservationModel = new Reservation();
$inventoryModel = new Inventory();

try {
    $recentOrders = $orderModel->getAll([], 1, 5)['orders'];
    $todayReservations = $reservationModel->getTodayReservations();
    $lowStockItems = $inventoryModel->getLowStockItems();
} catch (Exception $e) {
    $recentOrders = [];
    $todayReservations = [];
    $lowStockItems = [];
    $error = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .btn-feature {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-feature:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .stats-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .alert-custom {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-5 mb-3">🍽️ Restaurant Management</h1>
            <p class="lead mb-4">Welcome back, <?= e($currentUser['username']) ?>! Manage your restaurant operations with ease.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="/" class="btn btn-outline-light">
                    <i class="bi bi-house me-2"></i>Home
                </a>
                <a href="/admin/dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                </a>
                <a href="/logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-warning alert-custom">
                <i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3><?= count($recentOrders) ?></h3>
                    <p class="mb-0">Recent Orders</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3><?= count($todayReservations) ?></h3>
                    <p class="mb-0">Today's Reservations</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3><?= count($lowStockItems) ?></h3>
                    <p class="mb-0">Low Stock Items</p>
                </div>
            </div>
        </div>

        <!-- Management Features -->
        <div class="row g-4 mb-5">
            <!-- Orders Management -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">📋</div>
                        <h4 class="card-title">Order Management</h4>
                        <p class="card-text">Create, view, and manage customer orders. Track order status and process payments.</p>
                        <div class="d-grid gap-2">
                            <a href="orders.php" class="btn-feature">
                                <i class="bi bi-plus-circle me-2"></i>Manage Orders
                            </a>
                            <a href="orders.php?action=create" class="btn btn-outline-primary">
                                <i class="bi bi-plus me-2"></i>New Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations Management -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">📅</div>
                        <h4 class="card-title">Reservations</h4>
                        <p class="card-text">Handle table bookings, check availability, and manage customer reservations.</p>
                        <div class="d-grid gap-2">
                            <a href="reservations.php" class="btn-feature">
                                <i class="bi bi-calendar-check me-2"></i>Manage Reservations
                            </a>
                            <a href="reservations.php?action=create" class="btn btn-outline-primary">
                                <i class="bi bi-plus me-2"></i>New Reservation
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Management -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">📦</div>
                        <h4 class="card-title">Inventory</h4>
                        <p class="card-text">Track stock levels, manage menu items, and monitor inventory movements.</p>
                        <div class="d-grid gap-2">
                            <a href="inventory.php" class="btn-feature">
                                <i class="bi bi-box-seam me-2"></i>Manage Inventory
                            </a>
                            <a href="inventory.php?action=create" class="btn btn-outline-primary">
                                <i class="bi bi-plus me-2"></i>Add Item
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Management -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">🍴</div>
                        <h4 class="card-title">Menu</h4>
                        <p class="card-text">View and manage your restaurant menu items and categories.</p>
                        <div class="d-grid gap-2">
                            <a href="menu.php" class="btn-feature">
                                <i class="bi bi-list me-2"></i>View Menu
                            </a>
                            <a href="inventory.php?filter=available" class="btn btn-outline-primary">
                                <i class="bi bi-eye me-2"></i>Available Items
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">📊</div>
                        <h4 class="card-title">Reports</h4>
                        <p class="card-text">View sales reports, analytics, and business insights.</p>
                        <div class="d-grid gap-2">
                            <a href="reports.php" class="btn-feature">
                                <i class="bi bi-graph-up me-2"></i>View Reports
                            </a>
                            <a href="/api/stats" class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-download me-2"></i>API Stats
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">⚙️</div>
                        <h4 class="card-title">Settings</h4>
                        <p class="card-text">Configure system settings, user preferences, and security options.</p>
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn-feature">
                                <i class="bi bi-person-gear me-2"></i>Profile Settings
                            </a>
                            <a href="enable_2fa.php" class="btn btn-outline-primary">
                                <i class="bi bi-shield-check me-2"></i>Security
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                            <p class="text-muted">No recent orders found.</p>
                            <a href="orders.php?action=create" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-2"></i>Create First Order
                            </a>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($recentOrders, 0, 3) as $order): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= e($order['order_number']) ?></strong><br>
                                        <small class="text-muted"><?= e($order['customer_name'] ?? 'Guest') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div>KES <?= number_format($order['total_amount'], 0) ?></div>
                                        <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <a href="orders.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Today's Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todayReservations)): ?>
                            <p class="text-muted">No reservations for today.</p>
                            <a href="reservations.php?action=create" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-2"></i>Create Reservation
                            </a>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($todayReservations, 0, 3) as $reservation): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= e($reservation['customer_name']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= date('H:i', strtotime($reservation['reservation_time'])) ?> - 
                                            <?= $reservation['party_size'] ?> people
                                        </small>
                                    </div>
                                    <span class="badge bg-<?= $reservation['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($reservation['status']) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <a href="reservations.php" class="btn btn-outline-primary btn-sm">View All Reservations</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($lowStockItems)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning alert-custom">
                    <h5><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</h5>
                    <p class="mb-2">The following items are running low on stock:</p>
                    <ul class="mb-2">
                        <?php foreach (array_slice($lowStockItems, 0, 3) as $item): ?>
                        <li><strong><?= e($item['name']) ?></strong> - Only <?= $item['stock_quantity'] ?> left</li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="inventory.php?filter=low_stock" class="btn btn-warning btn-sm">
                        <i class="bi bi-box-seam me-2"></i>Manage Inventory
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
