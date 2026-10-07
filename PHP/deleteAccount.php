<?php
session_start();

require_once 'db.php';
require_once 'profile_helpers.php';
require_once 'remember_helpers.php';

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Authentication required.',
    ]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported request method.',
    ]);
    exit;
}

$input = readRequestBody();
$confirmation = strtoupper(trim($input['confirm'] ?? ''));

if ($confirmation !== 'DELETE') {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Please type DELETE to confirm.',
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $conn->begin_transaction();

    $tables = [
        'user_tokens',
        'user_ranks',
        'user_profile_meta',
        'user_settings',
        'user_profiles',
    ];

    foreach ($tables as $table) {
        if ($conn->query("SHOW TABLES LIKE '{$table}'")->num_rows === 0) {
            continue;
        }
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    if ($conn->query("SHOW TABLES LIKE 'user_notifications'")->num_rows > 0) {
        $stmt = $conn->prepare('DELETE FROM user_notifications WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException('Unable to delete user record.');
    }
    $stmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    respondJson(500, [
        'status' => 'error',
        'message' => 'Failed to delete account. Please try again.',
    ]);
    exit;
}

forgetRememberedUser($conn, null, $userId);
session_unset();
session_destroy();

respondJson(200, [
    'status' => 'success',
    'message' => 'Account deleted successfully.',
]);
