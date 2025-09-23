<?php
session_start();
require 'config.php';
require 'helpers.php';

if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $two_factor_type = $_POST['two_factor_type'];
    $stmt = $pdo->prepare("UPDATE accounts SET is_2fa_enabled=TRUE,two_factor_type=? WHERE id=?");
    $stmt->execute([$two_factor_type,$_SESSION['user_id']]);
    $_SESSION['success']="Two-factor authentication enabled successfully!";
    header("Location: dashboard.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enable 2FA - Smart Restaurant System</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Enhanced 2FA Setup Styling */
.tfa-setup-container {
    max-width: 550px;
    margin: 50px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.tfa-header {
    text-align: center;
    margin-bottom: 30px;
}

.tfa-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.tfa-option {
    border: 3px solid #e1e8ed;
    padding: 25px;
    border-radius: 15px;
    margin: 20px 0;
    background: rgba(248, 249, 250, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.tfa-option:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.1);
}

.tfa-option.selected {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.tfa-option.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: rgba(149, 165, 166, 0.1);
    border-color: #bdc3c7;
}

.tfa-option.disabled:hover {
    transform: none;
    box-shadow: none;
}

.tfa-option input[type="radio"] {
    margin-right: 15px;
    width: auto;
    transform: scale(1.2);
}

.option-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
}

.option-icon {
    font-size: 1.5rem;
    margin-right: 10px;
}

.option-description {
    color: #666;
    margin: 10px 0;
    line-height: 1.5;
}

.option-contact {
    font-weight: 600;
    color: #2c3e50;
    margin: 8px 0;
}

.option-badge {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(40, 167, 69, 0.1);
    color: #27ae60;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 8px;
}

.option-warning {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(255, 193, 7, 0.1);
    color: #856404;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 8px;
}

.setup-btn {
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
    margin: 30px 0 20px 0;
}

.setup-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
}

.setup-btn:active {
    transform: translateY(-1px);
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
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(102, 126, 234, 0.1);
    transition: all 0.3s ease;
    display: inline-block;
}

.nav-link:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .tfa-setup-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .tfa-option {
        padding: 20px;
    }
}
</style>
</head>
<body>
<div class="tfa-setup-container">
    <div class="tfa-header">
        <span class="tfa-icon">🔐</span>
        <h2 style="margin: 0; color: #2c3e50; font-size: 2rem;">Two-Factor Authentication</h2>
        <p style="color: #666; margin: 10px 0 0 0;">Secure your account with an extra layer of protection</p>
    </div>

    <div class="alert alert-info">
        <strong>🛡️ Enhanced Security:</strong> Two-factor authentication significantly improves your account security by requiring a verification code in addition to your password.
    </div>

    <form method="POST" id="tfaSetupForm">
        <div class="tfa-option" data-method="email">
            <div class="option-header">
                <input type="radio" name="two_factor_type" value="email" id="email_2fa" checked>
                <span class="option-icon">📧</span>
                <label for="email_2fa">Email Verification</label>
            </div>
            <div class="option-description">
                We'll send a 6-digit verification code to your registered email address.
            </div>
            <div class="option-contact">
                📧 <?= htmlspecialchars($user['email']) ?>
            </div>
            <span class="option-badge">✓ Most Reliable</span>
        </div>

        <div class="tfa-option <?= empty($user['phone']) ? 'disabled' : '' ?>" data-method="sms">
            <div class="option-header">
                <input type="radio" 
                       name="two_factor_type" 
                       value="sms" 
                       id="sms_2fa"
                       <?= empty($user['phone']) ? 'disabled' : '' ?>>
                <span class="option-icon">📱</span>
                <label for="sms_2fa">SMS Verification</label>
            </div>
            <div class="option-description">
                <?php if(!empty($user['phone'])): ?>
                    We'll send a 6-digit code via SMS to your registered phone number.
                <?php else: ?>
                    ⚠️ SMS verification requires a phone number. Please add one to your profile first.
                <?php endif; ?>
            </div>
            <div class="option-contact">
                <?php if(!empty($user['phone'])): ?>
                    📱 <?= substr($user['phone'], 0, 3).'****'.substr($user['phone'], -2) ?>
                <?php else: ?>
                    🚫 No phone number on file
                <?php endif; ?>
            </div>
            <?php if(!empty($user['phone'])): ?>
                <span class="option-badge">✓ Faster Delivery</span>
            <?php else: ?>
                <span class="option-warning">⚠️ Setup Required</span>
            <?php endif; ?>
        </div>

        <button type="submit" class="setup-btn">
            <?= $user['is_2fa_enabled'] ? '⚙️ Update' : '🔐 Enable' ?> Two-Factor Authentication
        </button>
    </form>

    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>
    </div>

    <!-- Security Benefits -->
    <div style="margin-top: 30px; padding: 20px; background: rgba(40, 167, 69, 0.1); border-radius: 12px; border-left: 4px solid #28a745;">
        <strong>🛡️ Security Benefits:</strong>
        <ul style="margin: 10px 0 0 20px; color: #155724;">
            <li>Prevents unauthorized access even if password is compromised</li>
            <li>Protects against phishing and brute force attacks</li>
            <li>Meets industry security standards and compliance requirements</li>
            <li>Provides audit trail for all authentication attempts</li>
        </ul>
    </div>
</div>

<script>
// Enhanced 2FA setup functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tfaSetupForm');
    const options = document.querySelectorAll('.tfa-option');
    const radios = document.querySelectorAll('input[type="radio"]');
    const submitBtn = form.querySelector('.setup-btn');
    
    // Option selection handling
    options.forEach(option => {
        if (!option.classList.contains('disabled')) {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio && !radio.disabled) {
                    radio.checked = true;
                    updateSelection();
                }
            });
        }
    });
    
    // Radio button change handling
    radios.forEach(radio => {
        radio.addEventListener('change', updateSelection);
    });
    
    function updateSelection() {
        options.forEach(option => {
            const radio = option.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        const selectedMethod = form.querySelector('input[type="radio"]:checked').value;
        submitBtn.innerHTML = `<span class="loading"></span> Setting up ${selectedMethod.toUpperCase()} 2FA...`;
        submitBtn.disabled = true;
    });
    
    // Initialize selection
    updateSelection();
});
</script>
</body>
</html>