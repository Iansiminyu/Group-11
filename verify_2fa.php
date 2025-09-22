<?php
session_start();
require 'config.php';
require 'helpers.php';

if(!isset($_SESSION['temp_user_id'])){ header("Location: login.php"); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $code = trim($_POST['code']);
    if(verify2FACode($pdo,$_SESSION['temp_user_id'],$code)){
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['2fa_message']);
        header("Location: dashboard.php"); exit;
    } else { $error="Invalid or expired verification code."; }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Verify 2FA</title>
<style>
body { font-family: Arial; background:#f4f4f9; }
.container { max-width:400px; margin:50px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
input { width:100%; padding:15px; font-size:18px; text-align:center; letter-spacing:5px; margin:10px 0; border-radius:5px; border:2px solid #ddd; }
button { width:100%; padding:12px; background:#28a745; color:white; border:none; border-radius:5px; cursor:pointer; transition:0.3s; }
button:hover { background:#218838; }
.info { background:#e7f3ff; padding:15px; border-radius:5px; margin-bottom:15px; text-align:center; }
.error { color:red; background:#ffeaea; padding:10px; border-radius:5px; text-align:center; }
.resend { text-align:center; margin-top:15px; }
a { color:#007bff; text-decoration:none; }
</style>
</head>
<body>
<div class="container">
<h2>Verify Your Identity</h2>
<?php if(isset($_SESSION['2fa_message'])): ?><div class="info"><?= $_SESSION['2fa_message'] ?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
<form method="POST">
<input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
<button type="submit">Verify & Continue</button>
</form>
<div class="resend"><p>Didn't receive the code? <a href="login.php">Try again</a></p></div>
<a href="login.php">← Back to Login</a>
</div>
</body>
</html>
