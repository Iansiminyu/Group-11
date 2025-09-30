<?php
require_once 'src/bootstrap.php';

use SmartRestaurant\Models\User;
use SmartRestaurant\Services\TwoFactorAuthService;

$error = '';
$success = '';
$token = get('token');
$user = null;
$requireTwoFactor = false;

if (empty($token)) {
    $error = 'Invalid or missing reset token';
} else {
    try {
        $user = User::verifyPasswordResetToken($token);
        if (!$user) {
            $error = 'Invalid or expired reset token';
        } else {
            $requireTwoFactor = $user->is2FAEnabled();
        }
    } catch (Exception $e) {
        $error = 'An error occurred while verifying the reset token';
        error_log('Reset token verification error: ' . $e->getMessage());
    }
}

if (isPost() && $user) {
    $twoFactorCode = post('two_factor_code');
    $newPassword = post('new_password');
    $confirmPassword = post('confirm_password');
    
    try {
        // Verify 2FA code if required
        if ($requireTwoFactor) {
            if (empty($twoFactorCode)) {
                $error = '2FA code is required';
            } else {
                $twoFactorService = app('twoFactorService');
                if (!$twoFactorService->verifyCode($user->getId(), $twoFactorCode, 'password_reset')) {
                    $error = 'Invalid or expired 2FA code';
                }
            }
        }
        
        // Validate password
        if (empty($error)) {
            if (empty($newPassword)) {
                $error = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters long';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match';
            } else {
                // Update password
                $user->updatePassword($newPassword);
                $user->deletePasswordResetToken();
                
                // Clear 2FA code if used
                if ($requireTwoFactor) {
                    $twoFactorService->clearCode($user->getId(), 'password_reset');
                }
                
                $success = 'Your password has been successfully reset. You can now log in with your new password.';
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred while resetting your password. Please try again.';
        error_log('Password reset error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Smart Restaurant System</title>
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
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .two-factor-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
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
                            <h2 class="h4 mb-2">Reset Password</h2>
                            <?php if ($user): ?>
                                <p class="text-muted">Hello <?= e($user->getUsername()) ?>, please enter your new password below.</p>
                            <?php endif; ?>
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
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                                </a>
                            </div>
                        <?php elseif ($user): ?>
                            <?php if ($requireTwoFactor): ?>
                                <div class="two-factor-info">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-shield-check text-primary me-2"></i>
                                        <div>
                                            <strong>2FA Verification Required</strong>
                                            <div class="small">Please enter the verification code sent to your email.</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <?php if ($requireTwoFactor): ?>
                                    <div class="mb-4">
                                        <label for="two_factor_code" class="form-label">
                                            <i class="bi bi-shield-check me-2"></i>2FA Verification Code
                                        </label>
                                        <input type="text" class="form-control text-center" id="two_factor_code" 
                                               name="two_factor_code" placeholder="Enter 6-digit code" 
                                               maxlength="6" pattern="[0-9]{6}" required>
                                        <div class="form-text">Enter the 6-digit code sent to your email</div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required minlength="8">
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required minlength="8">
                                </div>

                                <div class="password-requirements mb-4">
                                    <small>
                                        <i class="bi bi-info-circle me-1"></i>
                                        Password must be at least 8 characters long
                                    </small>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-key me-2"></i>Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="mb-0">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="bi bi-house me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format 2FA code input
        const twoFactorInput = document.getElementById('two_factor_code');
        if (twoFactorInput) {
            twoFactorInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-submit when 6 digits are entered
                if (this.value.length === 6) {
                    // Optional: auto-focus to password field
                    document.getElementById('new_password').focus();
                }
            });
        }

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }
    </script>
</body>
</html>
