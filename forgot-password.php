<?php
require_once 'src/bootstrap.php';

use SmartRestaurant\Models\User;
use SmartRestaurant\Services\EmailService;
use SmartRestaurant\Services\TwoFactorAuthService;

$error = '';
$success = '';

if (isPost()) {
    $email = post('email');
    
    if (empty($email)) {
        $error = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            
            if ($user) {
                // Generate reset token
                $resetToken = $user->createPasswordResetToken();
                
                // Send reset email with 2FA code if enabled
                $emailService = app('emailService');
                $twoFactorService = app('twoFactorService');
                
                if ($user->is2FAEnabled()) {
                    // Generate 2FA code for password reset
                    $twoFactorCode = $twoFactorService->generateCode($user->getId(), 'password_reset');
                    
                    $emailBody = "
                        <h2>Password Reset Request</h2>
                        <p>Hello {$user->getUsername()},</p>
                        <p>We received a request to reset your password. Since you have 2FA enabled, please use the following code to verify your identity:</p>
                        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                            <h3 style='color: #007bff; font-size: 32px; letter-spacing: 5px; margin: 0;'>{$twoFactorCode}</h3>
                            <p style='margin: 10px 0 0 0; color: #6c757d;'>This code expires in 10 minutes</p>
                        </div>
                        <p>After entering the code, you'll be able to reset your password using this link:</p>
                        <p><a href='" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token={$resetToken}' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                        <p>If you didn't request this password reset, please ignore this email.</p>
                        <p>Best regards,<br>Smart Restaurant System</p>
                    ";
                } else {
                    $emailBody = "
                        <h2>Password Reset Request</h2>
                        <p>Hello {$user->getUsername()},</p>
                        <p>We received a request to reset your password. Click the link below to reset your password:</p>
                        <p><a href='" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token={$resetToken}' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this password reset, please ignore this email.</p>
                        <p>Best regards,<br>Smart Restaurant System</p>
                    ";
                }
                
                $emailService->sendEmail(
                    $user->getEmail(),
                    'Password Reset Request - Smart Restaurant System',
                    $emailBody
                );
                
                $success = 'Password reset instructions have been sent to your email address.';
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If an account with that email exists, password reset instructions have been sent.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while processing your request. Please try again.';
            error_log('Password reset error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
        }
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h1 class="display-6">🍽️</h1>
                            <h2 class="h4 mb-2">Forgot Password</h2>
                            <p class="text-muted">Enter your email address and we'll send you instructions to reset your password.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= e(post('email', '')) ?>" required>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-envelope me-2"></i>Send Reset Instructions
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="mb-0">
                                Remember your password? 
                                <a href="login.php" class="text-decoration-none">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="bi bi-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
