<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Dashboard</h1>
        
        <div class="card">
            <h3>Welcome, <?= htmlspecialchars($user['username']) ?>!</h3>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <p>2FA: <?= $user['is_2fa_enabled'] ? '✅ Enabled' : '❌ Disabled' ?></p>
            
            <div style="margin-top: 20px;">
                <?php if(!$user['is_2fa_enabled']): ?>
                    <a href="enable_2fa.php" class="btn">Enable 2FA</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>