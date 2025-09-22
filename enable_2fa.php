<?php
session_start();
require 'config.php';
require 'helpers.php';

if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $two_factor_type = $_POST['two_factor_type'];
    $stmt = $pdo->prepare("UPDATE accounts SET is_2fa_enabled=TRUE,two_factor_type=? WHERE id=?");
    $stmt->execute([$two_factor_type,$_SESSION['user_id']]);
    $_SESSION['success']="Two-factor authentication enabled successfully!";
    header("Location: dashboard.php"); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Enable 2FA</title>
<style>
body { font-family: Arial; background:#f4f4f9; }
.container { max-width:500px; margin:50px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
.option { border:1px solid #ddd; padding:20px; border-radius:8px; margin:10px 0; background:#f8f9fa; cursor:pointer; }
.option input { margin-right:10px; }
.option.disabled { opacity:0.5; cursor:not-allowed; }
button { width:100%; padding:12px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer; transition:0.3s; }
button:hover { background:#0056b3; }
a { text-decoration:none; color:#007bff; display:block; text-align:center; margin-top:15px; }
</style>
</head>
<body>
<div class="container">
<h2>Enable Two-Factor Authentication</h2>
<form method="POST">
<div class="option">
<label><input type="radio" name="two_factor_type" value="email" checked>Email Verification</label>
<p>We'll send a 6-digit code to your email: <?= $user['email'] ?></p>
</div>

<div class="option <?= empty($user['phone'])?'disabled':'' ?>">
<label><input type="radio" name="two_factor_type
