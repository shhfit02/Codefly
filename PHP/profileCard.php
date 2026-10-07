<?php
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required.',
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$profileBundle = fetchUserProfile($conn, $userId);

$profile = $profileBundle['profile'] ?? [];
$account = $profileBundle['account'] ?? [];
$meta = $profileBundle['meta'] ?? [];

$rank = $profileBundle['rank'] ?? PROFILE_DEFAULT_RANK;
$lessons = updateRankMissions($conn, $userId, $meta);
$streak = isset($meta['login_streak']) ? (int)$meta['login_streak'] : 0;
$xpTotal = isset($meta['xp_total']) ? (int)$meta['xp_total'] : ((int)($rank['score'] ?? 0));

$conn->close();

$isAdmin = strtolower($account['level'] ?? 'user') === 'admin';
$adminRank = [
    'title' => 'Admin (Not Ranked)',
    'note' => 'Admin accounts are excluded from ranks.',
    'badge' => PROFILE_DEFAULT_RANK['badge'],
    'status' => 'Not Ranked',
];
$workspace = workspaceAccess($rank, $isAdmin, $xpTotal);
$rankPayload = $isAdmin ? $adminRank : [
    'title' => $rank['title'] ?? PROFILE_DEFAULT_RANK['title'],
    // The stored note can be stale ("eligible to fly" while the workspace is still locked),
    // so the note follows the same rule as the workspace gate.
    'note' => $workspace['allowed'] ? ($rank['note'] ?? PROFILE_DEFAULT_RANK['note']) : 'Not cleared to fly yet',
    'badge' => $rank['badge'] ?? PROFILE_DEFAULT_RANK['badge'],
    'status' => $rank['status'] ?? PROFILE_DEFAULT_RANK['status'],
];

echo json_encode([
    'status' => 'success',
    'data' => [
        'fullName' => $profile['fullName'] ?? $account['username'],
        'username' => $account['username'] ?? '',
        'level' => $account['level'] ?? 'user',
        'age' => $profile['age'],
        'gender' => $profile['gender'],
        'avatar' => $profile['avatar'] ?? PROFILE_DEFAULT_AVATAR,
        'rank' => $rankPayload,
        'lessons' => $lessons,
        'streak' => $streak,
        'xp' => $xpTotal,
        'workspace' => $workspace,
        'nextRank' => $isAdmin ? null : nextRankForXp($xpTotal),
    ],
]);
