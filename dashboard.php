<?php

/**
 * Dashboard page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the dashboard controller
    $dashboardController = app('dashboardController');
    
    // Show dashboard (handles authentication check internally)
    $dashboardController->index();
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Dashboard error: " . $e->getMessage());
    
    // Redirect to error page or show generic error
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('login.php');
}
