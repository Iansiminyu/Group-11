<?php

namespace SmartRestaurant\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email service class for sending emails using PHPMailer
 */
class EmailService
{
    private array $config;
    private string $logPath;

    public function __construct()
    {
        $this->config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'bethueldadaeb@gmail.com',
            'password' => 'vszblqlobaqahbsf',
            'from_email' => 'bethueldadaeb@gmail.com',
            'from_name' => 'Smart Restaurant System'
        ];

        $this->logPath = __DIR__ . '/../../logs/';
        $this->ensureLogDirectory();
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $message, bool $isHtml = false): bool
    {
        try {
            $mail = $this->createMailer();
            
            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $message;

            if ($mail->send()) {
                $this->logSuccess($to, $subject);
                return true;
            }

            $this->logError("Failed to send email to: {$to}", 'Send failure');
            return false;

        } catch (Exception $e) {
            $this->logError("Email error: " . $e->getMessage(), $to);
            return false;
        }
    }

    /**
     * Send HTML email
     */
    public function sendHtml(string $to, string $subject, string $htmlMessage, string $textMessage = ''): bool
    {
        try {
            $mail = $this->createMailer();
            
            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlMessage;
            
            if (!empty($textMessage)) {
                $mail->AltBody = $textMessage;
            }

            if ($mail->send()) {
                $this->logSuccess($to, $subject);
                return true;
            }

            $this->logError("Failed to send HTML email to: {$to}", 'Send failure');
            return false;

        } catch (Exception $e) {
            $this->logError("HTML email error: " . $e->getMessage(), $to);
            return false;
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulk(array $recipients, string $subject, string $message, bool $isHtml = false): array
    {
        $results = [];
        
        foreach ($recipients as $email) {
            $results[$email] = $this->send($email, $subject, $message, $isHtml);
        }

        return $results;
    }

    /**
     * Create and configure PHPMailer instance
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $this->config['port'];

        // Disable SSL verification for development
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Set charset
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    /**
     * Log successful email sending
     */
    private function logSuccess(string $to, string $subject): void
    {
        $logMessage = sprintf(
            "[%s] SUCCESS: Email sent to %s - Subject: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject
        );

        file_put_contents($this->logPath . 'email_success.log', $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log email errors
     */
    private function logError(string $error, string $context = ''): void
    {
        $logMessage = sprintf(
            "[%s] ERROR: %s - Context: %s\n",
            date('Y-m-d H:i:s'),
            $error,
            $context
        );

        file_put_contents($this->logPath . 'email_error.log', $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Update email configuration
     */
    public function updateConfig(array $newConfig): void
    {
        $this->config = array_merge($this->config, $newConfig);
    }

    /**
     * Test email configuration
     */
    public function testConnection(): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            
            // Just test the connection without sending
            return $mail->smtpConnect();

        } catch (Exception $e) {
            $this->logError("Connection test failed: " . $e->getMessage());
            return false;
        }
    }
}
