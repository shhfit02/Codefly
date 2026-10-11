<?php

require_once __DIR__ . '/notification_helpers.php';

if (!defined('PROFILE_DEFAULT_AVATAR')) {
    define('PROFILE_DEFAULT_AVATAR', '../Assets/StudentProfile.png');
}

if (!defined('PROFILE_DEFAULT_COVER')) {
    define('PROFILE_DEFAULT_COVER', '../Assets/background-Profile.jpg');
}

if (!defined('PROFILE_DEFAULT_MISSIONS')) {
    define('PROFILE_DEFAULT_MISSIONS', 0);
}

if (!defined('PROFILE_DEFAULT_RANK')) {
    define('PROFILE_DEFAULT_RANK', [
        'title' => 'Bronze',
        'note' => 'Not eligible to fly yet',
        'badge' => '../Assets/RankBronze.png',
        'status' => 'Not Eligible',
        'score' => 0,
        'lessons' => PROFILE_DEFAULT_MISSIONS,
    ]);
}

/**
 * Shared helpers for profile-centric endpoints.
 */

function ensureProfileRecord(mysqli $conn, int $userId): void
{
    $check = $conn->prepare('SELECT user_id FROM user_profiles WHERE user_id = ? LIMIT 1');
    $check->bind_param('i', $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $insert = $conn->prepare('INSERT INTO user_profiles (user_id, full_name, age, gender) VALUES (?, ?, NULL, ?)');
        $fullName = '';
        $gender = 'prefer-not';
        $insert->bind_param('iss', $userId, $fullName, $gender);
        $insert->execute();
        $insert->close();
    }

    $check->close();
}

function ensureProfileMetaTable(mysqli $conn): void
{
    static $metaReady = false;

    if ($metaReady) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS user_profile_meta (
    user_id INT NOT NULL,
    meta_key VARCHAR(64) NOT NULL,
    meta_value TEXT NULL,
    PRIMARY KEY (user_id, meta_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to ensure profile meta storage.');
    }

    $metaReady = true;
}

function ensureSettingsTable(mysqli $conn): void
{
    static $settingsReady = false;

    if ($settingsReady) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    email_alerts TINYINT(1) NOT NULL DEFAULT 1,
    in_app_notifications TINYINT(1) NOT NULL DEFAULT 1,
    sms_alerts TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to ensure user settings storage.');
    }

    $settingsReady = true;
}

function ensureRankTable(mysqli $conn): void
{
    static $rankReady = false;

    if ($rankReady) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS user_ranks (
    user_id INT PRIMARY KEY,
    rank_title VARCHAR(32) NOT NULL DEFAULT 'Bronze',
    rank_note VARCHAR(255) NOT NULL DEFAULT 'Not eligible to fly yet',
    rank_badge VARCHAR(255) NOT NULL DEFAULT '../Assets/RankBronze.png',
    rank_status VARCHAR(32) NOT NULL DEFAULT 'Not Eligible',
    rank_score INT NOT NULL DEFAULT 0,
    rank_missions INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to ensure rank storage.');
    }

    $rankReady = true;
}

function normalizeRankPayload(array $rank = []): array
{
    $defaults = PROFILE_DEFAULT_RANK;
    $lessons = isset($rank['lessons']) ? max(0, (int)$rank['lessons']) : PROFILE_DEFAULT_MISSIONS;

    return [
        'title' => $rank['title'] ?? $defaults['title'],
        'note' => $rank['note'] ?? $defaults['note'],
        'badge' => $rank['badge'] ?? $defaults['badge'],
        'status' => $rank['status'] ?? $defaults['status'],
        'score' => isset($rank['score']) ? (int)$rank['score'] : $defaults['score'],
        'lessons' => $lessons,
    ];
}

function saveRankRecord(mysqli $conn, int $userId, array $rank): void
{
    ensureRankTable($conn);

    $rank = normalizeRankPayload($rank);
    $stmt = $conn->prepare('
        INSERT INTO user_ranks (user_id, rank_title, rank_note, rank_badge, rank_status, rank_score, rank_missions)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rank_title = VALUES(rank_title),
            rank_note = VALUES(rank_note),
            rank_badge = VALUES(rank_badge),
            rank_status = VALUES(rank_status),
            rank_score = VALUES(rank_score),
            rank_missions = VALUES(rank_missions)
    ');

    $stmt->bind_param(
        'issssii',
        $userId,
        $rank['title'],
        $rank['note'],
        $rank['badge'],
        $rank['status'],
        $rank['score'],
        $rank['lessons']
    );
    $stmt->execute();
    $stmt->close();
}

function ensureRankRecord(mysqli $conn, int $userId, array $seedRank = []): array
{
    ensureRankTable($conn);

    $stmt = $conn->prepare('
        SELECT rank_title, rank_note, rank_badge, rank_status, rank_score, rank_missions
        FROM user_ranks
        WHERE user_id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return [
            'title' => $row['rank_title'],
            'note' => $row['rank_note'],
            'badge' => $row['rank_badge'],
            'status' => $row['rank_status'],
            'score' => (int)$row['rank_score'],
            'lessons' => max(0, (int)$row['rank_missions']),
        ];
    }

    if (empty($seedRank)) {
        $meta = fetchProfileMeta($conn, $userId);
        if (!empty($meta)) {
            $seedRank = extractRankFromMeta($meta);
        }
    }

    $rank = normalizeRankPayload($seedRank);
    saveRankRecord($conn, $userId, $rank);

    return $rank;
}

function fetchUserRank(mysqli $conn, int $userId, ?array $seedMeta = null): array
{
    $seedRank = [];

    if ($seedMeta !== null) {
        $seedRank = extractRankFromMeta($seedMeta);
    }

    return ensureRankRecord($conn, $userId, $seedRank);
}

const WORKSPACE_RANK_HIERARCHY = ['bronze', 'silver', 'gold', 'ace', 'captain', 'commander'];
const WORKSPACE_MIN_RANK = 'ace';

/**
 * Workspace (Code Block) access: admins always; students from the Ace rank up.
 * Shared by HTML/workspace.php (the real gate) and profileCard.php (home page hints).
 */
function workspaceAccess(array $rank, bool $isAdmin, int $xp): array
{
    $rankKey = strtolower($rank['title'] ?? '');
    $rankIndex = array_search($rankKey, WORKSPACE_RANK_HIERARCHY, true);
    $minIndex = array_search(WORKSPACE_MIN_RANK, WORKSPACE_RANK_HIERARCHY, true);
    $allowed = $isAdmin || ($rankIndex !== false && $rankIndex >= $minIndex);

    $requiredXp = 0;
    foreach (rankTiers() as $tier) {
        if ($tier['key'] === WORKSPACE_MIN_RANK) {
            $requiredXp = $tier['min'];
        }
    }

    return [
        'allowed' => $allowed,
        'requiredRank' => WORKSPACE_MIN_RANK,
        'requiredXp' => $requiredXp,
        'xpRemaining' => $allowed ? 0 : max(0, $requiredXp - $xp),
    ];
}

/**
 * Next rank tier above the given XP (null at the top tier), for the home page progress bar.
 */
function nextRankForXp(int $xp): ?array
{
    $current = null;
    foreach (rankTiers() as $tier) {
        if ($xp < $tier['min']) {
            return [
                'key' => $tier['key'],
                'minXp' => $tier['min'],
                'fromXp' => $current['min'] ?? 0,
            ];
        }
        $current = $tier;
    }
    return null;
}

function rankTiers(): array
{
    return [
        ['min' => 0, 'key' => 'bronze', 'title' => 'Bronze', 'badge' => '../Assets/RankBronze.png', 'note' => 'Not eligible to fly yet'],
        ['min' => 400, 'key' => 'silver', 'title' => 'Silver', 'badge' => '../Assets/RankSilver.png', 'note' => 'Showing steady progress'],
        ['min' => 800, 'key' => 'gold', 'title' => 'Gold', 'badge' => '../Assets/RankGold.png', 'note' => 'Certified for lessons'],
        ['min' => 1200, 'key' => 'ace', 'title' => 'Ace', 'badge' => '../Assets/RankAce.png', 'note' => 'Elite pilot ready for advanced ops'],
        ['min' => 3000, 'key' => 'captain', 'title' => 'Captain', 'badge' => '../Assets/RankCaptain.png', 'note' => 'Leading squads to victory'],
        ['min' => 5000, 'key' => 'commander', 'title' => 'Commander', 'badge' => '../Assets/RankCommander.png', 'note' => 'Top of the fleet'],
    ];
}

/** Names students see in the UI (same mapping as displayRankTitle() in JS/home.js). */
const RANK_DISPLAY_NAMES = [
    'bronze' => 'Rookie',
    'silver' => 'Operator',
    'gold' => 'Specialist',
    'ace' => 'Elite Pilot',
    'captain' => 'Commander',
    'commander' => 'Grand Commander',
];

/** Notify a student that their rank changed (up or down), mentioning the Code Block unlock. */
function notifyRankChange(mysqli $conn, int $userId, string $oldTitle, string $newTitle): void
{
    $oldKey = strtolower($oldTitle);
    $newKey = strtolower($newTitle);
    $oldIndex = array_search($oldKey, WORKSPACE_RANK_HIERARCHY, true);
    $newIndex = array_search($newKey, WORKSPACE_RANK_HIERARCHY, true);
    $name = RANK_DISPLAY_NAMES[$newKey] ?? $newTitle;
    $minIndex = array_search(WORKSPACE_MIN_RANK, WORKSPACE_RANK_HIERARCHY, true);

    if ($oldIndex !== false && $newIndex !== false && $newIndex < $oldIndex) {
        $message = "Your rank is now {$name}.";
        $url = '../HTML/leaderboard.php';
    } elseif ($newIndex === $minIndex && ($oldIndex === false || $oldIndex < $minIndex)) {
        $message = "Promoted to {$name}! The Code Block workspace is now unlocked.";
        $url = '../HTML/workspace.php';
    } else {
        $message = "Promoted to {$name}! Keep flying.";
        $url = '../HTML/leaderboard.php';
    }

    notifySafely(fn () => createNotification($conn, $userId, 'rank', $message, [
        'oldRank' => $oldTitle,
        'newRank' => $newTitle,
        'url' => $url,
    ]));
}

function resolveRankForScore(int $score): array
{
    $tiers = rankTiers();
    $score = max(0, $score);
    $resolved = $tiers[0];
    foreach ($tiers as $tier) {
        if ($score >= $tier['min']) {
            $resolved = $tier;
        }
    }

    $status = $resolved['key'] === 'bronze' ? 'Not Eligible' : 'Eligible';

    return [
        'title' => $resolved['title'],
        'note' => $resolved['note'],
        'badge' => $resolved['badge'],
        'status' => $status,
        'score' => $score,
    ];
}

function syncRankToScore(mysqli $conn, int $userId, int $score, ?array $seedMeta = null): array
{
    $currentRank = fetchUserRank($conn, $userId, $seedMeta);
    $resolved = resolveRankForScore($score);

    $newRank = [
        'title' => $resolved['title'],
        'note' => $resolved['note'],
        'badge' => $resolved['badge'],
        'status' => $resolved['status'],
        'score' => $resolved['score'],
        'lessons' => $currentRank['lessons'],
    ];

    $rankChanged = $newRank['title'] !== $currentRank['title'];
    $scoreChanged = $newRank['score'] !== $currentRank['score'];
    $otherChanged = $newRank['note'] !== $currentRank['note']
        || $newRank['badge'] !== $currentRank['badge']
        || $newRank['status'] !== $currentRank['status']
        || $newRank['lessons'] !== $currentRank['lessons'];

    if ($rankChanged || $scoreChanged || $otherChanged) {
        saveRankRecord($conn, $userId, $newRank);
    }

    if ($rankChanged) {
        notifyRankChange($conn, $userId, $currentRank['title'], $newRank['title']);
    }

    return $newRank;
}

function pruneOrphanRanks(mysqli $conn): void
{
    ensureRankTable($conn);
    $sql = '
        DELETE r
        FROM user_ranks r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE u.id IS NULL
    ';
    $conn->query($sql);
}

function fetchProfileMeta(mysqli $conn, int $userId): array
{
    ensureProfileMetaTable($conn);

    $stmt = $conn->prepare('SELECT meta_key, meta_value FROM user_profile_meta WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $meta = [];
    while ($row = $result->fetch_assoc()) {
        $meta[$row['meta_key']] = $row['meta_value'];
    }

    $stmt->close();

    return $meta;
}

function saveProfileMeta(mysqli $conn, int $userId, array $meta): void
{
    if (empty($meta)) {
        return;
    }

    ensureProfileMetaTable($conn);

    $stmt = $conn->prepare('INSERT INTO user_profile_meta (user_id, meta_key, meta_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)');

    foreach ($meta as $key => $value) {
        $metaKey = substr($key, 0, 64);
        $metaValue = $value === null ? '' : (string)$value;
        $stmt->bind_param('iss', $userId, $metaKey, $metaValue);
        $stmt->execute();
    }

    $stmt->close();
}

function fetchUserProfile(mysqli $conn, int $userId): array
{
    ensureProfileRecord($conn, $userId);
    $meta = fetchProfileMeta($conn, $userId);
    $rank = fetchUserRank($conn, $userId, $meta);
    $lessonsCompleted = updateRankMissions($conn, $userId, $meta);
    $rank['lessons'] = $lessonsCompleted;

    $stmt = $conn->prepare('
        SELECT 
            u.username,
            u.email,
            u.created_at,
            u.level,
            COALESCE(p.full_name, u.username) AS full_name,
            p.age,
            p.gender
        FROM users u
        JOIN user_profiles p ON p.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: [];
    $stmt->close();

    $profile = [
        'fullName' => $row['full_name'] ?? '',
        'age' => isset($row['age']) ? (int)$row['age'] : null,
        'gender' => $row['gender'] ?? 'prefer-not',
        'school' => $meta['school'] ?? '',
        'phone' => $meta['phone'] ?? '',
        'location' => $meta['location'] ?? '',
        'bio' => $meta['bio'] ?? '',
        'avatar' => $meta['avatar_path'] ?? PROFILE_DEFAULT_AVATAR,
        'cover' => $meta['cover_path'] ?? PROFILE_DEFAULT_COVER,
    ];

    $account = [
        'username' => $row['username'] ?? '',
        'email' => $row['email'] ?? '',
        'memberSince' => isset($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : null,
        'level' => $row['level'] ?? 'user',
    ];

    $stats = [
        'flightHours' => (int)($meta['flight_hours'] ?? 0),
        'lessonScore' => (int)($meta['lesson_score'] ?? 0),
        'xp' => (int)($rank['score'] ?? 0),
        'missionsCompleted' => (int)($rank['lessons'] ?? 0),
        'certification' => $meta['certification'] ?? 'Cadet Pilot',
    ];

    return [
        'profile' => $profile,
        'account' => $account,
        'stats' => $stats,
        'rank' => $rank,
        'meta' => $meta,
    ];
}

function fetchUserSettings(mysqli $conn, int $userId): array
{
    ensureSettingsTable($conn);

    $stmt = $conn->prepare('
        SELECT two_factor_enabled, email_alerts, in_app_notifications, sms_alerts
        FROM user_settings
        WHERE user_id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'twoFactor' => false,
            'emailAlerts' => true,
            'inAppNotifications' => true,
            'smsAlerts' => false,
        ];
    }

    return [
        'twoFactor' => (bool)$row['two_factor_enabled'],
        'emailAlerts' => (bool)$row['email_alerts'],
        'inAppNotifications' => (bool)$row['in_app_notifications'],
        'smsAlerts' => (bool)$row['sms_alerts'],
    ];
}

function saveUserSettings(mysqli $conn, int $userId, array $settings): void
{
    ensureSettingsTable($conn);

    $twoFactor = !empty($settings['twoFactor']) ? 1 : 0;
    $emailAlerts = !empty($settings['emailAlerts']) ? 1 : 0;
    $inApp = !empty($settings['inAppNotifications']) ? 1 : 0;
    $sms = !empty($settings['smsAlerts']) ? 1 : 0;

    $stmt = $conn->prepare('
        INSERT INTO user_settings (user_id, two_factor_enabled, email_alerts, in_app_notifications, sms_alerts)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            two_factor_enabled = VALUES(two_factor_enabled),
            email_alerts = VALUES(email_alerts),
            in_app_notifications = VALUES(in_app_notifications),
            sms_alerts = VALUES(sms_alerts)
    ');
    $stmt->bind_param('iiiii', $userId, $twoFactor, $emailAlerts, $inApp, $sms);
    $stmt->execute();
    $stmt->close();
}

function isJsonRequest(): bool
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (stripos($accept, 'application/json') !== false) {
        return true;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    return stripos($contentType, 'application/json') !== false;
}

function readRequestBody(): array
{
    if (isJsonRequest()) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function respondJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
}

function countCompletedMissions(array $meta): int
{
    $completed = 0;
    foreach ($meta as $key => $value) {
        if (strpos($key, 'quiz_score_') !== 0) {
            continue;
        }
        if ($value === '' || $value === null) {
            continue;
        }
        $completed++;
    }
    return $completed;
}

function computeCompletedMissions(array $meta): int
{
    // Number of distinct lessons with a recorded quiz score (shown as "N lessons completed").
    return countCompletedMissions($meta);
}

function updateRankMissions(mysqli $conn, int $userId, array $meta): int
{
    $completed = computeCompletedMissions($meta);
    $currentRank = fetchUserRank($conn, $userId, $meta);

    if ($currentRank['lessons'] !== $completed) {
        $currentRank['lessons'] = $completed;
        saveRankRecord($conn, $userId, $currentRank);
    }

    return $completed;
}

function extractRankFromMeta(array $meta): array
{
    $defaults = PROFILE_DEFAULT_RANK;

    $rank = [
        'title' => $meta['rank_title'] ?? $defaults['title'],
        'note' => $meta['rank_note'] ?? $defaults['note'],
        'badge' => $meta['rank_badge'] ?? $defaults['badge'],
        'status' => $meta['rank_status'] ?? $defaults['status'],
        'score' => isset($meta['rank_score']) ? (int)$meta['rank_score'] : $defaults['score'],
        'lessons' => isset($meta['rank_missions'])
            ? (int)$meta['rank_missions']
            : (isset($meta['rank_lessons']) ? (int)$meta['rank_lessons'] : $defaults['lessons']),
    ];

    return normalizeRankPayload($rank);
}

function updateUserRank(mysqli $conn, int $userId, array $rankData): bool
{
    $allowedRanks = [
        'bronze' => ['badge' => '../Assets/RankBronze.png', 'note' => 'Not eligible to fly yet'],
        'silver' => ['badge' => '../Assets/RankSilver.png', 'note' => 'Showing steady progress'],
        'gold' => ['badge' => '../Assets/RankGold.png', 'note' => 'Certified for lessons'],
        'ace' => ['badge' => '../Assets/RankAce.png', 'note' => 'Elite pilot ready for advanced ops'],
        'captain' => ['badge' => '../Assets/RankCaptain.png', 'note' => 'Leading squads to victory'],
        'commander' => ['badge' => '../Assets/RankCommander.png', 'note' => 'Top of the fleet'],
    ];

    $requestedTitle = strtolower(trim($rankData['title'] ?? ''));
    if (!isset($allowedRanks[$requestedTitle])) {
        throw new InvalidArgumentException('Unknown rank specified.');
    }

    $currentRank = fetchUserRank($conn, $userId);

    $newRank = [
        'title' => ucfirst($requestedTitle),
        'note' => $rankData['note'] ?? $allowedRanks[$requestedTitle]['note'],
        'badge' => $rankData['badge'] ?? $allowedRanks[$requestedTitle]['badge'],
        'status' => $rankData['status'] ?? ($requestedTitle === 'bronze' ? 'Not Eligible' : 'Eligible'),
        'score' => isset($rankData['score']) ? (int)$rankData['score'] : $currentRank['score'],
        'lessons' => isset($rankData['lessons']) ? (int)$rankData['lessons'] : $currentRank['lessons'],
    ];

    if ($newRank['title'] === $currentRank['title']
        && $newRank['note'] === $currentRank['note']
        && $newRank['badge'] === $currentRank['badge']
        && $newRank['status'] === $currentRank['status']
        && $newRank['score'] === $currentRank['score']
        && $newRank['lessons'] === $currentRank['lessons']
    ) {
        return false;
    }

    saveRankRecord($conn, $userId, $newRank);

    if ($newRank['title'] !== $currentRank['title']) {
        notifyRankChange($conn, $userId, $currentRank['title'], $newRank['title']);
    }

    return true;
}
