<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Restaurant Reservation & Ordering System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>✅ Smart Restaurant System</h1>
    <p>Welcome! Your setup is working correctly.</p>

    <nav>
        <a href="register.php">Register</a> | 
        <a href="login.php">Login</a>
    </nav>

    <?php
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM accounts");
        $result = $stmt->fetch();
        echo "<p>Accounts in system: " . $result['count'] . "</p>";
    } catch (PDOException $e) {
        echo "<p>Database error: " . $e->getMessage() . "</p>";
    }
    ?>
</div>
</body>
</html>
