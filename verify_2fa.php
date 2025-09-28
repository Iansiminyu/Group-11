<?php

/**
 * 2FA Verification page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the auth controller
    $authController = app('authController');
    
    // Handle POST request (2FA verification form submission)
    if (isPost()) {
        $authController->verify2FA();
    } else {
        // Show 2FA verification form
        $authController->show2FA();
    }
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("2FA verification error: " . $e->getMessage());
    
    // Redirect to error page or show generic error
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('verify_2fa.php');
}
