<?php
session_start();

require_once 'db.php';
require_once 'profile_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Authentication required.',
    ]);
    exit;
}

$requestingUserId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT level FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $requestingUserId);
$stmt->execute();
$stmt->bind_result($requestingLevel);
$stmt->fetch();
$stmt->close();

if (strtolower($requestingLevel ?? 'user') !== 'admin') {
    respondJson(403, [
        'status' => 'error',
        'message' => 'Admin privileges required.',
    ]);
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported method.',
    ]);
    $conn->close();
    exit;
}

$targetId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($targetId <= 0) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'Invalid user id.',
    ]);
    $conn->close();
    exit;
}

if ($targetId === $requestingUserId) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'You cannot delete your own account.',
    ]);
    $conn->close();
    exit;
}

$check = $conn->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
$check->bind_param('i', $targetId);
$check->execute();
$check->bind_result($targetUsername);
$check->fetch();
$check->close();

if (!$targetUsername) {
    respondJson(404, [
        'status' => 'error',
        'message' => 'User not found.',
    ]);
    $conn->close();
    exit;
}

$delete = $conn->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
$delete->bind_param('i', $targetId);
$ok = $delete->execute();
$delete->close();

if (!$ok) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to delete user right now.',
    ]);
    $conn->close();
    exit;
}

respondJson(200, [
    'status' => 'success',
    'message' => 'User deleted.',
    'data' => [
        'id' => $targetId,
        'username' => $targetUsername,
    ],
]);

$conn->close();
