<?php
// Generate 6-digit 2FA code
function generate2FACode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Send email via PHPMailer
function sendEmail2FACode($email, $code) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Disable SSL verification for testing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(false);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "Your verification code is: $code\nThis code expires in 10 minutes.";
        
        if ($mail->send()) {
            file_put_contents(__DIR__.'/email_success.log', "[".date('Y-m-d H:i:s')."] Code sent to: $email\n", FILE_APPEND);
            return true;
        }
        return false;
        
    } catch (Exception $e) {
        file_put_contents(__DIR__.'/email_error.log', "[".date('Y-m-d H:i:s')."] Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Store 2FA code in DB - FIXED VERSION (uses database time)
function store2FACode($pdo, $user_id, $code) {
    try {
        // Use database time for consistency
        $stmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, NOW() + INTERVAL '10 minutes')");
        return $stmt->execute([$user_id, $code]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Verify 2FA code - FIXED VERSION (uses database time)
function verify2FACode($pdo, $user_id, $code) {
    try {
        // Use database time (NOW()) for accurate comparison
        $stmt = $pdo->prepare("SELECT * FROM two_factor_codes WHERE user_id = ? AND code = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, trim($code)]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Delete the used code
            $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE id = ?");
            $stmt->execute([$result['id']]);
            return true;
        }
        return false;
        
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        return false;
    }
}
?>