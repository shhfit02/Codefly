<?php
session_start();
require_once 'db.php';
require_once 'toast_helpers.php';
require_once 'password_reset_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/forgotPass.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$tacCode = trim($_POST['tac_code'] ?? '');

if ($email === '' || $tacCode === '') {
    queueToast('warning', 'Please enter the TAC sent to your email.');
    header('Location: ../HTML/verifyTAC.html?email=' . urlencode($email));
    exit;
}

if (!preg_match('/^\d{6}$/', $tacCode)) {
    queueToast('warning', 'TAC must be a 6-digit code.');
    header('Location: ../HTML/verifyTAC.html?email=' . urlencode($email));
    exit;
}

$userStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$userStmt->bind_param('s', $email);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    queueToast('warning', 'No account found. Please check the email entered.');
    header('Location: ../HTML/forgotPass.html');
    exit;
}

ensurePasswordResetsTable($conn);

$resetStmt = $conn->prepare('SELECT id, token_hash, expires_at, used_at FROM password_resets WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$resetStmt->bind_param('i', $user['id']);
$resetStmt->execute();
$resetResult = $resetStmt->get_result();
$resetRow = $resetResult->fetch_assoc();
$resetStmt->close();

if (!$resetRow) {
    queueToast('warning', 'No TAC request was found. Please request a new code.');
    header('Location: ../HTML/forgotPass.html?email=' . urlencode($email));
    exit;
}

if ($resetRow['used_at'] !== null || strtotime($resetRow['expires_at']) < time()) {
    queueToast('warning', 'The TAC has expired. Please request a new code.');
    header('Location: ../HTML/forgotPass.html?email=' . urlencode($email));
    exit;
}

if (!password_verify($tacCode, $resetRow['token_hash'])) {
    queueToast('error', 'The TAC entered is incorrect. Please try again.');
    header('Location: ../HTML/verifyTAC.html?email=' . urlencode($email));
    exit;
}

$_SESSION['password_reset_user_id'] = (int)$user['id'];
$_SESSION['password_reset_email'] = $email;
$_SESSION['password_reset_reset_id'] = (int)$resetRow['id'];

queueToast('success', 'TAC verified. You may now reset your password.');

header('Location: ../HTML/resetPassword.html?email=' . urlencode($email));
exit;
