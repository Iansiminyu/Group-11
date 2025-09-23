<?php
session_start();
require 'config.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone']);
    $two_factor_type = in_array($_POST['two_factor_type'], ['email','sms']) ? $_POST['two_factor_type'] : 'email';
    $password = $_POST['password'];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO accounts (username,email,phone,two_factor_type,password_hash) VALUES (?,?,?,?,?)");
    try {
        $stmt->execute([$username, $email, $phone, $two_factor_type, $passwordHash]);
        $_SESSION['success'] = "Registration successful. Please login.";
        header("Location: login.php"); exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-container">
<h2>Create Account</h2>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<?php if(isset($_SESSION['success'])) { echo "<p class='success'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); } ?>
<form method="POST">
<input type="text" name="username" placeholder="Username" required>
<input type="email" name="email" placeholder="Email" required>
<input type="tel" name="phone" placeholder="+254700000000">
<select name="two_factor_type">
<option value="email">Email Verification</option>
<option value="sms">SMS Verification</option>
</select>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
