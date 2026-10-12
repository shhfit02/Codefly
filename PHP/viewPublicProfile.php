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

/**
 * Lessons done per module: a lesson counts once it has a quiz score (quiz_score_<lessonId>),
 * the same rule as the lesson counts elsewhere. A module is complete when all its lessons are.
 */
function moduleProgress(mysqli $conn, array $meta): array
{
    $modules = [];
    $result = $conn->query('
        SELECT m.id, m.title, l.id AS lesson_id
        FROM modules m
        LEFT JOIN lessons l ON l.module_id = m.id
        ORDER BY m.id, l.id
    ');
    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['id'];
        $modules[$id] ??= ['id' => $id, 'title' => $row['title'], 'lessons' => 0, 'completed' => 0];
        if ($row['lesson_id'] !== null) {
            $modules[$id]['lessons']++;
            $score = $meta['quiz_score_' . $row['lesson_id']] ?? '';
            if ($score !== '' && $score !== null) {
                $modules[$id]['completed']++;
            }
        }
    }
    return array_values($modules);
}

try {
    $bundle = fetchUserProfile($conn, $targetId);
    $meta = $bundle['meta'] ?? [];
    $xp = (int)($bundle['rank']['score'] ?? 0);

    // The streak only counts while it is unbroken: last login today or yesterday.
    $lastLogin = $meta['last_login_date'] ?? null;
    $streakAlive = $lastLogin !== null
        && in_array($lastLogin, [date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))], true);

    $modules = moduleProgress($conn, $meta);
    $stats = $bundle['stats'] + [
        'loginStreak' => $streakAlive ? (int)($meta['login_streak'] ?? 0) : 0,
        'lastLogin' => $lastLogin,
        'lessonsCompleted' => computeCompletedMissions($meta),
        'lessonsTotal' => array_sum(array_column($modules, 'lessons')),
        'modulesCompleted' => count(array_filter($modules, fn ($m) => $m['lessons'] > 0 && $m['completed'] === $m['lessons'])),
        'modules' => $modules,
        'nextRank' => nextRankForXp($xp),
    ];

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
