<?php
session_start();
require_once 'db.php';
require_once 'remember_helpers.php';
require_once 'toast_helpers.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
    [$selector] = array_pad(explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]), 2, null);
    if ($selector) {
        forgetRememberedUser($conn, $selector, $userId);
    } elseif ($userId) {
        forgetRememberedUser($conn, null, $userId);
    } else {
        clearRememberCookie();
    }
} elseif ($userId) {
    forgetRememberedUser($conn, null, $userId);
}

$_SESSION = [];
if (session_id()) {
    session_destroy();
}

queueToast('success', 'You have been logged out.');

$conn->close();

header('Location: ../index.html');
exit;
