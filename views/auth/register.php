<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Registration form styling - similar to login but adapted */
.register-container {
    max-width: 500px;
    margin: 30px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.register-header {
    text-align: center;
    margin-bottom: 30px;
}

.register-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-input, .form-select {
    width: 100%;
    padding: 18px 20px;
    font-size: 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-input:focus, .form-select:focus {
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

.register-btn {
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

.register-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
}

.register-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.info-box {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
    border-left: 4px solid #667eea;
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

@media (max-width: 768px) {
    .register-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .nav-link {
        display: block;
        margin: 5px 0;
    }
}
</style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <span class="register-icon">🎆</span>
        <h2 style="margin: 0; color: #2c3e50; font-size: 2rem;">Create Account</h2>
        <p style="color: #666; margin: 10px 0 0 0;">Join our restaurant management platform</p>
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

    <div class="info-box">
        <strong>🍽️ Smart Restaurant System</strong><br>
        <small>Create your account to access our comprehensive restaurant management tools</small>
    </div>

    <form method="POST" class="register-form" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        
        <div class="form-group">
            <input type="text" 
                   name="username" 
                   class="form-input" 
                   placeholder="👤 Username (min 3 characters)" 
                   required 
                   minlength="3"
                   value="<?= e(post('username', '')) ?>">
        </div>
        
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
                   placeholder="🔒 Password (min 6 characters)" 
                   required 
                   minlength="6">
        </div>
        
        <div class="form-group">
            <select name="two_factor_type" class="form-select" id="twoFactorType">
                <option value="email" <?= post('two_factor_type') === 'email' ? 'selected' : '' ?>>
                    📧 Email Verification
                </option>
                <option value="sms" <?= post('two_factor_type') === 'sms' ? 'selected' : '' ?>>
                    📱 SMS Verification
                </option>
            </select>
        </div>
        
        <div class="form-group" id="phoneGroup" style="display: none;">
            <input type="tel" 
                   name="phone" 
                   class="form-input" 
                   placeholder="📱 Phone Number (required for SMS)" 
                   value="<?= e(post('phone', '')) ?>">
        </div>
        
        <button type="submit" class="register-btn" id="registerBtn">
            🚀 Create Account
        </button>
    </form>

    <div class="nav-links">
        <p style="margin: 0 0 15px 0; color: #666;">Already have an account?</p>
        <a href="login_oop.php" class="nav-link">🔐 Sign In</a>
        <a href="index.php" class="nav-link">← Back to Home</a>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666; text-align: center;">
        🔒 <strong>Security:</strong> Your account will be protected with 2FA and enterprise-grade encryption.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const twoFactorType = document.getElementById('twoFactorType');
    const phoneGroup = document.getElementById('phoneGroup');
    const phoneInput = phoneGroup.querySelector('input[name="phone"]');
    const registerBtn = document.getElementById('registerBtn');
    
    // Show/hide phone field based on 2FA type
    function togglePhoneField() {
        if (twoFactorType.value === 'sms') {
            phoneGroup.style.display = 'block';
            phoneInput.required = true;
        } else {
            phoneGroup.style.display = 'none';
            phoneInput.required = false;
        }
    }
    
    // Initial check
    togglePhoneField();
    
    // Listen for changes
    twoFactorType.addEventListener('change', togglePhoneField);
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const username = form.querySelector('input[name="username"]').value.trim();
        const email = form.querySelector('input[name="email"]').value.trim();
        const password = form.querySelector('input[name="password"]').value;
        const phone = phoneInput.value.trim();
        
        let errors = [];
        
        if (username.length < 3) {
            errors.push('Username must be at least 3 characters long.');
        }
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.push('Please enter a valid email address.');
        }
        
        if (password.length < 6) {
            errors.push('Password must be at least 6 characters long.');
        }
        
        if (twoFactorType.value === 'sms' && !phone) {
            errors.push('Phone number is required for SMS verification.');
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
            return;
        }
        
        // Show loading state
        registerBtn.innerHTML = '<span class="loading"></span> Creating Account...';
        registerBtn.disabled = true;
    });
    
    // Real-time email validation
    const emailInput = form.querySelector('input[name="email"]');
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
    const inputs = form.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.style.borderColor = '#e1e8ed';
            this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
        });
    });
});
</script>
</body>
</html>
