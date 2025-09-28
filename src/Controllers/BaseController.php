<?php

namespace SmartRestaurant\Controllers;

use SmartRestaurant\Services\SessionService;

/**
 * Base controller class with common functionality
 */
abstract class BaseController
{
    protected SessionService $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess(string $url, string $message): void
    {
        $this->sessionService->setSuccessMessage($message);
        $this->redirect($url);
    }

    /**
     * Redirect with error message
     */
    protected function redirectWithError(string $url, string $message): void
    {
        $this->sessionService->setErrorMessage($message);
        $this->redirect($url);
    }

    /**
     * Check if request is POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Get POST data
     */
    protected function getPostData(): array
    {
        return $_POST;
    }

    /**
     * Get specific POST field
     */
    protected function getPost(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Sanitize input data
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate CSRF token (basic implementation)
     */
    protected function validateCSRF(): bool
    {
        $token = $this->getPost('csrf_token');
        $sessionToken = $this->sessionService->get('csrf_token');
        
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Generate CSRF token
     */
    protected function generateCSRF(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->sessionService->set('csrf_token', $token);
        return $token;
    }

    /**
     * Render a view with data
     */
    protected function render(string $view, array $data = []): void
    {
        // Extract data to variables
        extract($data);
        
        // Add common data
        $successMessage = $this->sessionService->getSuccessMessage();
        $errorMessage = $this->sessionService->getErrorMessage();
        $csrfToken = $this->generateCSRF();
        
        // Include the view file
        $viewPath = __DIR__ . "/../../views/{$view}.php";
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \RuntimeException("View not found: {$view}");
        }
    }

    /**
     * Return JSON response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Check if user is authenticated
     */
    protected function requireAuth(): void
    {
        if (!$this->sessionService->isLoggedIn()) {
            $this->redirectWithError('login.php', 'Please login to access this page.');
        }
    }

    /**
     * Check if user is guest (not authenticated)
     */
    protected function requireGuest(): void
    {
        if ($this->sessionService->isLoggedIn()) {
            $this->redirect('dashboard.php');
        }
    }
}
