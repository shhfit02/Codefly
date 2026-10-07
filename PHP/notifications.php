<?php
session_start();
require_once 'db.php';
require_once 'notification_helpers.php';

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
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    try {
        markNotificationsRead($conn, $userId);
        echo json_encode([
            'status' => 'success',
            'message' => 'Notifications marked as read.',
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to mark notifications as read.',
        ]);
    } finally {
        $conn->close();
    }
    exit;
}

try {
    $notifications = fetchNotifications($conn, $userId, 20);
    echo json_encode([
        'status' => 'success',
        'data' => $notifications,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to load notifications.',
    ]);
} finally {
    $conn->close();
}
