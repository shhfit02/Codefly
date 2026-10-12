<?php
require_once 'db.php';
require_once 'profile_helpers.php';

pruneOrphanRanks($conn);

header('Content-Type: application/json');

/**
 * Returns remaining basic lessons (lessons) based on stored quiz scores.
 */
function computePendingBasicMissions(array $meta): int
{
    return computeCompletedMissions($meta);
}

try {
    // Filter out admin accounts before building leaderboard rows.
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.level,
            u.created_at,
            COALESCE(p.full_name, u.username) AS full_name
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE LOWER(u.level) <> 'admin'
        ORDER BY u.created_at DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new RuntimeException('Unable to load users.');
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $userId = (int)$row['id'];
        $users[$userId] = [
            'userId' => $userId,
            'username' => $row['username'],
            'fullName' => $row['full_name'] ?? $row['username'],
            'level' => $row['level'] ?? 'user',
            'created_at' => $row['created_at'],
            'meta' => [],
        ];
    }
    $result->close();

    $ranks = [];
    if (!empty($users)) {
        $ids = implode(',', array_keys($users));
        $metaSql = "
            SELECT user_id, meta_key, meta_value
            FROM user_profile_meta
            WHERE user_id IN ($ids)
        ";
        $metaResult = $conn->query($metaSql);
        if ($metaResult) {
            while ($meta = $metaResult->fetch_assoc()) {
                $userId = (int)$meta['user_id'];
                if (!isset($users[$userId])) {
                    continue;
                }
                $users[$userId]['meta'][$meta['meta_key']] = $meta['meta_value'];
            }
            $metaResult->close();
        }

        ensureRankTable($conn);
        $rankSql = "
            SELECT user_id, rank_title, rank_note, rank_badge, rank_status, rank_score, rank_missions
            FROM user_ranks
            WHERE user_id IN ($ids)
        ";
        $rankResult = $conn->query($rankSql);
        if ($rankResult) {
            while ($row = $rankResult->fetch_assoc()) {
                $userId = (int)$row['user_id'];
                $ranks[$userId] = [
                    'title' => $row['rank_title'],
                    'note' => $row['rank_note'],
                    'badge' => $row['rank_badge'],
                    'status' => $row['rank_status'],
                    'score' => (int)$row['rank_score'],
                    'lessons' => (int)$row['rank_missions'],
                ];
            }
            $rankResult->close();
        }

        $missingRankIds = array_diff(array_keys($users), array_keys($ranks));
        foreach ($missingRankIds as $missingId) {
            $seedRank = [];
            if (!empty($users[$missingId]['meta'])) {
                $seedRank = extractRankFromMeta($users[$missingId]['meta']);
            }
            $ranks[$missingId] = ensureRankRecord($conn, $missingId, $seedRank);
        }
    }

    $data = [];
    foreach ($users as $user) {
        $userId = $user['userId'];
        $level = strtolower($user['level'] ?? 'user');
        if ($level === 'admin') {
            continue;
        }
        $meta = $user['meta'] ?? [];
        $rank = $ranks[$userId] ?? PROFILE_DEFAULT_RANK;
        $joined = $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : '--';
        $rankTitle = $rank['title'] ?? 'Bronze';
        // Lowercase first: stripping [^a-z] from "Bronze" would leave "ronze".
        $rankKey = preg_replace('/[^a-z]/', '', strtolower($rankTitle)) ?: 'bronze';
        $lessonsPending = computeCompletedMissions($meta);

        $data[] = [
            'username' => $user['username'],
            'fullName' => $user['fullName'],
            'avatar' => $meta['avatar_path'] ?? PROFILE_DEFAULT_AVATAR,
            'joined' => $joined,
            'score' => isset($rank['score']) ? (int)$rank['score'] : 0,
            'lessons' => $lessonsPending,
            'rank' => $rankTitle,
            'rankKey' => $rankKey,
            'status' => $rank['status'] ?? 'Not Eligible',
            'badge' => $rank['badge'] ?? PROFILE_DEFAULT_RANK['badge'],
            'level' => $user['level'] ?? 'user',
        ];
    }

    respondJson(200, [
        'status' => 'success',
        'data' => $data,
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to load leaderboard data.',
    ]);
} finally {
    $conn->close();
}
