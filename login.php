<?php
require 'config.php';
require 'helpers.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_2fa_enabled']) {
            // Clean up expired codes first
            $pdo->exec("DELETE FROM two_factor_codes WHERE expires_at < NOW()");
            
            $code = generate2FACode();
            if (store2FACode($pdo, $user['id'], $code)) {
                if (sendEmail2FACode($user['email'], $code)) {
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['2fa_message'] = "Verification code sent to your email.";
                    header("Location: verify_2fa.php");
                    exit;
                } else {
                    $error = "Failed to send verification code. Please try again.";
                }
            } else {
                $error = "Error generating verification code.";
            }
        } else {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .login-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .btn { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🔐 Login</h2>
        
        <?php if(isset($error)): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="form-input" placeholder="Email" required>
            <input type="password" name="password" class="form-input" placeholder="Password" required>
            <button type="submit" class="btn">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="register.php">Create account</a> | 
            <a href="index.php">Home</a>
        </p>
    </div>
</body>
</html>