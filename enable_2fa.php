<?php
require 'config.php';
require 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE accounts SET is_2fa_enabled = TRUE WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    $_SESSION['success'] = "2FA enabled successfully!";
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enable 2FA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Enable 2FA</h2>
        <p>Two-factor authentication will be enabled for your account.</p>
        <p>You'll receive verification codes via email when logging in.</p>
        
        <form method="POST">
            <button type="submit" class="btn">Enable 2FA</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>