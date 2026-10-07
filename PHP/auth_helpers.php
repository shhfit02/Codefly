<?php

/**
 * True when the current session belongs to an admin (level read from the database).
 */
function isAdminSession(mysqli $conn): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $userId = (int)$_SESSION['user_id'];
    $level = null;

    $stmt = $conn->prepare('SELECT level FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($level);
        $stmt->fetch();
        $stmt->close();
    }

    return strtolower($level ?? 'user') === 'admin';
}

/**
 * Stops the request with a JSON error unless the session belongs to an admin.
 * The level is read from the database so demoted admins lose access immediately.
 */
function requireAdminJson(mysqli $conn): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
        exit;
    }

    if (!isAdminSession($conn)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Admin privileges required.']);
        exit;
    }

    return (int)$_SESSION['user_id'];
}
