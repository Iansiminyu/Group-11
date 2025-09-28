<?php

namespace SmartRestaurant\Controllers;

use SmartRestaurant\Services\AuthService;
use SmartRestaurant\Services\SessionService;

/**
 * Authentication controller for handling login, register, and logout
 */
class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(AuthService $authService, SessionService $sessionService)
    {
        parent::__construct($sessionService);
        $this->authService = $authService;
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        $this->requireGuest();
        $this->render('auth/login');
    }

    /**
     * Handle login form submission
     */
    public function login(): void
    {
        $this->requireGuest();

        if (!$this->isPost()) {
            $this->redirect('login.php');
        }

        $email = $this->sanitize($this->getPost('email', ''));
        $password = $this->getPost('password', '');

        if (empty($email) || empty($password)) {
            $this->redirectWithError('login.php', 'Please fill in all fields.');
        }

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            if ($result['requires_2fa']) {
                $this->redirectWithSuccess($result['redirect'], $result['message']);
            } else {
                $this->redirect($result['redirect']);
            }
        } else {
            $this->redirectWithError('login.php', $result['message']);
        }
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        $this->requireGuest();
        $this->render('auth/register');
    }

    /**
     * Handle registration form submission
     */
    public function register(): void
    {
        $this->requireGuest();

        if (!$this->isPost()) {
            $this->redirect('register.php');
        }

        $userData = [
            'username' => $this->sanitize($this->getPost('username', '')),
            'email' => $this->sanitize($this->getPost('email', '')),
            'phone' => $this->sanitize($this->getPost('phone', '')),
            'password' => $this->getPost('password', ''),
            'two_factor_type' => $this->getPost('two_factor_type', 'email')
        ];

        $result = $this->authService->register($userData);

        if ($result['success']) {
            $this->redirectWithSuccess($result['redirect'], $result['message']);
        } else {
            $this->redirectWithError('register.php', $result['message']);
        }
    }

    /**
     * Show 2FA verification form
     */
    public function show2FA(): void
    {
        if (!$this->sessionService->getTempUserId()) {
            $this->redirectWithError('login.php', 'Invalid session. Please login again.');
        }

        $message = $this->sessionService->get2FAMessage();
        $this->render('auth/verify_2fa', ['message' => $message]);
    }

    /**
     * Handle 2FA verification
     */
    public function verify2FA(): void
    {
        if (!$this->isPost()) {
            $this->redirect('verify_2fa.php');
        }

        $code = $this->sanitize($this->getPost('code', ''));

        if (empty($code)) {
            $this->redirectWithError('verify_2fa.php', 'Please enter the verification code.');
        }

        $result = $this->authService->verify2FA($code);

        if ($result['success']) {
            $this->redirect($result['redirect']);
        } else {
            if (isset($result['redirect'])) {
                $this->redirectWithError($result['redirect'], $result['message']);
            } else {
                $this->redirectWithError('verify_2fa.php', $result['message']);
            }
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->redirectWithSuccess('login.php', 'You have been logged out successfully.');
    }

    /**
     * AJAX login endpoint
     */
    public function ajaxLogin(): void
    {
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        $email = $this->sanitize($this->getPost('email', ''));
        $password = $this->getPost('password', '');

        if (empty($email) || empty($password)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please fill in all fields.'
            ], 400);
        }

        $result = $this->authService->login($email, $password);
        $this->jsonResponse($result);
    }

    /**
     * AJAX registration endpoint
     */
    public function ajaxRegister(): void
    {
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        $userData = [
            'username' => $this->sanitize($this->getPost('username', '')),
            'email' => $this->sanitize($this->getPost('email', '')),
            'phone' => $this->sanitize($this->getPost('phone', '')),
            'password' => $this->getPost('password', ''),
            'two_factor_type' => $this->getPost('two_factor_type', 'email')
        ];

        $result = $this->authService->register($userData);
        $this->jsonResponse($result);
    }

    /**
     * Check authentication status (AJAX)
     */
    public function checkAuth(): void
    {
        $this->jsonResponse([
            'authenticated' => $this->authService->isAuthenticated(),
            'user' => $this->authService->getCurrentUser()?->getEmail()
        ]);
    }
}
