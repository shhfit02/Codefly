<?php
session_start();
require_once 'db.php';
require_once 'toast_helpers.php';
require_once 'password_reset_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/forgotPass.html');
    exit;
}

$sessionUserId = $_SESSION['password_reset_user_id'] ?? null;
$sessionResetId = $_SESSION['password_reset_reset_id'] ?? null;
$sessionEmail = $_SESSION['password_reset_email'] ?? '';

if (!$sessionUserId || !$sessionResetId) {
    queueToast('warning', 'Please verify the TAC sent to your email before resetting your password.');
    header('Location: ../HTML/forgotPass.html');
    exit;
}

$email = trim($_POST['email'] ?? $sessionEmail);
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$redirectBack = '../HTML/resetPassword.html?email=' . urlencode($email ?: $sessionEmail);

if ($newPassword === '' || $confirmPassword === '') {
    queueToast('warning', 'Please provide and confirm your new password.');
    header('Location: ' . $redirectBack);
    exit;
}

if ($newPassword !== $confirmPassword) {
    queueToast('warning', 'Passwords do not match.');
    header('Location: ' . $redirectBack);
    exit;
}

if (strlen($newPassword) < 8) {
    queueToast('warning', 'Password must be at least 8 characters long.');
    header('Location: ' . $redirectBack);
    exit;
}

ensurePasswordResetsTable($conn);

$resetStmt = $conn->prepare('SELECT id, expires_at, used_at FROM password_resets WHERE id = ? AND user_id = ? LIMIT 1');
$resetStmt->bind_param('ii', $sessionResetId, $sessionUserId);
$resetStmt->execute();
$resetResult = $resetStmt->get_result();
$resetRow = $resetResult->fetch_assoc();
$resetStmt->close();

if (!$resetRow) {
    queueToast('warning', 'Reset session is invalid. Please request a new TAC.');
    header('Location: ../HTML/forgotPass.html');
    exit;
}

if ($resetRow['used_at'] !== null || strtotime($resetRow['expires_at']) < time()) {
    queueToast('warning', 'Your TAC has expired. Please request a new code.');
    header('Location: ../HTML/forgotPass.html');
    exit;
}

$conn->begin_transaction();

try {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateUser = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateUser->bind_param('si', $hashedPassword, $sessionUserId);
    $updateUser->execute();
    $updateUser->close();

    $markUsed = $conn->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
    $markUsed->bind_param('i', $resetRow['id']);
    $markUsed->execute();
    $markUsed->close();

    $conn->commit();
    $conn->close();

    unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_reset_id'], $_SESSION['password_reset_email']);

    queueToast('success', 'Password updated successfully. You can now log in.');
    header('Location: ../index.html');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    $conn->close();
    queueToast('error', 'Unable to reset your password right now. Please try again.');
    header('Location: ' . $redirectBack);
    exit;
}
