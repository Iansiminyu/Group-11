<?php
// Autoload PHPMailer
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
}

// Generate 6-digit 2FA code
function generate2FACode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Send email via PHPMailer, fallback to simulation
function sendEmail2FACode($email, $code) {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body = "<h2>Smart Restaurant System</h2><p>Your verification code is: <b>$code</b></p><p>This code expires in 10 minutes.</p>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return simulateEmail($email, $code, $e->getMessage());
        }
    } else {
        return simulateEmail($email, $code, "PHPMailer not found");
    }
}

// Simulate email for dev
function simulateEmail($email, $code, $reason = "") {
    $log = "[".date('Y-m-d H:i:s')."] To: $email | Code: $code | $reason\n";
    file_put_contents(__DIR__.'/email_simulation.txt', $log, FILE_APPEND);
    return true;
}

// Simulate SMS
function sendSMS2FACode($phone, $code) {
    $log = "[".date('Y-m-d H:i:s')."] To: $phone | Code: $code\n";
    file_put_contents(__DIR__.'/sms_log.txt', $log, FILE_APPEND);
    return true;
}

// Store 2FA code
function store2FACode($pdo, $user_id, $code) {
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS two_factor_codes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES accounts(id),
            code VARCHAR(10) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $code, $expires_at]);
}

// Verify 2FA code
function verify2FACode($pdo, $user_id, $code) {
    $code = trim($code);
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT * FROM two_factor_codes 
        WHERE user_id = ? AND code = ? AND expires_at >= ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $code, $now]);
    $result = $stmt->fetch();

    if ($result) {
        $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    }
    return false;
}
?>
