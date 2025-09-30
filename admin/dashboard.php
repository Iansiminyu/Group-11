<?php
require_once __DIR__ . '/../src/bootstrap.php';

use SmartRestaurant\Models\Order;
use SmartRestaurant\Models\Reservation;
use SmartRestaurant\Models\Inventory;
use SmartRestaurant\Models\User;

// Check if user is authenticated and is admin
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $sessionService->getCurrentUser();

// Get dashboard statistics
$orderModel = new Order();
$reservationModel = new Reservation();
$inventoryModel = new Inventory();

$todayFilters = ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')];
$monthFilters = ['date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d')];

$todayStats = [
    'orders' => $orderModel->getStatistics($todayFilters),
    'reservations' => $reservationModel->getStatistics($todayFilters)
];

$monthStats = [
    'orders' => $orderModel->getStatistics($monthFilters),
    'reservations' => $reservationModel->getStatistics($monthFilters),
    'inventory' => $inventoryModel->getStatistics()
];

// Get recent data
$recentOrders = $orderModel->getAll([], 1, 5)['orders'];
$todayReservations = $reservationModel->getTodayReservations();
$lowStockItems = $inventoryModel->getLowStockItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">🍽️ Smart Restaurant</h4>
                    <small class="text-white-50">Admin Dashboard</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#dashboard" data-section="dashboard">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="#orders" data-section="orders">
                        <i class="bi bi-receipt me-2"></i> Orders
                    </a>
                    <a class="nav-link" href="#reservations" data-section="reservations">
                        <i class="bi bi-calendar-check me-2"></i> Reservations
                    </a>
                    <a class="nav-link" href="#inventory" data-section="inventory">
                        <i class="bi bi-box-seam me-2"></i> Inventory
                    </a>
                    <a class="nav-link" href="#users" data-section="users">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                    <a class="nav-link" href="#reports" data-section="reports">
                        <i class="bi bi-graph-up me-2"></i> Reports
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </nav>
                
                <div class="mt-auto pt-4">
                    <div class="text-white-50 small">
                        <div>Welcome back,</div>
                        <div class="fw-bold text-white"><?= e($currentUser['username']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dashboard Overview</h2>
                        <div class="text-muted">
                            <i class="bi bi-calendar3"></i> <?= date('F j, Y') ?>
                        </div>
                    </div>
                    
                    <!-- Today's Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-primary text-white me-3">
                                        <i class="bi bi-receipt"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0"><?= $todayStats['orders']['total_orders'] ?></div>
                                        <div class="text-muted small">Today's Orders</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-success text-white me-3">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0">KES <?= number_format($todayStats['orders']['total_revenue'], 0) ?></div>
                                        <div class="text-muted small">Today's Revenue</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-info text-white me-3">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0"><?= $todayStats['reservations']['total_reservations'] ?></div>
                                        <div class="text-muted small">Today's Reservations</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-warning text-white me-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0"><?= count($lowStockItems) ?></div>
                                        <div class="text-muted small">Low Stock Items</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Stats -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Monthly Performance</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <div class="h3 text-primary"><?= $monthStats['orders']['total_orders'] ?></div>
                                            <div class="text-muted">Total Orders</div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="h3 text-success">KES <?= number_format($monthStats['orders']['total_revenue'], 0) ?></div>
                                            <div class="text-muted">Total Revenue</div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="h3 text-info">KES <?= number_format($monthStats['orders']['average_order_value'], 0) ?></div>
                                            <div class="text-muted">Avg Order Value</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Inventory Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span>Total Items</span>
                                            <strong><?= $monthStats['inventory']['total_items'] ?></strong>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span>Available</span>
                                            <strong class="text-success"><?= $monthStats['inventory']['available_items'] ?></strong>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span>Low Stock</span>
                                            <strong class="text-warning"><?= $monthStats['inventory']['low_stock_items'] ?></strong>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="d-flex justify-content-between">
                                            <span>Out of Stock</span>
                                            <strong class="text-danger"><?= $monthStats['inventory']['out_of_stock_items'] ?></strong>
                                        </div>
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
                                    <h5 class="mb-0">Recent Orders</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= e($order['order_number']) ?></td>
                                                    <td><?= e($order['customer_name'] ?? 'Guest') ?></td>
                                                    <td>KES <?= number_format($order['total_amount'], 0) ?></td>
                                                    <td>
                                                        <span class="badge badge-status bg-<?= 
                                                            $order['status'] === 'completed' ? 'success' : 
                                                            ($order['status'] === 'pending' ? 'warning' : 'info') 
                                                        ?>">
                                                            <?= ucfirst($order['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Today's Reservations</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Guest</th>
                                                    <th>Party</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($todayReservations, 0, 5) as $reservation): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= date('H:i', strtotime($reservation['reservation_time'])) ?></td>
                                                    <td><?= e($reservation['guest_name']) ?></td>
                                                    <td><?= $reservation['party_size'] ?> people</td>
                                                    <td>
                                                        <span class="badge badge-status bg-<?= 
                                                            $reservation['status'] === 'confirmed' ? 'success' : 
                                                            ($reservation['status'] === 'pending' ? 'warning' : 'info') 
                                                        ?>">
                                                            <?= ucfirst($reservation['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other sections will be loaded via AJAX -->
                <div id="orders-section" class="content-section d-none">
                    <h2>Orders Management</h2>
                    <div id="orders-content">Loading...</div>
                </div>
                
                <div id="reservations-section" class="content-section d-none">
                    <h2>Reservations Management</h2>
                    <div id="reservations-content">Loading...</div>
                </div>
                
                <div id="inventory-section" class="content-section d-none">
                    <h2>Inventory Management</h2>
                    <div id="inventory-content">Loading...</div>
                </div>
                
                <div id="users-section" class="content-section d-none">
                    <h2>User Management</h2>
                    <div id="users-content">Loading...</div>
                </div>
                
                <div id="reports-section" class="content-section d-none">
                    <h2>Reports & Analytics</h2>
                    <div id="reports-content">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation handling
        document.querySelectorAll('.sidebar .nav-link[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active nav
                document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide sections
                const section = this.dataset.section;
                document.querySelectorAll('.content-section').forEach(s => s.classList.add('d-none'));
                document.getElementById(section + '-section').classList.remove('d-none');
                
                // Load content if needed
                if (section !== 'dashboard') {
                    loadSectionContent(section);
                }
            });
        });
        
        function loadSectionContent(section) {
            const contentDiv = document.getElementById(section + '-content');
            
            if (contentDiv.innerHTML === 'Loading...') {
                // Simulate loading content (in real app, this would be AJAX calls)
                setTimeout(() => {
                    switch(section) {
                        case 'orders':
                            contentDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <p>Orders management interface would be loaded here via AJAX.</p>
                                        <p>Features would include:</p>
                                        <ul>
                                            <li>View all orders with filtering and pagination</li>
                                            <li>Update order status</li>
                                            <li>Process payments</li>
                                            <li>Print receipts</li>
                                        </ul>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'reservations':
                            contentDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <p>Reservations management interface would be loaded here via AJAX.</p>
                                        <p>Features would include:</p>
                                        <ul>
                                            <li>View all reservations with calendar view</li>
                                            <li>Check-in customers</li>
                                            <li>Manage table assignments</li>
                                            <li>Send confirmation emails</li>
                                        </ul>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'inventory':
                            contentDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <p>Inventory management interface would be loaded here via AJAX.</p>
                                        <p>Features would include:</p>
                                        <ul>
                                            <li>Add/edit/delete inventory items</li>
                                            <li>Update stock quantities</li>
                                            <li>Set low stock alerts</li>
                                            <li>Generate stock reports</li>
                                        </ul>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'users':
                            contentDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <p>User management interface would be loaded here via AJAX.</p>
                                        <p>Features would include:</p>
                                        <ul>
                                            <li>View all users</li>
                                            <li>Manage user roles and permissions</li>
                                            <li>Enable/disable 2FA</li>
                                            <li>Reset passwords</li>
                                        </ul>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'reports':
                            contentDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <p>Reports and analytics interface would be loaded here via AJAX.</p>
                                        <p>Features would include:</p>
                                        <ul>
                                            <li>Sales reports</li>
                                            <li>Inventory reports</li>
                                            <li>Customer analytics</li>
                                            <li>Financial summaries</li>
                                        </ul>
                                    </div>
                                </div>
                            `;
                            break;
                    }
                }, 500);
            }
        }
        
        // Auto-refresh dashboard stats every 30 seconds
        setInterval(() => {
            if (!document.getElementById('dashboard-section').classList.contains('d-none')) {
                // In a real app, this would refresh the dashboard data
                console.log('Refreshing dashboard data...');
            }
        }, 30000);
    </script>
</body>
</html>
