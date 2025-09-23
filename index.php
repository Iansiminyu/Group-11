<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Restaurant System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Smart Restaurant System</h1>
        <p>Welcome to our restaurant management platform</p>
        
        <div style="margin: 30px 0;">
            <a href="login.php" class="btn">🔐 Login</a>
            <a href="register.php" class="btn btn-success">🎆 Register</a>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </div>
</body>
</html>