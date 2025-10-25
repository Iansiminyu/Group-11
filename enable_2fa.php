<?php

/**
 * Enable 2FA page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the dashboard controller
    $dashboardController = app('dashboardController');
    
    // Handle POST request (2FA settings update)
    if (isPost()) {
        $dashboardController->update2FASettings();
    } else {
        // Show 2FA settings form
        $dashboardController->twoFactorSettings();
    }
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("2FA settings error: " . $e->getMessage());
    
    // Redirect to error page or show generic error
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('error.php');
}
