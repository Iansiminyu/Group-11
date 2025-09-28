<?php
// Set timezone to match your location
date_default_timezone_set('Africa/Nairobi');

// Database Configuration
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

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'bethueldadaeb@gmail.com');
define('SMTP_PASSWORD', 'vszblqlobaqahbsf');
define('SMTP_FROM', 'bethueldadaeb@gmail.com');
define('SMTP_FROM_NAME', 'Smart Restaurant System');

// Autoload PHPMailer
require_once __DIR__.'/vendor/autoload.php';

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    die("❌ PHPMailer not found. Run: composer require phpmailer/phpmailer");
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>