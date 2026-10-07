<?php
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';
require_once 'auth_helpers.php';

// Ranks normally follow XP (syncRankToScore). Manual overrides are an admin tool only;
// letting students call this would let anyone promote themselves and unlock the workspace.
$adminId = requireAdminJson($conn);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported request method.',
    ]);
    exit;
}

$input = readRequestBody();
$targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : $adminId;
$rankTitle = strtolower(trim($input['rank'] ?? $input['title'] ?? ''));
$score = isset($input['score']) ? (int)$input['score'] : null;
$lessons = isset($input['lessons']) ? (int)$input['lessons'] : null;

if ($rankTitle === '') {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Rank title is required.',
    ]);
    exit;
}

$rankPayload = [
    'title' => $rankTitle,
    'score' => $score,
    'lessons' => $lessons,
];

if (!empty($input['status'])) {
    $rankPayload['status'] = trim($input['status']);
}

try {
    $changed = updateUserRank($conn, $targetUserId, $rankPayload);
    $message = $changed ? 'Rank updated successfully.' : 'No changes detected.';

    respondJson(200, [
        'status' => 'success',
        'message' => $message,
    ]);
} catch (InvalidArgumentException $e) {
    respondJson(422, [
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to update rank.',
    ]);
} finally {
    $conn->close();
}
