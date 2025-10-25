<?php

/**
 * Dashboard page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the dashboard controller instance
    /** @var \App\Controllers\DashboardController $dashboardController */
    $dashboardController = app('dashboardController');

    // Render the dashboard (handles authentication check internally)
    $dashboardController->index();
} catch (Exception $e) {
    // Log error with additional context for debugging
    error_log(sprintf(
        "[Dashboard Error] %s in %s on line %d",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    // Redirect to login page with a generic error message
    /** @var \App\Services\SessionService $sessionService */
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An unexpected error occurred. Please try again later.');
    
    redirect('login.php');
}
