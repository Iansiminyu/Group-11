<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Enable 2FA - Smart Restaurant System' ?></title>
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

    <div class="alert alert-info">
        <strong>🛡️ Enhanced Security:</strong> Two-factor authentication significantly improves your account security by requiring a verification code in addition to your password.
    </div>

    <form method="POST" id="tfaSetupForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        
        <div class="tfa-option" data-method="email">
            <div class="option-header">
                <input type="radio" name="two_factor_type" value="email" id="email_2fa" <?= ($user->getTwoFactorType() === 'email' || !$user->getTwoFactorType()) ? 'checked' : '' ?>>
                <span class="option-icon">📧</span>
                <label for="email_2fa">Email Verification</label>
            </div>
            <div class="option-description">
                We'll send a 6-digit verification code to your registered email address.
            </div>
            <div class="option-contact">
                📧 <?= e($user->getEmail()) ?>
            </div>
        </div>
        
        <div class="tfa-option" data-method="sms">
            <div class="option-header">
                <input type="radio" name="two_factor_type" value="sms" id="sms_2fa" <?= $user->getTwoFactorType() === 'sms' ? 'checked' : '' ?>>
                <span class="option-icon">📱</span>
                <label for="sms_2fa">SMS Verification</label>
            </div>
            <div class="option-description">
                We'll send a 6-digit verification code to your phone via SMS.
            </div>
            <?php if ($user->getPhone()): ?>
                <div class="option-contact">
                    📱 <?= e($user->getPhone()) ?>
                </div>
            <?php else: ?>
                <div class="option-description" style="color: #e74c3c;">
                    ⚠️ You need to add a phone number to your profile first.
                </div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="setup-btn" id="setupBtn">
            <?= $user->is2FAEnabled() ? '⚙️ Update 2FA Settings' : '🔐 Enable Two-Factor Authentication' ?>
        </button>
    </form>

    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>
    </div>

    <!-- Security Notice -->
    <div style="margin-top: 30px; padding: 15px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; font-size: 0.85rem; color: #666; text-align: center;">
        🔒 <strong>Security:</strong> 2FA codes expire in 10 minutes and can only be used once.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tfaSetupForm');
    const setupBtn = document.getElementById('setupBtn');
    const options = document.querySelectorAll('.tfa-option');
    const radioButtons = document.querySelectorAll('input[name="two_factor_type"]');
    
    // Handle option selection
    options.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            updateSelection();
        });
    });
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', updateSelection);
    });
    
    function updateSelection() {
        options.forEach(option => {
            const radio = option.querySelector('input[type="radio"]');
            if (radio.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }
    
    // Initial selection
    updateSelection();
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        setupBtn.innerHTML = '<span class="loading"></span> Updating Settings...';
        setupBtn.disabled = true;
    });
});
</script>
</body>
</html>
