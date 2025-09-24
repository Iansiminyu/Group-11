<?php
require 'config.php';
require 'helpers.php';

echo "<h2>📊 System Status</h2>";

try {
    // Check database
    $pdo->query("SELECT 1");
    echo "✅ Database: Connected<br>";
    
    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM accounts");
    $userCount = $stmt->fetch()['count'];
    echo "✅ Users: $userCount accounts<br>";
    
    // Check 2FA codes table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM two_factor_codes");
    $codeCount = $stmt->fetch()['count'];
    echo "✅ 2FA System: Ready ($codeCount codes in database)<br>";
    
    // Test email
    if (sendEmail2FACode('bethueldadaeb@gmail.com', 'TEST123')) {
        echo "✅ Email System: Working<br>";
    } else {
        echo "⚠ Email System: Check configuration<br>";
    }
    
    echo "<h3>🎉 System is fully operational!</h3>";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='index.php'>Home</a> | <a href='login.php'>Login</a>";
?>