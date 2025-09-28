<?php

namespace SmartRestaurant\Services;

use SmartRestaurant\Core\Database;
use PDOException;

/**
 * Two-Factor Authentication service class
 * Handles 2FA code generation, storage, and verification
 */
class TwoFactorAuthService
{
    private Database $db;
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->db = Database::getInstance();
        $this->emailService = $emailService;
    }

    /**
     * Generate a 6-digit 2FA code
     */
    public function generateCode(): string
    {
        return sprintf("%06d", mt_rand(100000, 999999));
    }

    /**
     * Store 2FA code in database with expiration
     */
    public function storeCode(int $userId, string $code): bool
    {
        try {
            $query = "INSERT INTO two_factor_codes (user_id, code, expires_at) 
                     VALUES (?, ?, NOW() + INTERVAL '10 minutes')";
            
            $this->db->execute($query, [$userId, $code]);
            return true;

        } catch (PDOException $e) {
            error_log("Failed to store 2FA code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(int $userId, string $code): bool
    {
        try {
            // Find valid code
            $query = "SELECT id FROM two_factor_codes 
                     WHERE user_id = ? AND code = ? AND expires_at > NOW() 
                     ORDER BY created_at DESC LIMIT 1";
            
            $result = $this->db->fetchOne($query, [$userId, trim($code)]);

            if ($result) {
                // Delete the used code
                $this->deleteCode($result['id']);
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Failed to verify 2FA code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send 2FA code via email
     */
    public function sendCodeByEmail(string $email, string $code): bool
    {
        $subject = 'Your Verification Code - Smart Restaurant System';
        $message = $this->buildEmailMessage($code);

        return $this->emailService->send($email, $subject, $message);
    }

    /**
     * Send 2FA code via SMS (placeholder for future implementation)
     */
    public function sendCodeBySMS(string $phone, string $code): bool
    {
        // TODO: Integrate with SMS gateway API
        // For now, return true to simulate successful SMS sending
        error_log("SMS 2FA code would be sent to {$phone}: {$code}");
        return true;
    }

    /**
     * Generate and send 2FA code
     */
    public function generateAndSend(int $userId, string $email, string $type = 'email', ?string $phone = null): array
    {
        $code = $this->generateCode();
        
        if (!$this->storeCode($userId, $code)) {
            return [
                'success' => false,
                'message' => 'Failed to generate verification code. Please try again.'
            ];
        }

        $success = false;
        $message = '';

        if ($type === 'sms' && !empty($phone)) {
            $success = $this->sendCodeBySMS($phone, $code);
            $message = $success 
                ? "Verification code sent to phone ending " . substr($phone, -4)
                : "Failed to send SMS. Please try email verification.";
        } else {
            $success = $this->sendCodeByEmail($email, $code);
            $message = $success 
                ? "Verification code sent to your email."
                : "Failed to send email. Please check your email configuration.";
        }

        return [
            'success' => $success,
            'message' => $message
        ];
    }

    /**
     * Clean up expired codes
     */
    public function cleanupExpiredCodes(): int
    {
        try {
            $query = "DELETE FROM two_factor_codes WHERE expires_at <= NOW()";
            $stmt = $this->db->execute($query);
            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Failed to cleanup expired 2FA codes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete a specific code
     */
    private function deleteCode(int $codeId): bool
    {
        try {
            $query = "DELETE FROM two_factor_codes WHERE id = ?";
            $this->db->execute($query, [$codeId]);
            return true;

        } catch (PDOException $e) {
            error_log("Failed to delete 2FA code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email message for 2FA code
     */
    private function buildEmailMessage(string $code): string
    {
        return "Your verification code for Smart Restaurant System is: {$code}\n\n" .
               "This code will expire in 10 minutes.\n\n" .
               "If you didn't request this code, please ignore this email.\n\n" .
               "Best regards,\n" .
               "Smart Restaurant System Team";
    }
}
