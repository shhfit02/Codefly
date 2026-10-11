<?php
// Returns every user for the admin "Manage Users" directory, newest first.
// Unlike the dashboard's recentUsers (capped at 8), this lists all accounts so
// admins can search, filter and manage the whole user base.
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/profile_helpers.php';

function respondJson(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Admins only; the guard reads the level from the database and exits with JSON otherwise.
requireAdminJson($conn);

$sql = "
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
";

$result = $conn->query($sql);
if (!$result) {
    respondJson(500, ['status' => 'error', 'message' => 'Unable to load users.']);
}

$users = [];
$adminCount = 0;
while ($row = $result->fetch_assoc()) {
    if (strtolower($row['level'] ?? 'user') === 'admin') {
        $adminCount++;
    }
    $users[] = [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'level' => $row['level'] ?? 'user',
        'joined' => $row['created_at'],
        'avatar' => $row['avatar'] ?: PROFILE_DEFAULT_AVATAR,
        'fullName' => $row['full_name'] ?? '',
        'age' => $row['age'] ?? null,
        'gender' => $row['gender'] ?? '',
    ];
}
$result->close();

respondJson(200, [
    'status' => 'success',
    'data' => [
        'totals' => [
            'users' => count($users),
            'admins' => $adminCount,
        ],
        'users' => $users,
    ],
]);
