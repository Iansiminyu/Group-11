<?php
require 'config.php';
require 'helpers.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    
    // Clean up expired codes first
    $pdo->exec("DELETE FROM two_factor_codes WHERE expires_at < NOW()");
    
    if (verify2FACode($pdo, $_SESSION['temp_user_id'], $code)) {
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['2fa_message']);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid or expired verification code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify 2FA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .verify-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        .code-input { width: 100%; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 10px; border: 2px solid #ddd; border-radius: 5px; margin: 20px 0; }
        .btn { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="verify-container">
        <h2>🔒 Verify Identity</h2>
        
        <?php if(isset($_SESSION['2fa_message'])): ?>
            <div style="background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0;">
                📧 <?= htmlspecialchars($_SESSION['2fa_message']) ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="code" class="code-input" placeholder="000000" maxlength="6" required autofocus>
            <button type="submit" class="btn">Verify & Continue</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.querySelector('input[name="code"]');
            codeInput.focus();
            
            codeInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>