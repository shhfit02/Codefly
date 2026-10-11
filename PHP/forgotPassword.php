<?php
session_start();
require_once 'db.php';
require_once 'toast_helpers.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../HTML/forgotPass.html");
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    queueToast('warning', 'Please enter your email address.');
    header("Location: ../HTML/forgotPass.html");
    exit;
}

$userStmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    queueToast('warning', 'No account found with that email.');
    header("Location: ../HTML/forgotPass.html");
    exit;
}

// One TAC per minute per account: stops email spam and cycling fresh codes for more
// guesses. Codes live 15 minutes, so a code issued under a minute ago expires in >14.
$recentStmt = $conn->prepare("SELECT expires_at FROM password_resets WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$recentStmt->bind_param("i", $user['id']);
$recentStmt->execute();
$recentStmt->bind_result($latestExpiry);
$hasRecent = $recentStmt->fetch();
$recentStmt->close();
if ($hasRecent && strtotime($latestExpiry) - time() > 14 * 60) {
    queueToast('warning', 'A TAC was just sent. Please wait a minute before requesting another.');
    header("Location: ../HTML/verifyTAC.html?email=" . urlencode($email));
    exit;
}

$deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$deleteStmt->bind_param("i", $user['id']);
$deleteStmt->execute();
$deleteStmt->close();

$tacCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$tacHash = password_hash($tacCode, PASSWORD_DEFAULT);
$expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');

$insertStmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
$insertStmt->bind_param("iss", $user['id'], $tacHash, $expiresAt);
$insertStmt->execute();
$insertStmt->close();

$mailSent = sendTacEmail($email, $user['username'], $tacCode);

if (!$mailSent) {
    // Never show the TAC in the browser: anyone could reset any account by typing its email.
    // The code is written to the server error log only, so local testing still works.
    error_log("Password reset TAC for user #{$user['id']}: {$tacCode}");
    $conn->close();
    queueToast('error', 'We could not send the TAC email right now. Please try again later or contact the admin.');
    header("Location: ../HTML/forgotPass.html");
    exit;
}

queueToast('success', 'TAC sent. Please check your inbox and enter the code to continue.');

$conn->close();

$verifyUrl = '../HTML/verifyTAC.html?email=' . urlencode($email);
header("Location: $verifyUrl");
exit;

function sendTacEmail(string $recipient, string $name, string $code): bool
{
    $configPath = __DIR__ . '/mail_config.php';
    if (!is_file($configPath)) {
        error_log('TAC email skipped: PHP/mail_config.php is missing (copy mail_config.example.php).');
        return false;
    }
    $config = require $configPath;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$config['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipient, $name ?? '');

        $mail->Subject = 'CodeFly Password Reset TAC';
        $mail->Body = "Hi {$name},\n\n"
            . "Here is your CodeFly password reset TAC: {$code}\n"
            . "This code expires in 15 minutes. Enter it on the verification screen to continue.\n\n"
            . "If you did not request this, please ignore this email.\n\n"
            . "Regards,\n"
            . "CodeFly Support";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('TAC email failed: ' . $mail->ErrorInfo);
        return false;
    }
}
