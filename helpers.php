<?php

/**
 * Legacy helper functions - now use OOP services
 * These functions are kept for backward compatibility
 */

// Load the OOP bootstrap if not already loaded
if (!function_exists('app')) {
    require_once __DIR__ . '/src/bootstrap.php';
}

// Generate 6-digit 2FA code
function generate2FACode() {
    return app('twoFactorService')->generateCode();
}

// Send email via OOP EmailService
function sendEmail2FACode($email, $code) {
    return app('emailService')->send(
        $email, 
        'Your Verification Code - Smart Restaurant System',
        "Your verification code is: $code\nThis code expires in 10 minutes."
    );
}

// Send SMS via OOP TwoFactorAuthService
function sendSMS2FACode($phone, $code) {
    return app('twoFactorService')->sendCodeBySMS($phone, $code);
}

// Store 2FA code using OOP service
function store2FACode($pdo, $user_id, $code) {
    return app('twoFactorService')->storeCode($user_id, $code);
}

// Verify 2FA code using OOP service
function verify2FACode($pdo, $user_id, $code) {
    return app('twoFactorService')->verifyCode($user_id, $code);
}