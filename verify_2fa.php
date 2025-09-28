<?php
session_start();
require 'config.php';
require 'helpers.php';

if(!isset($_SESSION['temp_user_id'])){ header("Location: login.php"); exit; }

// Rate limiting for 2FA attempts
if (!isset($_SESSION['2fa_attempts'])) {
    $_SESSION['2fa_attempts'] = 0;
    $_SESSION['2fa_last_attempt'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['2fa_last_attempt'] > 900) {
    $_SESSION['2fa_attempts'] = 0;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    // Check rate limiting
    if ($_SESSION['2fa_attempts'] >= 5) {
        $error = "Too many failed attempts. Please wait 15 minutes before trying again.";
    } else {
        $code = trim($_POST['code']);
        
        // Validate code format
        if (!preg_match('/^[0-9]{6}$/', $code)) {
            $error = "Please enter a valid 6-digit code.";
            $_SESSION['2fa_attempts']++;
        } else {
            if(verify2FACode($pdo, $_SESSION['temp_user_id'], $code)){
                // Success - reset attempts and login
                unset($_SESSION['2fa_attempts']);
                unset($_SESSION['2fa_last_attempt']);
                $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['2fa_message']);
                
                // Log successful 2FA
                error_log("Successful 2FA login for user ID: " . $_SESSION['user_id']);
                
                header("Location: dashboard.php"); exit;
            } else { 
                $_SESSION['2fa_attempts']++;
                $_SESSION['2fa_last_attempt'] = time();
                $remaining = 5 - $_SESSION['2fa_attempts'];
                
                if ($remaining > 0) {
                    $error = "Invalid or expired verification code. {$remaining} attempts remaining.";
                } else {
                    $error = "Too many failed attempts. Please wait 15 minutes before trying again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify 2FA - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Enhanced 2FA Verification Styles */
.verification-container {
    max-width: 450px;
    margin: 50px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
}

.verification-header {
    margin-bottom: 30px;
}

.verification-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.code-input {
    width: 100%;
    padding: 20px;
    font-size: 28px;
    text-align: center;
    letter-spacing: 12px;
    font-weight: bold;
    margin: 20px 0;
    border: 3px solid #e1e8ed;
    border-radius: 15px;
    background: rgba(102, 126, 234, 0.05);
    transition: all 0.3s ease;
    font-family: 'Courier New', monospace;
}

.code-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
    background: rgba(102, 126, 234, 0.08);
}

.code-input::placeholder {
    color: #bdc3c7;
    font-weight: normal;
    letter-spacing: 8px;
}

.verify-btn {
    width: 100%;
    padding: 18px;
    font-size: 1.1rem;
    font-weight: 600;
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 20px 0;
}

.verify-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(40, 167, 69, 0.3);
}

.verify-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.security-info {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 20px;
    border-radius: 15px;
    margin: 20px 0;
    border-left: 4px solid #667eea;
}

.attempts-warning {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 152, 0, 0.1));
    color: #856404;
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
    border-left: 4px solid #ffc107;
    font-weight: 500;
}

.resend-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.timer-display {
    font-family: 'Courier New', monospace;
    font-size: 1.2rem;
    color: #667eea;
    font-weight: bold;
    margin: 10px 0;
}

.help-links {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 25px;
}

.help-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(102, 126, 234, 0.1);
    transition: all 0.3s ease;
}

.help-link:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

/* Auto-advance animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.code-complete {
    animation: pulse 0.3s ease;
    border-color: #28a745 !important;
    background: rgba(40, 167, 69, 0.1) !important;
}
</style>
</head>
<body>
<div class="verification-container">
    <div class="verification-header">
        <span class="verification-icon">🔐</span>
        <h2 style="margin: 0; color: #2c3e50; font-size: 1.8rem;">Verify Your Identity</h2>
        <p style="color: #666; margin: 10px 0 0 0;">Enter the 6-digit code sent to your device</p>
    </div>

    <?php if(isset($_SESSION['2fa_message'])): ?>
        <div class="alert alert-info">
            📧 <?= htmlspecialchars($_SESSION['2fa_message']) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['2fa_attempts']) && $_SESSION['2fa_attempts'] > 0): ?>
        <div class="attempts-warning">
            ⚠️ <?= $_SESSION['2fa_attempts'] ?> failed attempt(s). 
            <?php if($_SESSION['2fa_attempts'] >= 5): ?>
                Account temporarily locked for security.
            <?php else: ?>
                <?= 5 - $_SESSION['2fa_attempts'] ?> attempts remaining.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="security-info">
        <strong>🔒 Security Check:</strong> This extra step helps protect your account from unauthorized access.
    </div>

    <form method="POST" id="verificationForm">
        <input type="text" 
               name="code" 
               id="codeInput"
               class="code-input" 
               placeholder="000000" 
               maxlength="6" 
               pattern="[0-9]{6}" 
               required 
               autocomplete="off"
               <?= (isset($_SESSION['2fa_attempts']) && $_SESSION['2fa_attempts'] >= 5) ? 'disabled' : '' ?>
               title="Enter the 6-digit verification code">
        
        <button type="submit" 
                class="verify-btn" 
                id="verifyBtn"
                <?= (isset($_SESSION['2fa_attempts']) && $_SESSION['2fa_attempts'] >= 5) ? 'disabled' : '' ?>>
            ✓ Verify & Continue to Dashboard
        </button>
    </form>

    <div class="resend-section">
        <p style="color: #666; margin-bottom: 15px;">🔄 Didn't receive the code?</p>
        <div class="help-links">
            <a href="login.php" class="help-link">Request New Code</a>
            <a href="login.php" class="help-link">← Back to Login</a>
        </div>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666;">
        🛡️ <strong>Security Notice:</strong> Codes expire in 10 minutes. Never share your verification code with anyone.
    </div>
</div>

<script>
// Enhanced JavaScript for better UX
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('codeInput');
    const verifyBtn = document.getElementById('verifyBtn');
    const form = document.getElementById('verificationForm');
    
    // Auto-format and validate input
    codeInput.addEventListener('input', function(e) {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto-submit when 6 digits entered
        if (this.value.length === 6) {
            this.classList.add('code-complete');
            // Auto-submit after brief delay for visual feedback
            setTimeout(() => {
                if (!verifyBtn.disabled) {
                    form.submit();
                }
            }, 300);
        } else {
            this.classList.remove('code-complete');
        }
    });
    
    // Focus input on page load
    codeInput.focus();
    
    // Prevent form submission if code is incomplete
    form.addEventListener('submit', function(e) {
        if (codeInput.value.length !== 6) {
            e.preventDefault();
            codeInput.focus();
            codeInput.style.borderColor = '#e74c3c';
            setTimeout(() => {
                codeInput.style.borderColor = '#e1e8ed';
            }, 2000);
        }
    });
    
    // Add loading state on submit
    form.addEventListener('submit', function() {
        verifyBtn.innerHTML = '<span class="loading"></span> Verifying...';
        verifyBtn.disabled = true;
    });
});
</script>
</body>
</html>