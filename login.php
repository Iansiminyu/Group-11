<?php

/**
 * Login page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the auth controller
    $authController = app('authController');
    
    // Handle POST request (login form submission)
    if (isPost()) {
        $authController->login();
    } else {
        // Show login form
        $authController->showLogin();
    }
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Login error: " . $e->getMessage());
    
    // Redirect to error page or show generic error
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('login.php');
}