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
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<style>
body { font-family: Arial; background:#f4f4f9; }
.container { max-width:800px; margin:50px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
.user-info { text-align:center; margin-bottom:30px; }
.user-info span { font-weight:bold; color:#007bff; }
.section { background:#f8f9fa; padding:20px; border-radius:8px; margin-bottom:20px; }
.section p { margin:10px 0; }
a.button { display:inline-block; padding:10px 20px; margin:5px; background:#28a745; color:white; text-decoration:none; border-radius:5px; transition:0.3s; }
a.button:hover { background:#218838; }
</style>
</head>
<body>
<div class="container">
<h2>Welcome, <span><?= htmlspecialchars($user['username']) ?></span>!</h2>

<div class="section">
<h3>Security Settings</h3>
<p>Two-Factor Authentication: 
<?= $user['is_2fa_enabled'] ? '<span style="color:green">Enabled</span> ('.$user['two_factor_type'].')' : '<span style="color:red">Disabled</span>' ?>
</p>
<?php if(!$user['is_2fa_enabled']): ?>
<a class="button" href="enable_2fa.php">Enable 2FA</a>
<?php else: ?>
<a class="button" href="enable_2fa.php">Change 2FA Method</a>
<?php endif; ?>
</div>

<div class="section" style="text-align:center;">
<a class="button" href="logout.php">Logout</a>
</div>
</div>
</body>
</html>
