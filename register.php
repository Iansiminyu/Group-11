<?php
session_start();
require 'config.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $two_factor_type = $_POST['two_factor_type'];
    $password = $_POST['password'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Validation
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if ($two_factor_type === 'sms' && empty($phone)) {
        $errors[] = "Phone number is required for SMS verification.";
    }
    
    if (empty($errors)) {
        // Check for existing users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email or username already exists.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO accounts (username,email,phone,two_factor_type,password_hash) VALUES (?,?,?,?,?)");
        
        try {
            $stmt->execute([$username,$email,$phone,$two_factor_type,$passwordHash]);
            $_SESSION['success']="🎉 Registration successful! Please login to continue.";
            header("Location: login.php"); exit;
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Enhanced Registration Styling */
.registration-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.registration-header {
    text-align: center;
    margin-bottom: 30px;
}

.registration-icon {
    font-size: 3.5rem;
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

.form-input {
    width: 100%;
    padding: 15px 20px;
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

.form-select {
    width: 100%;
    padding: 15px 20px;
    font-size: 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    cursor: pointer;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

.register-btn:active {
    transform: translateY(-1px);
}

.validation-info {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
    border-left: 4px solid #667eea;
}

.validation-list {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.validation-list li {
    padding: 5px 0;
    color: #555;
    font-size: 0.9rem;
}

.validation-list li::before {
    content: '✓';
    color: #28a745;
    font-weight: bold;
    margin-right: 8px;
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
}

.nav-link:hover {
    color: #764ba2;
    text-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

/* 2FA Option Styling */
.tfa-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 15px 0;
}

.tfa-option {
    padding: 15px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    background: rgba(248, 249, 250, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.tfa-option:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.tfa-option input[type="radio"] {
    margin-right: 8px;
    width: auto;
}

@media (max-width: 768px) {
    .registration-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .tfa-options {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<div class="registration-container">
    <div class="registration-header">
        <span class="registration-icon">🎆</span>
        <h2 style="margin: 0; color: #2c3e50; font-size: 2rem;">Create Your Account</h2>
        <p style="color: #666; margin: 10px 0 0 0;">Join the Smart Restaurant System</p>
    </div>

    <?php if(!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>⚠️ Validation Errors:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="validation-info">
        <strong>🛡️ Account Requirements:</strong>
        <ul class="validation-list">
            <li>Username: minimum 3 characters</li>
            <li>Password: minimum 6 characters</li>
            <li>Valid email address required</li>
            <li>Phone number needed for SMS 2FA</li>
        </ul>
    </div>

    <form method="POST" id="registrationForm">
        <div class="form-group">
            <input type="text" 
                   name="username" 
                   class="form-input" 
                   placeholder="👤 Username (min 3 characters)" 
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <input type="email" 
                   name="email" 
                   class="form-input" 
                   placeholder="📧 Email Address" 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <input type="tel" 
                   name="phone" 
                   class="form-input" 
                   placeholder="📱 Phone Number (for SMS 2FA)" 
                   value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
        </div>
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50;">
                🔒 Two-Factor Authentication Method:
            </label>
            <div class="tfa-options">
                <div class="tfa-option">
                    <input type="radio" 
                           name="two_factor_type" 
                           value="email" 
                           id="tfa_email"
                           <?= (!isset($_POST['two_factor_type']) || $_POST['two_factor_type'] === 'email') ? 'checked' : '' ?>>
                    <label for="tfa_email">
                        📧<br><strong>Email</strong><br>
                        <small>Most reliable</small>
                    </label>
                </div>
                <div class="tfa-option">
                    <input type="radio" 
                           name="two_factor_type" 
                           value="sms" 
                           id="tfa_sms"
                           <?= (isset($_POST['two_factor_type']) && $_POST['two_factor_type'] === 'sms') ? 'checked' : '' ?>>
                    <label for="tfa_sms">
                        📱<br><strong>SMS</strong><br>
                        <small>Requires phone</small>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <input type="password" 
                   name="password" 
                   class="form-input" 
                   placeholder="🔒 Password (min 6 characters)" 
                   required>
        </div>
        
        <div class="form-group">
            <input type="password" 
                   name="confirm_password" 
                   class="form-input" 
                   placeholder="🔒 Confirm Password" 
                   required>
        </div>
        
        <button type="submit" class="register-btn">
            🎆 Create Account
        </button>
    </form>

    <div class="nav-links">
        <p style="margin: 0 0 15px 0; color: #666;">Already have an account?</p>
        <a href="login.php" class="nav-link">🔐 Login Here</a>
        <a href="index.php" class="nav-link">← Back to Home</a>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666; text-align: center;">
        🛡️ <strong>Security:</strong> Your data is protected with enterprise-grade encryption and 2FA security.
    </div>
</div>

<script>
// Enhanced form validation and UX
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const inputs = form.querySelectorAll('.form-input');
    const submitBtn = form.querySelector('.register-btn');
    
    // Real-time validation feedback
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Remove error styling on input
            this.style.borderColor = '#e1e8ed';
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        switch(field.name) {
            case 'username':
                isValid = value.length >= 3;
                break;
            case 'email':
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                break;
            case 'password':
                isValid = value.length >= 6;
                break;
            case 'confirm_password':
                const password = form.querySelector('input[name="password"]').value;
                isValid = value === password;
                break;
        }
        
        if (!isValid && value !== '') {
            field.style.borderColor = '#e74c3c';
            field.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        } else if (isValid && value !== '') {
            field.style.borderColor = '#27ae60';
            field.style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.1)';
        }
    }
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<span class="loading"></span> Creating Account...';
        submitBtn.disabled = true;
    });
    
    // 2FA option selection enhancement
    const tfaOptions = document.querySelectorAll('.tfa-option');
    tfaOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update visual selection
            tfaOptions.forEach(opt => opt.style.borderColor = '#e1e8ed');
            this.style.borderColor = '#667eea';
        });
    });
});
</script>
</body>
</html>