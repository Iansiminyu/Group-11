<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Enhanced Login Styling */
.login-container {
    max-width: 450px;
    margin: 50px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.login-form {
    margin: 30px 0;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-input {
    width: 100%;
    padding: 18px 20px;
    font-size: 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 1);
}

.form-input::placeholder {
    color: #95a5a6;
    font-weight: 400;
}

.login-btn {
    width: 100%;
    padding: 18px;
    font-size: 1.1rem;
    font-weight: 600;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 10px;
}

.login-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
}

.login-btn:active {
    transform: translateY(-1px);
}

.login-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.welcome-message {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
    border-left: 4px solid #667eea;
    text-align: center;
}

.nav-links {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.nav-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    margin: 0 15px;
    transition: all 0.3s ease;
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(102, 126, 234, 0.1);
}

.nav-link:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

.security-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 20px 0;
    padding: 15px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 10px;
    color: #155724;
    font-size: 0.9rem;
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

@media (max-width: 768px) {
    .login-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .nav-links {
        flex-direction: column;
        gap: 10px;
    }
    
    .nav-link {
        display: block;
        margin: 5px 0;
    }
}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <span class="login-icon">🔐</span>
        <h2 style="margin: 0; color: #2c3e50; font-size: 2rem;">Welcome Back</h2>
        <p style="color: #666; margin: 10px 0 0 0;">Sign in to your restaurant dashboard</p>
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

    <div class="welcome-message">
        <strong>🍽️ Smart Restaurant System</strong><br>
        <small>Secure access to your restaurant management platform</small>
    </div>

    <form method="POST" class="login-form" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        
        <div class="form-group">
            <input type="email" 
                   name="email" 
                   class="form-input" 
                   placeholder="📧 Email Address" 
                   required 
                   value="<?= e(post('email', '')) ?>">
        </div>
        
        <div class="form-group">
            <input type="password" 
                   name="password" 
                   class="form-input" 
                   placeholder="🔒 Password" 
                   required>
        </div>
        
        <button type="submit" class="login-btn" id="loginBtn">
            🚀 Login to Dashboard
        </button>
    </form>

    <div class="security-badge">
        🛡️ <strong>Secured with 2FA Protection</strong>
    </div>

    <div class="nav-links">
        <p style="margin: 0 0 15px 0; color: #666;">New to our platform?</p>
        <a href="register.php" class="nav-link">🎆 Create Account</a>
        <a href="index.php" class="nav-link">← Back to Home</a>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666; text-align: center;">
        🔒 <strong>Security:</strong> Your login is protected with enterprise-grade encryption and optional 2FA.
    </div>
</div>

<script>
// Enhanced login form functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const loginBtn = document.getElementById('loginBtn');
    
    // Focus email input on page load
    emailInput.focus();
    
    // Real-time validation
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this.style.borderColor = '#e74c3c';
            this.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        } else if (email) {
            this.style.borderColor = '#27ae60';
            this.style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.1)';
        }
    });
    
    // Clear error styling on input
    [emailInput, passwordInput].forEach(input => {
        input.addEventListener('input', function() {
            this.style.borderColor = '#e1e8ed';
            this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
        });
    });
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        loginBtn.innerHTML = '<span class="loading"></span> Signing In...';
        loginBtn.disabled = true;
    });
    
    // Enter key navigation
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            passwordInput.focus();
        }
    });
    
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            form.submit();
        }
    });
});
</script>
</body>
</html>
