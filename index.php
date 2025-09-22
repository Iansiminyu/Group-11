<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Restaurant System</title>
    <style>
        /* Reset & basic styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
        body { background: #f4f4f9; color: #333; line-height: 1.6; }

        /* Container */
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }

        /* Header */
        header { background: #007bff; color: white; padding: 30px 20px; text-align: center; border-radius: 10px; margin-bottom: 30px; }
        header h1 { font-size: 2.5em; margin-bottom: 10px; }
        header p { font-size: 1.2em; }

        /* Navigation */
        nav { text-align: center; margin-bottom: 40px; }
        nav a {
            display: inline-block;
            text-decoration: none;
            color: white;
            background: #28a745;
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 5px;
            transition: background 0.3s ease;
            font-weight: bold;
        }
        nav a:hover { background: #218838; }

        /* Info Box */
        .info-box { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; }
        .info-box p { font-size: 1.1em; margin-bottom: 10px; }
        .info-box .success { color: #28a745; font-weight: bold; }
        .info-box .error { color: #dc3545; font-weight: bold; }

        /* Footer */
        footer { text-align: center; margin-top: 50px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🍽 Smart Restaurant Reservation & Ordering</h1>
            <p>Welcome! Manage your reservations and orders effortlessly.</p>
        </header>

        <nav>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        </nav>

        <div class="info-box">
            <?php
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM accounts");
                $result = $stmt->fetch();
                echo "<p class='success'>Database connected successfully! Accounts in system: " . $result['count'] . "</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>

        <footer>
            &copy; <?= date('Y') ?> Smart Restaurant System. All rights reserved.
        </footer>
    </div>
</body>
</html>
