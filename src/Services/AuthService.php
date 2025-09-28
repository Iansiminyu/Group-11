<?php

namespace SmartRestaurant\Services;

use SmartRestaurant\Models\User;
use SmartRestaurant\Services\TwoFactorAuthService;

/**
 * Authentication service class
 * Handles user authentication, registration, and session management
 */
class AuthService
{
    private User $userModel;
    private TwoFactorAuthService $twoFactorService;
    private SessionService $sessionService;

    public function __construct(
        TwoFactorAuthService $twoFactorService,
        SessionService $sessionService
    ) {
        $this->userModel = new User();
        $this->twoFactorService = $twoFactorService;
        $this->sessionService = $sessionService;
    }

    /**
     * Authenticate user with email and password
     */
    public function login(string $email, string $password): array
    {
        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user || !$user->verifyPassword($password)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password.',
                    'requires_2fa' => false
                ];
            }

            // Check if 2FA is enabled
            if ($user->is2FAEnabled()) {
                return $this->handle2FALogin($user);
            }

            // Direct login without 2FA
            $this->sessionService->loginUser($user->getId());

            return [
                'success' => true,
                'message' => 'Login successful!',
                'requires_2fa' => false,
                'redirect' => 'dashboard.php'
            ];

        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login. Please try again.',
                'requires_2fa' => false
            ];
        }
    }

    /**
     * Handle 2FA login process
     */
    private function handle2FALogin(User $user): array
    {
        $result = $this->twoFactorService->generateAndSend(
            $user->getId(),
            $user->getEmail(),
            $user->getTwoFactorType(),
            $user->getPhone()
        );

        if ($result['success']) {
            $this->sessionService->setTempUserId($user->getId());
            $this->sessionService->set2FAMessage($result['message']);

            return [
                'success' => true,
                'message' => $result['message'],
                'requires_2fa' => true,
                'redirect' => 'verify_2fa.php'
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] . ' Please contact support if the problem persists.',
            'requires_2fa' => false
        ];
    }

    /**
     * Verify 2FA code and complete login
     */
    public function verify2FA(string $code): array
    {
        $tempUserId = $this->sessionService->getTempUserId();

        if (!$tempUserId) {
            return [
                'success' => false,
                'message' => 'Invalid session. Please login again.',
                'redirect' => 'login.php'
            ];
        }

        if ($this->twoFactorService->verifyCode($tempUserId, $code)) {
            $this->sessionService->clearTempSession();
            $this->sessionService->loginUser($tempUserId);

            return [
                'success' => true,
                'message' => 'Verification successful!',
                'redirect' => 'dashboard.php'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid or expired verification code. Please try again.'
        ];
    }

    /**
     * Register a new user
     */
    public function register(array $userData): array
    {
        try {
            // Validate input data
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $validation['errors'])
                ];
            }

            // Check if user already exists
            if ($this->userModel->exists($userData['email'], $userData['username'])) {
                return [
                    'success' => false,
                    'message' => 'Email or username already exists.'
                ];
            }

            // Create the user
            if ($this->userModel->create($userData)) {
                return [
                    'success' => true,
                    'message' => '🎉 Registration successful! Please login to continue.',
                    'redirect' => 'login.php'
                ];
            }

            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];

        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ];
        }
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->sessionService->logout();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->sessionService->isLoggedIn();
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?User
    {
        $userId = $this->sessionService->getUserId();
        
        if ($userId) {
            return $this->userModel->findById($userId);
        }

        return null;
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData(array $data): array
    {
        $errors = [];

        // Username validation
        if (empty($data['username']) || strlen(trim($data['username'])) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }

        // Email validation
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        // Password validation
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        // 2FA validation
        if (isset($data['two_factor_type']) && $data['two_factor_type'] === 'sms' && empty($data['phone'])) {
            $errors[] = "Phone number is required for SMS verification.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
