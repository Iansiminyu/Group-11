<?php
session_start();
require 'config.php';
require 'helpers.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $two_factor_type = $_POST['two_factor_type'];
    $password = $_POST['password'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO accounts (username,email,phone,two_factor_type,password_hash) VALUES (?,?,?,?,?)");
    try {
        $stmt->execute([$username,$email,$phone,$two_factor_type,$passwordHash]);
        $_SESSION['success']="Registration successful. Please login.";
        header("Location: login.php"); exit;
    } catch(PDOException $e) {
        $error="Error: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register</title>
<style>
body { font-family: Arial; background:#f4f4f9; }
.container { max-width:500px; margin:50px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
input, select, button { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ddd; }
button { background:#007bff; color:white; border:none; cursor:pointer; transition:0.3s; }
button:hover { background:#0056b3; }
.error { color:red; }
.success { color:green; }
</style>
</head>
<body>
<div class="container">
<h2>Create Your Account</h2>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<?php if(isset($_SESSION['success'])) { echo "<p class='success'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); } ?>
<form method="POST">
<input type="text" name="username" placeholder="Username" required>
<input type="email" name="email" placeholder="Email" required>
<input type="tel" name="phone" placeholder="Phone (+254...)">
<select name="two_factor_type" required>
<option value="email">Email Verification</option>
<option value="sms">SMS Verification</option>
</select>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Register</button>
</form>
<p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
