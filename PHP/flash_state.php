<?php
session_start();
require_once 'db.php';
require_once 'remember_helpers.php';

header('Content-Type: application/json');

$autoLoginUser = attemptRememberedLogin($conn);
$isAuthenticated = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? ($autoLoginUser['username'] ?? null);

$messages = $_SESSION['toast_messages'] ?? [];

if (!is_array($messages)) {
    $messages = [];
}

unset($_SESSION['toast_messages']);

echo json_encode([
    'toasts' => $messages,
    'authenticated' => $isAuthenticated,
    'username' => $username,
]);

$conn->close();
