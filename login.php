<?php
session_start();
require 'config.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user && password_verify($password,$user['password_hash'])){
        if($user['is_2fa_enabled']){
            $code = generate2FACode();
            $stored = store2FACode($pdo,$user['id'],$code);
            if($stored){
                if($user['two_factor_type']==='sms' && !empty($user['phone'])){
                    sendSMS2FACode($user['phone'],$code);
                    $message="Verification code sent to phone ending ".substr($user['phone'],-4);
                } else {
                    sendEmail2FACode($user['email'],$code);
                    $message="Verification code sent to your email.";
                }
                $_SESSION['temp_user_id']=$user['id'];
                $_SESSION['2fa_message']=$message;
                header("Location: verify_2fa.php"); exit;
            } else { $error="Error generating code. Try again."; }
        } else {
            $_SESSION['user_id']=$user['id'];
            header("Location: dashboard.php"); exit;
        }
    } else { $error="Invalid email or password."; }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
body { font-family: Arial; background:#f4f4f9; }
.container { max-width:400px; margin:50px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
input, button { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ddd; }
button { background:#007bff; color:white; border:none; cursor:pointer; transition:0.3s; }
button:hover { background:#0056b3; }
.error { color:red; background:#ffeaea; padding:10px; border-radius:5px; }
</style>
</head>
<body>
<div class="container">
<h2>Login</h2>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<input type="email" name="email" placeholder="Email" required value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>">
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>
<p style="text-align:center;">Don't have an account? <a href="register.php">Register</a></p>
</d
