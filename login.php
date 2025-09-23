<?php
session_start();
require 'config.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) $error = "Enter a valid email.";

    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_2fa_enabled']) {
            $code = generate2FACode();
            if (store2FACode($pdo, $user['id'], $code)) {
                if ($user['two_factor_type'] === 'sms' && !empty($user['phone'])) {
                    sendSMS2FACode($user['phone'], $code);
                    $message = "Verification code sent to phone ending with " . substr($user['phone'], -4);
                } else {
                    sendEmail2FACode($user['email'], $code);
                    $message = "Verification code sent to your email.";
                }
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['2fa_message'] = $message;
                header("Location: verify_2fa.php"); exit;
            } else {
                $error = "Failed to generate verification code.";
            }
        } else {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php"); exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-container">
<h2>Login</h2>
<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<input type="email" name="email" placeholder="Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>
<p>Don't have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
