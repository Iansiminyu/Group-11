<?php
// config.php - Database and environment configuration

$host = getenv('DB_HOST') ?: "localhost";
$port = getenv('DB_PORT') ?: "5434";
$dbname = getenv('DB_NAME') ?: "auth_system";
$user = getenv('DB_USER') ?: "postgres";
$password = getenv('DB_PASSWORD') ?: "1a2bacac";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// Email settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'bethueldadaeb@gmail.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'nnza gvcu zumo qwgc');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'bethueldadaeb@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Smart Restaurant System');

// SMS settings
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: 'SK437712c347e9bb64bf1accc765313d79');
define('SMS_SENDER_ID', getenv('SMS_SENDER_ID') ?: 'RESTAURANT');
