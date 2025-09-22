<?php
// Database configuration
$host = "localhost";
$port = "5434";
$dbname = "auth_system";
$user = "postgres";
$password = "1a2bacac";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// Email 
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'bethueldadaeb@gmail.com'); 
define('SMTP_PASSWORD', 'nnza gvcu zumo qwgc'); 
define('SMTP_FROM', 'bethueldadaeb@gmail.com'); 
define('SMTP_FROM_NAME', 'Smart Restaurant System');

// SMS 
define('SMS_API_KEY', 'SK437712c347e9bb64bf1accc765313d79');
define('SMS_SENDER_ID', 'RESTAURANT');
?>
