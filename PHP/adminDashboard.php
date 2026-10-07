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

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT username, level FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($username, $level);
$stmt->fetch();
$stmt->close();

if (strtolower($level ?? 'user') !== 'admin') {
    respondJson(403, [
        'status' => 'error',
        'message' => 'Admin privileges required.',
    ]);
    $conn->close();
    exit;
}

function fetchScalar(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();

    return $value;
}

function countDistinctLoginsInRange(mysqli $conn, string $startDate, string $endDate): int
{
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id)
        FROM user_profile_meta
        WHERE meta_key = 'last_login_date'
          AND meta_value >= ?
          AND meta_value < ?
    ");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();
    return (int)($value ?? 0);
}

function countDistinctLoginsOnDate(mysqli $conn, string $date): int
{
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id)
        FROM user_profile_meta
        WHERE meta_key = 'last_login_date'
          AND meta_value = ?
    ");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();
    return (int)($value ?? 0);
}

try {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $monthStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');

    ensureProfileMetaTable($conn);
    ensureRankTable($conn);

    $moduleCount = (int)(fetchScalar($conn, 'SELECT COUNT(*) FROM modules') ?? 0);

    $totals = [
        'users' => (int)(fetchScalar($conn, 'SELECT COUNT(*) FROM users') ?? 0),
        'admins' => (int)(fetchScalar($conn, "SELECT COUNT(*) FROM users WHERE level = 'admin'") ?? 0),
        // Keep legacy key (newThisMonth) feeding the "Total Modules" card; use modules count for accuracy.
        'modules' => $moduleCount,
        'newThisMonth' => $moduleCount,
        'activeToday' => (int)(fetchScalar($conn, "SELECT COUNT(DISTINCT user_id) FROM user_profile_meta WHERE meta_key = 'last_login_date' AND meta_value = ?", 's', [$today]) ?? 0),
        'avgScore' => (int)round((float)(fetchScalar($conn, 'SELECT AVG(rank_score) FROM user_ranks') ?? 0)),
        'lessonsCompleted' => (int)(fetchScalar($conn, 'SELECT SUM(rank_missions) FROM user_ranks') ?? 0),
        'completedMissions' => (int)(fetchScalar($conn, "SELECT COUNT(*) FROM user_profile_meta WHERE meta_key LIKE 'quiz_score_%' AND meta_value IS NOT NULL AND meta_value <> ''") ?? 0),
    ];

    $rankDistribution = [];
    $rankResult = $conn->query("
        SELECT rank_title, COUNT(*) AS total, MIN(rank_badge) AS badge
        FROM user_ranks
        GROUP BY rank_title
        ORDER BY total DESC
    ");
    if ($rankResult) {
        while ($row = $rankResult->fetch_assoc()) {
            $rankDistribution[] = [
                'label' => $row['rank_title'] ?? 'Unknown',
                'count' => (int)$row['total'],
                'badge' => $row['badge'] ?? null,
            ];
        }
        $rankResult->close();
    }

    $topPilots = [];
    $topSql = "
        SELECT u.username, COALESCE(p.full_name, u.username) AS full_name, r.rank_title, r.rank_badge, r.rank_score, r.rank_missions
        FROM user_ranks r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN user_profiles p ON p.user_id = u.id
        ORDER BY r.rank_score DESC
        LIMIT 5
    ";
    $topResult = $conn->query($topSql);
    if ($topResult) {
        while ($row = $topResult->fetch_assoc()) {
            $topPilots[] = [
                'username' => $row['username'],
                'fullName' => $row['full_name'],
                'rank' => $row['rank_title'],
                'badge' => $row['rank_badge'],
                'score' => (int)$row['rank_score'],
                'lessons' => (int)$row['rank_missions'],
            ];
        }
        $topResult->close();
    }

    $recentUsers = [];
    $recentUserSql = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.level,
            u.created_at,
            m.meta_value AS avatar,
            p.full_name,
            p.age,
            p.gender
        FROM users u
        LEFT JOIN user_profile_meta m ON m.user_id = u.id AND m.meta_key = 'avatar_path'
        LEFT JOIN user_profiles p ON p.user_id = u.id
        ORDER BY u.created_at DESC
        LIMIT 8
    ";
    $recentUsersResult = $conn->query($recentUserSql);
    if ($recentUsersResult) {
        while ($row = $recentUsersResult->fetch_assoc()) {
            $recentUsers[] = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'level' => $row['level'],
                'joined' => $row['created_at'],
                'avatar' => $row['avatar'] ?: PROFILE_DEFAULT_AVATAR,
                'fullName' => $row['full_name'] ?? '',
                'age' => $row['age'] ?? null,
                'gender' => $row['gender'] ?? '',
            ];
        }
        $recentUsersResult->close();
    }

    $recentLogins = [];
    $recentLoginSql = "
        SELECT u.username, u.level, m.meta_value AS last_login
        FROM user_profile_meta m
        JOIN users u ON u.id = m.user_id
        WHERE m.meta_key = 'last_login_date'
        ORDER BY m.meta_value DESC
        LIMIT 10
    ";
    $recentLoginResult = $conn->query($recentLoginSql);
    if ($recentLoginResult) {
        while ($row = $recentLoginResult->fetch_assoc()) {
            $recentLogins[] = [
                'username' => $row['username'],
                'level' => $row['level'],
                'lastLogin' => $row['last_login'],
            ];
        }
        $recentLoginResult->close();
    }

    $moduleStats = [];
    $modResult = $conn->query("SELECT id, title, image_path FROM modules LIMIT 4");
    if ($modResult) {
        while ($row = $modResult->fetch_assoc()) {
            $modId = (int)$row['id'];
            // Count distinct users who have a quiz score entry for this module ID
            $completedCount = (int)(fetchScalar($conn, "SELECT COUNT(DISTINCT user_id) FROM user_profile_meta WHERE meta_key = ?", 's', ["quiz_score_" . $modId]) ?? 0);
            $moduleStats[] = [
                'title' => $row['title'],
                'completed' => $completedCount,
                'image' => $row['image_path'] ?: null,
            ];
        }
        $modResult->close();
    }

    $signupSeries = [];
    $signupSeriesSql = "
        SELECT DATE(created_at) AS signup_date, COUNT(*) AS total
        FROM users
        GROUP BY signup_date
        ORDER BY signup_date DESC
        LIMIT 30
    ";
    $signupSeriesResult = $conn->query($signupSeriesSql);
    if ($signupSeriesResult) {
        while ($row = $signupSeriesResult->fetch_assoc()) {
            $signupSeries[] = [
                'date' => $row['signup_date'],
                'count' => (int)($row['total'] ?? 0),
            ];
        }
        $signupSeriesResult->close();
        $signupSeries = array_reverse($signupSeries);
    }

    $currentMonthStart = new DateTimeImmutable('first day of this month');
    $nextMonthStart = $currentMonthStart->modify('first day of next month');
    $todayDt = new DateTimeImmutable($today);
    $prevMonthStart = $currentMonthStart->sub(new DateInterval('P1M'));

    $signupMonthCurrent = (int)(fetchScalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?",
        'ss',
        [$currentMonthStart->format('Y-m-d 00:00:00'), $nextMonthStart->format('Y-m-d 00:00:00')]
    ) ?? 0);

    $signupMonthPrevious = (int)(fetchScalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?",
        'ss',
        [$prevMonthStart->format('Y-m-d 00:00:00'), $currentMonthStart->format('Y-m-d 00:00:00')]
    ) ?? 0);

    $signupWeekStart = $todayDt->sub(new DateInterval('P6D'));
    $signupPrevWeekStart = $signupWeekStart->sub(new DateInterval('P7D'));

    $signupWeekCurrent = (int)(fetchScalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?",
        'ss',
        [$signupWeekStart->format('Y-m-d 00:00:00'), $todayDt->modify('+1 day')->format('Y-m-d 00:00:00')]
    ) ?? 0);

    $signupWeekPrevious = (int)(fetchScalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?",
        'ss',
        [$signupPrevWeekStart->format('Y-m-d 00:00:00'), $signupWeekStart->format('Y-m-d 00:00:00')]
    ) ?? 0);

    $activitySeries = [];
    $monthsBack = 10; // current month + previous 10 months
    for ($i = $monthsBack; $i >= 0; $i--) {
        $monthStartDt = $currentMonthStart->sub(new DateInterval("P{$i}M"));
        $nextMonthDt = $monthStartDt->modify('first day of next month');
        $monthEndDt = $nextMonthDt->sub(new DateInterval('P1D'));
        $dailyRef = $monthEndDt > $todayDt ? $todayDt : $monthEndDt;

        $weekStartDt = $monthEndDt->sub(new DateInterval('P6D'));
        if ($weekStartDt < $monthStartDt) {
            $weekStartDt = $monthStartDt;
        }

        $monthlyActive = countDistinctLoginsInRange($conn, $monthStartDt->format('Y-m-d'), $nextMonthDt->format('Y-m-d'));
        $weeklyActive = countDistinctLoginsInRange($conn, $weekStartDt->format('Y-m-d'), $nextMonthDt->format('Y-m-d'));
        $dailyActive = countDistinctLoginsOnDate($conn, $dailyRef->format('Y-m-d'));

        $activitySeries[] = [
            'label' => $monthStartDt->format('M'),
            'monthly' => $monthlyActive,
            'weekly' => $weeklyActive,
            'daily' => $dailyActive,
        ];
    }

    $monthlyCurrent = countDistinctLoginsInRange($conn, $currentMonthStart->format('Y-m-d'), $nextMonthStart->format('Y-m-d'));
    $monthlyPrevious = countDistinctLoginsInRange($conn, $prevMonthStart->format('Y-m-d'), $currentMonthStart->format('Y-m-d'));

    $yesterdayDt = $todayDt->sub(new DateInterval('P1D'));
    $dailyCurrent = countDistinctLoginsOnDate($conn, $todayDt->format('Y-m-d'));
    $dailyPrevious = countDistinctLoginsOnDate($conn, $yesterdayDt->format('Y-m-d'));

    $weeklyStart = $todayDt->sub(new DateInterval('P6D'));
    $weeklyPreviousStart = $weeklyStart->sub(new DateInterval('P7D'));
    $weeklyCurrent = countDistinctLoginsInRange($conn, $weeklyStart->format('Y-m-d'), $todayDt->modify('+1 day')->format('Y-m-d'));
    $weeklyPrevious = countDistinctLoginsInRange($conn, $weeklyPreviousStart->format('Y-m-d'), $weeklyStart->format('Y-m-d'));

    respondJson(200, [
        'status' => 'success',
        'data' => [
            'totals' => $totals,
            'rankDistribution' => $rankDistribution,
            'topPilots' => $topPilots,
            'recentUsers' => $recentUsers,
            'recentLogins' => $recentLogins,
            'moduleStats' => $moduleStats,
            'signupGrowth' => [
                'series' => $signupSeries,
                'summary' => [
                    'totalUsers' => $totals['users'],
                    'weekCurrent' => $signupWeekCurrent,
                    'weekPrevious' => $signupWeekPrevious,
                    'monthCurrent' => $signupMonthCurrent,
                    'monthPrevious' => $signupMonthPrevious,
                ],
            ],
            'activity' => [
                'series' => $activitySeries,
                'summary' => [
                    'monthly' => ['current' => $monthlyCurrent, 'previous' => $monthlyPrevious],
                    'weekly' => ['current' => $weeklyCurrent, 'previous' => $weeklyPrevious],
                    'daily' => ['current' => $dailyCurrent, 'previous' => $dailyPrevious],
                ],
            ],
        ],
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to load admin dashboard data.',
    ]);
} finally {
    $conn->close();
}
