<?php
// helpers.php - Utility functions for 2FA, email, SMS

function generate2FACode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

function sendEmail2FACode($email, $code) {
    if (file_exists('vendor/autoload.php')) {
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->Timeout    = 30;

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Smart Restaurant Verification Code';
            $mail->Body    = "<p>Your verification code is: <strong>$code</strong></p>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: {$mail->ErrorInfo}");
            return simulateEmail($email, $code);
        }
    } else {
        return simulateEmail($email, $code);
    }
}

function simulateEmail($email, $code) {
    file_put_contents('email_simulation.txt', "[".date('Y-m-d H:i:s')."] To: $email | Code: $code\n", FILE_APPEND);
    return true;
}

function sendSMS2FACode($phone, $code) {
    file_put_contents('sms_log.txt', "[".date('Y-m-d H:i:s')."] To: $phone | Code: $code\n", FILE_APPEND);
    return true;
}

function store2FACode($pdo, $user_id, $code) {
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS two_factor_codes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES accounts(id) ON DELETE CASCADE,
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

function verify2FACode($pdo, $user_id, $code) {
    $stmt = $pdo->prepare("
        SELECT * FROM two_factor_codes 
        WHERE user_id = ? AND code = ? AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $code]);
    $result = $stmt->fetch();

    if ($result) {
        $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    }

    return false;
}
