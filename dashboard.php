<?php
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container container-wide">
<h1>Smart Restaurant System</h1>

<div class="user-info">
    <h2>Welcome back, <span class="username"><?= htmlspecialchars($user['username']) ?></span>!</h2>
    <p>Member since: <?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?></p>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="card">
    <h3> Security Settings</h3>
    <p><strong>Two-Factor Authentication:</strong> 
        <?php if($user['is_2fa_enabled']): ?>
            <span class="status-enabled">✓ Enabled</span>
            <small>(<?= ucfirst($user['two_factor_type']) ?> verification)</small>
        <?php else: ?>
            <span class="status-disabled">✗ Disabled</span>
            <small>(Your account is less secure)</small>
        <?php endif; ?>
    </p>
    
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <?php if(!empty($user['phone'])): ?>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
    <?php endif; ?>
    
    <div class="mt-20">
        <?php if(!$user['is_2fa_enabled']): ?>
            <a class="btn btn-success btn-small" href="enable_2fa.php" Enable 2FA</a>
        <?php else: ?>
            <a class="btn btn-small" href="enable_2fa.php">Change 2FA Method</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3> Restaurant Management</h3>
    <p>Welcome to your restaurant management dashboard. Here you can:</p>
    <ul style="text-align: left; margin: 20px 0;">
        <li> Manage table reservations</li>
        <li> Process food orders</li>
        <li> View sales analytics</li>
        <li> Manage customer accounts</li>
    </ul>
    <div class="alert alert-info">
        <strong>Coming Soon:</strong> Full restaurant management features will be available in the next update!
    </div>
</div>

<div class="text-center mt-20">
    <a class="btn btn-danger" href="logout.php"> Logout</a>
</div>

</div>
</body>
</html>
