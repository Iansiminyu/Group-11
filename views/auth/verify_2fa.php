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

.alert {
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-weight: 500;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    color: #155724;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    color: #721c24;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.alert-info {
    background: rgba(23, 162, 184, 0.1);
    color: #0c5460;
    border: 1px solid rgba(23, 162, 184, 0.2);
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

@media (max-width: 768px) {
    .verification-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .help-links {
        flex-direction: column;
        gap: 10px;
    }
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

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            ⚠️ <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($message) && $message): ?>
        <div class="alert alert-info">
            📧 <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="security-info">
        <strong>🔒 Security Check:</strong> This extra step helps protect your account from unauthorized access.
    </div>

    <form method="POST" id="verificationForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        
        <input type="text" 
               name="code" 
               id="codeInput"
               class="code-input" 
               placeholder="000000" 
               maxlength="6" 
               pattern="[0-9]{6}"
               autocomplete="one-time-code"
               required>
        
        <button type="submit" class="verify-btn" id="verifyBtn">
            🔓 Verify Code
        </button>
    </form>

    <div class="help-links">
        <a href="login.php" class="help-link">← Back to Login</a>
        <a href="#" class="help-link" onclick="resendCode()">📧 Resend Code</a>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666; text-align: center;">
        🔒 <strong>Security:</strong> This code expires in 10 minutes for your protection.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verificationForm');
    const codeInput = document.getElementById('codeInput');
    const verifyBtn = document.getElementById('verifyBtn');
    
    // Focus code input on page load
    codeInput.focus();
    
    // Only allow numeric input
    codeInput.addEventListener('input', function(e) {
        // Remove non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto-submit when 6 digits are entered
        if (this.value.length === 6) {
            this.classList.add('code-complete');
            setTimeout(() => {
                form.submit();
            }, 300);
        } else {
            this.classList.remove('code-complete');
        }
    });
    
    // Prevent paste of non-numeric content
    codeInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numericPaste = paste.replace(/[^0-9]/g, '').substring(0, 6);
        this.value = numericPaste;
        
        if (numericPaste.length === 6) {
            this.classList.add('code-complete');
            setTimeout(() => {
                form.submit();
            }, 300);
        }
    });
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        verifyBtn.innerHTML = '<span class="loading"></span> Verifying...';
        verifyBtn.disabled = true;
    });
    
    // Enter key handling
    codeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.length === 6) {
            form.submit();
        }
    });
});

// Resend code function (placeholder)
function resendCode() {
    if (confirm('Resend verification code?')) {
        // This would typically make an AJAX call to resend the code
        alert('New verification code sent!');
    }
}
</script>
</body>
</html>
