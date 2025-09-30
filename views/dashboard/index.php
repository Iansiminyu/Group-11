<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Dashboard - Smart Restaurant System' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container container-wide">
<h1>🍽️ Smart Restaurant System</h1>

<div class="user-info">
    <h2>Welcome back, <span class="username"><?= e($user->getUsername()) ?></span>!</h2>
    <p>Member since: <?= $user->getCreatedAt() ? $user->getCreatedAt()->format('F j, Y') : date('F j, Y') ?></p>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success">
        <?= e($successMessage) ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-error">
        ⚠️ <?= e($errorMessage) ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>🔒 Security Settings</h3>
    <p><strong>Two-Factor Authentication:</strong> 
        <?php if($user->is2FAEnabled()): ?>
            <span class="status-enabled">✓ Enabled</span>
            <small>(<?= ucfirst($user->getTwoFactorType()) ?> verification)</small>
        <?php else: ?>
            <span class="status-disabled">✗ Disabled</span>
            <small>(Your account is less secure)</small>
        <?php endif; ?>
    </p>
    
    <p><strong>Email:</strong> <?= e($user->getEmail()) ?></p>
    <?php if($user->getPhone()): ?>
        <p><strong>Phone:</strong> <?= e($user->getPhone()) ?></p>
    <?php endif; ?>
    
    <div class="mt-20">
        <?php if(!$user->is2FAEnabled()): ?>
            <a class="btn btn-success btn-small" href="enable_2fa.php">🔐 Enable 2FA</a>
        <?php else: ?>
            <a class="btn btn-small" href="enable_2fa.php">⚙️ Change 2FA Method</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>🍽️ Restaurant Management</h3>
    <p>Access your complete restaurant management system:</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="feature-card">
            <h4>📋 Orders Management</h4>
            <p>Create and manage customer orders, track status, and process payments.</p>
            <div style="margin-top: 15px;">
                <a class="btn btn-primary btn-small" href="simple-orders.php">View Orders</a>
                <a class="btn btn-success btn-small" href="orders.php">Advanced Orders</a>
            </div>
        </div>
        
        <div class="feature-card">
            <h4>📅 Reservations</h4>
            <p>Handle table bookings, check availability, and manage reservations.</p>
            <div style="margin-top: 15px;">
                <a class="btn btn-primary btn-small" href="simple-reservations.php">View Reservations</a>
                <a class="btn btn-success btn-small" href="reservations.php">Advanced Reservations</a>
            </div>
        </div>
        
        <div class="feature-card">
            <h4>📦 Inventory</h4>
            <p>Track stock levels, manage menu items, and monitor inventory.</p>
            <div style="margin-top: 15px;">
                <a class="btn btn-primary btn-small" href="simple-inventory.php">View Inventory</a>
                <a class="btn btn-success btn-small" href="inventory.php">Advanced Inventory</a>
            </div>
        </div>
        
        <div class="feature-card">
            <h4>📊 Reports</h4>
            <p>View sales reports, analytics, and business insights.</p>
            <div style="margin-top: 15px;">
                <a class="btn btn-primary btn-small" href="reports.php">View Reports</a>
                <a class="btn btn-outline btn-small" href="/api/stats" target="_blank">API Stats</a>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-20">
        <a class="btn btn-large" href="manage.php">🚀 Open Full Management Dashboard</a>
    </div>
</div>

<div class="text-center mt-20">
    <a class="btn btn-danger" href="logout.php">🚪 Logout</a>
</div>

</div>
</body>
</html>
