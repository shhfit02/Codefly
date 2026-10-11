<?php

session_start();
require_once 'db.php';
require_once 'profile_helpers.php';
require_once 'remember_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Authentication required.',
    ]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported request method.',
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

$errors = [];
if ($currentPassword === '') {
    $errors[] = 'Current password is required.';
}
if ($newPassword === '') {
    $errors[] = 'New password is required.';
} elseif (strlen($newPassword) < 8) {
    $errors[] = 'New password must be at least 8 characters.';
}
if ($newPassword !== $confirmPassword) {
    $errors[] = 'New password and confirmation do not match.';
}

if (!empty($errors)) {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Unable to update password.',
        'errors' => $errors,
    ]);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (empty($hashedPassword)) {
        respondJson(404, [
            'status' => 'error',
            'message' => 'Account not found.',
        ]);
        $conn->close();
        exit;
    }

    if (!password_verify($currentPassword, $hashedPassword)) {
        respondJson(401, [
            'status' => 'error',
            'message' => 'Current password is incorrect.',
        ]);
        $conn->close();
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1');
    $update->bind_param('si', $newHash, $userId);
    $update->execute();
    $update->close();

    // Revoke "remember me" on other devices. If this browser was remembered, give it a
    // fresh token (rememberUser deletes all of the user's tokens first).
    if (getRememberCookieParts()) {
        rememberUser($conn, $userId);
    } else {
        forgetRememberedUser($conn, null, $userId);
    }

    respondJson(200, [
        'status' => 'success',
        'message' => 'Password updated successfully.',
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to update password right now.',
    ]);
} finally {
    $conn->close();
}
