<?php

function ensureNotificationsTable(mysqli $conn): void
{
    static $notificationsReady = false;

    if ($notificationsReady) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(64) NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    meta TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to ensure notifications table.');
    }

    $notificationsReady = true;
}

function createNotification(mysqli $conn, int $userId, string $type, string $message, array $meta = []): void
{
    ensureNotificationsTable($conn);

    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare('INSERT INTO user_notifications (user_id, type, message, meta) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $type, $message, $metaJson);
    $stmt->execute();
    $stmt->close();
}

function fetchNotifications(mysqli $conn, int $userId, int $limit = 20): array
{
    ensureNotificationsTable($conn);

    $stmt = $conn->prepare('
        SELECT id, type, message, meta, is_read, created_at
        FROM user_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => (int)$row['id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'meta' => $row['meta'] ? json_decode($row['meta'], true) : null,
            'isRead' => (bool)$row['is_read'],
            'timestamp' => $row['created_at'],
        ];
    }

    $stmt->close();

    return $notifications;
}

function markNotificationsRead(mysqli $conn, int $userId): void
{
    ensureNotificationsTable($conn);

    $stmt = $conn->prepare('UPDATE user_notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}
