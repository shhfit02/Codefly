<?php
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Please sign in to view your profile.',
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $profileBundle = fetchUserProfile($conn, $userId);
    $settings = fetchUserSettings($conn, $userId);

    respondJson(200, [
        'status' => 'success',
        'data' => [
            'profile' => $profileBundle['profile'],
            'account' => $profileBundle['account'],
            'stats' => $profileBundle['stats'],
            'settings' => $settings,
            'rank' => $profileBundle['rank'],
        ],
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to load your profile right now.',
    ]);
} finally {
    $conn->close();
}
