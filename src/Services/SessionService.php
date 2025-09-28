<?php

namespace SmartRestaurant\Services;

/**
 * Session service class for managing user sessions
 */
class SessionService
{
    private const USER_ID_KEY = 'user_id';
    private const TEMP_USER_ID_KEY = 'temp_user_id';
    private const TWO_FA_MESSAGE_KEY = '2fa_message';
    private const SUCCESS_MESSAGE_KEY = 'success';
    private const ERROR_MESSAGE_KEY = 'error';

    public function __construct()
    {
        $this->startSession();
    }

    /**
     * Start session if not already started
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Login user by setting user ID in session
     */
    public function loginUser(int $userId): void
    {
        $_SESSION[self::USER_ID_KEY] = $userId;
        $this->regenerateSessionId();
    }

    /**
     * Logout user by clearing session
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        
        // Start a new session
        session_start();
        $this->regenerateSessionId();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION[self::USER_ID_KEY]) && !empty($_SESSION[self::USER_ID_KEY]);
    }

    /**
     * Get current user ID
     */
    public function getUserId(): ?int
    {
        return $_SESSION[self::USER_ID_KEY] ?? null;
    }

    /**
     * Set temporary user ID for 2FA process
     */
    public function setTempUserId(int $userId): void
    {
        $_SESSION[self::TEMP_USER_ID_KEY] = $userId;
    }

    /**
     * Get temporary user ID
     */
    public function getTempUserId(): ?int
    {
        return $_SESSION[self::TEMP_USER_ID_KEY] ?? null;
    }

    /**
     * Clear temporary session data
     */
    public function clearTempSession(): void
    {
        unset($_SESSION[self::TEMP_USER_ID_KEY]);
        unset($_SESSION[self::TWO_FA_MESSAGE_KEY]);
    }

    /**
     * Set 2FA message
     */
    public function set2FAMessage(string $message): void
    {
        $_SESSION[self::TWO_FA_MESSAGE_KEY] = $message;
    }

    /**
     * Get and clear 2FA message
     */
    public function get2FAMessage(): ?string
    {
        $message = $_SESSION[self::TWO_FA_MESSAGE_KEY] ?? null;
        unset($_SESSION[self::TWO_FA_MESSAGE_KEY]);
        return $message;
    }

    /**
     * Set success message
     */
    public function setSuccessMessage(string $message): void
    {
        $_SESSION[self::SUCCESS_MESSAGE_KEY] = $message;
    }

    /**
     * Get and clear success message
     */
    public function getSuccessMessage(): ?string
    {
        $message = $_SESSION[self::SUCCESS_MESSAGE_KEY] ?? null;
        unset($_SESSION[self::SUCCESS_MESSAGE_KEY]);
        return $message;
    }

    /**
     * Set error message
     */
    public function setErrorMessage(string $message): void
    {
        $_SESSION[self::ERROR_MESSAGE_KEY] = $message;
    }

    /**
     * Get and clear error message
     */
    public function getErrorMessage(): ?string
    {
        $message = $_SESSION[self::ERROR_MESSAGE_KEY] ?? null;
        unset($_SESSION[self::ERROR_MESSAGE_KEY]);
        return $message;
    }

    /**
     * Set a custom session value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a custom session value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a custom session value
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if a session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Regenerate session ID for security
     */
    public function regenerateSessionId(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Get session ID
     */
    public function getSessionId(): string
    {
        return session_id();
    }

    /**
     * Set session timeout
     */
    public function setTimeout(int $seconds): void
    {
        ini_set('session.gc_maxlifetime', $seconds);
        session_set_cookie_params($seconds);
    }

    /**
     * Check if session has expired
     */
    public function isExpired(): bool
    {
        $lastActivity = $_SESSION['last_activity'] ?? null;
        
        if ($lastActivity === null) {
            $_SESSION['last_activity'] = time();
            return false;
        }

        $timeout = ini_get('session.gc_maxlifetime') ?: 1440; // Default 24 minutes
        
        if ((time() - $lastActivity) > $timeout) {
            return true;
        }

        $_SESSION['last_activity'] = time();
        return false;
    }
}
