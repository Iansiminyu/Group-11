<?php

/**
 * Logout page using OOP architecture with backward compatibility
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    // Get the auth controller and handle logout
    $authController = app('authController');
    $authController->logout();
    
} catch (Exception $e) {
    // Log error and fallback to original approach for compatibility
    error_log("Logout error: " . $e->getMessage());
    
    // Fallback: Use original approach if OOP fails
    require 'config.php';
    session_destroy();
    header("Location: login.php");
    exit;
}