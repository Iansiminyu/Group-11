<?php

/**
 * Registration page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the auth controller
    $authController = app('authController');
    
    // Handle POST request (registration form submission)
    if (isPost()) {
        $authController->register();
    } else {
        // Show registration form
        $authController->showRegister();
    }
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Registration error: " . $e->getMessage());
    
    // Redirect to error page or show generic error
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('register.php');
}
