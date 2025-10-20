<?php

/**
 * Registration page using OOP architecture
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    
    $authController = app('authController');
    
    
    if (isPost()) {
        $authController->register();
    } else {
        
        $authController->showRegister();
    }
    
} catch (Exception $e) {
    
    error_log("Registration error: " . $e->getMessage());
    
    
    $sessionService = app('sessionService');
    $sessionService->setErrorMessage('An error occurred. Please try again.');
    redirect('register.php');
}
