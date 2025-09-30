<?php
require_once 'src/bootstrap.php';

$sessionService = app('sessionService');
$isLoggedIn = $sessionService->isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    try {
        $currentUser = $sessionService->getCurrentUser();
    } catch (Exception $e) {
        $currentUser = null;
    }
}

// Test database connections
$dbStatus = [];
try {
    $pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
    
    // Test each table
    $tables = ['accounts', 'orders', 'reservations', 'inventory'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            $dbStatus[$table] = ['status' => 'ok', 'count' => $count];
        } catch (Exception $e) {
            $dbStatus[$table] = ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Smart Restaurant System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-bar {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .feature-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .feature-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s;
        }
        .feature-link:hover {
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>🔧 System Status & Access</h1>
            <p>Check system health and access all features</p>
        </div>

        <!-- Authentication Status -->
        <div class="card">
            <h3>🔐 Authentication Status</h3>
            <?php if ($isLoggedIn): ?>
                <p class="status-ok">✅ <strong>Logged In</strong></p>
                <?php if ($currentUser): ?>
                    <p><strong>User:</strong> <?= e($currentUser['username']) ?> (<?= e($currentUser['email']) ?>)</p>
                    <p><strong>2FA:</strong> <?= $currentUser['is_2fa_enabled'] ? '✅ Enabled' : '❌ Disabled' ?></p>
                <?php else: ?>
                    <p class="status-warning">⚠️ Session exists but user data unavailable</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="status-error">❌ <strong>Not Logged In</strong></p>
                <p><a href="login.php" class="btn btn-primary">Login</a></p>
            <?php endif; ?>
        </div>

        <!-- Database Status -->
        <div class="card">
            <h3>🗄️ Database Status</h3>
            <?php if (isset($dbError)): ?>
                <p class="status-error">❌ <strong>Database Connection Failed:</strong> <?= e($dbError) ?></p>
            <?php else: ?>
                <p class="status-ok">✅ <strong>Database Connected</strong></p>
                <div class="system-grid">
                    <?php foreach ($dbStatus as $table => $status): ?>
                    <div class="status-card">
                        <h4><?= ucfirst($table) ?> Table</h4>
                        <?php if ($status['status'] === 'ok'): ?>
                            <p class="status-ok">✅ Working</p>
                            <p><strong>Records:</strong> <?= $status['count'] ?></p>
                        <?php else: ?>
                            <p class="status-error">❌ Error</p>
                            <p><small><?= e($status['error']) ?></small></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Feature Access -->
        <?php if ($isLoggedIn): ?>
        <div class="card">
            <h3>🚀 Available Features</h3>
            <p>Click any link below to access the feature:</p>
            
            <div class="feature-links">
                <a href="dashboard.php" class="feature-link">
                    🏠 Main Dashboard
                </a>
                <a href="simple-orders.php" class="feature-link">
                    📋 Orders (Simple)
                </a>
                <a href="simple-reservations.php" class="feature-link">
                    📅 Reservations (Simple)
                </a>
                <a href="simple-inventory.php" class="feature-link">
                    📦 Inventory (Simple)
                </a>
                <a href="orders.php" class="feature-link">
                    📋 Orders (Advanced)
                </a>
                <a href="reservations.php" class="feature-link">
                    📅 Reservations (Advanced)
                </a>
                <a href="inventory.php" class="feature-link">
                    📦 Inventory (Advanced)
                </a>
                <a href="manage.php" class="feature-link">
                    🎛️ Full Management
                </a>
                <a href="/api/" class="feature-link" target="_blank">
                    🔌 API Endpoints
                </a>
            </div>
        </div>

        <div class="card">
            <h3>📊 Quick System Overview</h3>
            <div class="system-grid">
                <div class="status-card">
                    <h4>✅ Working Features</h4>
                    <ul style="text-align: left;">
                        <li>User Authentication & 2FA</li>
                        <li>Session Management</li>
                        <li>Database Connectivity</li>
                        <li>Orders Display</li>
                        <li>Reservations Display</li>
                        <li>Inventory Display</li>
                        <li>Modern UI with CSS</li>
                        <li>Responsive Design</li>
                    </ul>
                </div>
                <div class="status-card">
                    <h4>🔧 Recent Fixes</h4>
                    <ul style="text-align: left;">
                        <li>Added getCurrentUser() method</li>
                        <li>Fixed database column mapping</li>
                        <li>Created simple management pages</li>
                        <li>Added comprehensive CSS styling</li>
                        <li>Fixed navigation links</li>
                        <li>Improved error handling</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mt-20">
            <?php if (!$isLoggedIn): ?>
                <a href="login.php" class="btn btn-primary">🔐 Login to Access Features</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary">🏠 Go to Dashboard</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
