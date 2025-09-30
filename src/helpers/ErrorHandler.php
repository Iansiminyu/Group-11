<?php

namespace SmartRestaurant\Helpers;

/**
 * Global Error Handler for Smart Restaurant System
 */
class ErrorHandler
{
    /**
     * Set up global error and exception handlers
     */
    public static function setup(): void
    {
        // Set error reporting level
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors to users
        ini_set('log_errors', 1);
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Set shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorType = self::getErrorType($severity);
        $logMessage = "[{$errorType}] {$message} in {$file} on line {$line}";
        
        error_log($logMessage);
        
        // For critical errors, show user-friendly message
        if ($severity & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
            self::showErrorPage("System Error", "An error occurred. Please try again later.");
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(\Throwable $exception): void
    {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        $logMessage = "[EXCEPTION] {$message} in {$file} on line {$line}\nStack trace:\n{$trace}";
        error_log($logMessage);
        
        // Show user-friendly error page
        self::showErrorPage("Application Error", "Something went wrong. Please try again later.");
    }
    
    /**
     * Handle fatal errors during shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $message = $error['message'];
            $file = $error['file'];
            $line = $error['line'];
            
            $logMessage = "[FATAL] {$message} in {$file} on line {$line}";
            error_log($logMessage);
            
            self::showErrorPage("System Error", "A critical error occurred. Please contact support.");
        }
    }
    
    /**
     * Show user-friendly error page
     */
    private static function showErrorPage(string $title, string $message): void
    {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title} - Smart Restaurant System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0; 
            padding: 40px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .error-title {
            color: #dc3545;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <div class='error-icon'>⚠️</div>
        <h1 class='error-title'>{$title}</h1>
        <p class='error-message'>{$message}</p>
        <div class='error-actions'>
            <a href='/' class='btn btn-primary'>🏠 Go Home</a>
            <a href='javascript:history.back()' class='btn btn-secondary'>← Go Back</a>
        </div>
        <p style='margin-top: 30px; font-size: 0.9rem; color: #999;'>
            Error ID: " . uniqid() . "<br>
            Time: " . date('Y-m-d H:i:s') . "
        </p>
    </div>
</body>
</html>";
        
        exit;
    }
    
    /**
     * Get human-readable error type
     */
    private static function getErrorType(int $type): string
    {
        switch ($type) {
            case E_ERROR: return 'ERROR';
            case E_WARNING: return 'WARNING';
            case E_PARSE: return 'PARSE';
            case E_NOTICE: return 'NOTICE';
            case E_CORE_ERROR: return 'CORE_ERROR';
            case E_CORE_WARNING: return 'CORE_WARNING';
            case E_COMPILE_ERROR: return 'COMPILE_ERROR';
            case E_COMPILE_WARNING: return 'COMPILE_WARNING';
            case E_USER_ERROR: return 'USER_ERROR';
            case E_USER_WARNING: return 'USER_WARNING';
            case E_USER_NOTICE: return 'USER_NOTICE';
            case E_STRICT: return 'STRICT';
            case E_RECOVERABLE_ERROR: return 'RECOVERABLE_ERROR';
            case E_DEPRECATED: return 'DEPRECATED';
            case E_USER_DEPRECATED: return 'USER_DEPRECATED';
            default: return 'UNKNOWN';
        }
    }
    
    /**
     * Safe database operation wrapper
     */
    public static function safeDbOperation(callable $operation, $fallback = null)
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            error_log("Database operation failed: " . $e->getMessage());
            return $fallback;
        }
    }
    
    /**
     * Safe model operation wrapper
     */
    public static function safeModelOperation(callable $operation, string $errorMessage = "Operation failed")
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            error_log("Model operation failed: " . $e->getMessage());
            throw new \RuntimeException($errorMessage . ": " . $e->getMessage());
        }
    }
}
