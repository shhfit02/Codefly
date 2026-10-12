<?php
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';
require_once 'auth_helpers.php';

$requestedUser = trim($_GET['user'] ?? '');
$requestedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestedUser === '' && $requestedId <= 0) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'Missing user identifier.',
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Please sign in to view profiles.',
    ]);
    exit;
}

$stmt = null;
if ($requestedId > 0) {
    $stmt = $conn->prepare('SELECT id, username, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $requestedId);
} else {
    $stmt = $conn->prepare('SELECT id, username, created_at FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $requestedUser);
}
$stmt->execute();
$result = $stmt->get_result();
$target = $result->fetch_assoc();
$stmt->close();

if (!$target) {
    respondJson(404, [
        'status' => 'error',
        'message' => 'Pilot not found.',
    ]);
    $conn->close();
    exit;
}

$targetId = (int)$target['id'];
$isOwner = isset($_SESSION['user_id']) && ((int)$_SESSION['user_id'] === $targetId);
// Email is private: only the owner and admins (adminViewProfile.php) may see it.
$canSeeEmail = $isOwner || isAdminSession($conn);

try {
    $bundle = fetchUserProfile($conn, $targetId);
    $stats = profileProgressStats($conn, $bundle);

    $publicPayload = [
        'profile' => $bundle['profile'],
        'account' => [
            // The id is only needed for the admin "Edit user" link.
            'id' => $canSeeEmail ? $targetId : null,
            'username' => $bundle['account']['username'] ?? '',
            'memberSince' => $bundle['account']['memberSince'] ?? null,
            'email' => $canSeeEmail ? ($bundle['account']['email'] ?? '') : '',
            'level' => $bundle['account']['level'] ?? 'user',
        ],
        'stats' => $stats,
        'rank' => $bundle['rank'],
        'isOwner' => $isOwner,
    ];

    respondJson(200, [
        'status' => 'success',
        'data' => $publicPayload,
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to load this profile right now.',
    ]);
} finally {
    $conn->close();
}
