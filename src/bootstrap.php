<?php

/**
 * Bootstrap file for initializing the application
 */

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load autoloader
require_once __DIR__ . '/autoload.php';

// Import necessary classes
use SmartRestaurant\Services\EmailService;
use SmartRestaurant\Services\TwoFactorAuthService;
use SmartRestaurant\Services\AuthService;
use SmartRestaurant\Services\SessionService;
use SmartRestaurant\Controllers\AuthController;
use SmartRestaurant\Controllers\DashboardController;

/**
 * Simple dependency injection container
 */
class Container
{
    private array $services = [];
    private array $singletons = [];

    public function register(string $name, callable $factory): void
    {
        $this->services[$name] = $factory;
    }

    public function singleton(string $name, callable $factory): void
    {
        $this->services[$name] = $factory;
        $this->singletons[$name] = true;
    }

    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new \RuntimeException("Service '{$name}' not found");
        }

        if (isset($this->singletons[$name])) {
            static $instances = [];
            if (!isset($instances[$name])) {
                $instances[$name] = $this->services[$name]($this);
            }
            return $instances[$name];
        }

        return $this->services[$name]($this);
    }
}

// Create container and register services
$container = new Container();

// Register services as singletons
$container->singleton('sessionService', function() {
    return new SessionService();
});

$container->singleton('emailService', function() {
    return new EmailService();
});

$container->singleton('twoFactorService', function($container) {
    return new TwoFactorAuthService($container->get('emailService'));
});

$container->singleton('authService', function($container) {
    return new AuthService(
        $container->get('twoFactorService'),
        $container->get('sessionService')
    );
});

// Register controllers
$container->register('authController', function($container) {
    return new AuthController(
        $container->get('authService'),
        $container->get('sessionService')
    );
});

$container->register('dashboardController', function($container) {
    return new DashboardController(
        $container->get('authService'),
        $container->get('sessionService')
    );
});

/**
 * Helper function to get service from container
 */
function app(string $service = null)
{
    global $container;
    
    if ($service === null) {
        return $container;
    }
    
    return $container->get($service);
}

/**
 * Helper function for redirects
 */
function redirect(string $url): void
{
    // Prevent header injection and ensure URL is safe
    $safe = filter_var($url, FILTER_SANITIZE_URL);
    header("Location: {$safe}");
    exit;
}

/**
 * Helper function for HTML escaping
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function for checking if request is POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Helper function for getting POST data
 */
function post(string $key, $default = null)
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

/**
 * Helper function for getting GET data
 */
function get(string $key, $default = null)
{
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

// Provide a getallheaders polyfill for non-Apache environments
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Safe input retrieval with optional sanitization
 */
function input(string $key, $default = null, bool $sanitize = true)
{
    $value = $_REQUEST[$key] ?? $default;
    if ($value === null) return $default;
    if ($sanitize && is_string($value)) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    return $value;
}
