<?php

function ensureNotificationsTable(mysqli $conn): void
{
    static $notificationsReady = false;

    if ($notificationsReady) {
        return;
    }

    // CREATE TABLE commits any open transaction (even with IF NOT EXISTS), so only run it
    // when the table is really missing; SHOW TABLES is safe inside a transaction.
    $existing = $conn->query("SHOW TABLES LIKE 'user_notifications'");
    if ($existing && $existing->num_rows > 0) {
        $notificationsReady = true;
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

/** user_settings only exists once someone has saved settings on an older database. */
function hasSettingsTable(mysqli $conn): bool
{
    static $exists = null;
    if ($exists === null) {
        $result = $conn->query("SHOW TABLES LIKE 'user_settings'");
        $exists = $result && $result->num_rows > 0;
    }
    return $exists;
}

/** Users who switched off "In-app notifications" in Settings get nothing. Default is on. */
function wantsInAppNotifications(mysqli $conn, int $userId): bool
{
    if (!hasSettingsTable($conn)) {
        return true;
    }
    $stmt = $conn->prepare('SELECT in_app_notifications FROM user_settings WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($enabled);
    $found = $stmt->fetch();
    $stmt->close();
    return !$found || (bool)$enabled;
}

/**
 * $meta['url'] (a page under ../HTML/) makes the notification clickable.
 */
function createNotification(mysqli $conn, int $userId, string $type, string $message, array $meta = []): void
{
    ensureNotificationsTable($conn);

    if (!wantsInAppNotifications($conn, $userId)) {
        return;
    }

    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare('INSERT INTO user_notifications (user_id, type, message, meta) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $type, $message, $metaJson);
    $stmt->execute();
    $stmt->close();
}

/**
 * One notification for every student ('user' level) or every admin, in a single query.
 */
function notifyRole(mysqli $conn, string $role, string $type, string $message, array $meta = []): void
{
    ensureNotificationsTable($conn);

    $levelCondition = $role === 'admin' ? "u.level = 'admin'" : "(u.level IS NULL OR u.level <> 'admin')";
    $settingsJoin = hasSettingsTable($conn) ? 'LEFT JOIN user_settings s ON s.user_id = u.id' : '';
    $settingsCondition = hasSettingsTable($conn) ? 'AND COALESCE(s.in_app_notifications, 1) = 1' : '';

    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare("
        INSERT INTO user_notifications (user_id, type, message, meta)
        SELECT u.id, ?, ?, ? FROM users u {$settingsJoin}
        WHERE {$levelCondition} {$settingsCondition}
    ");
    $stmt->bind_param('sss', $type, $message, $metaJson);
    $stmt->execute();
    $stmt->close();
}

/** Notifications must never break the action that triggered them. */
function notifySafely(callable $send): void
{
    try {
        $send();
    } catch (Throwable $e) {
        error_log('Notification failed: ' . $e->getMessage());
    }
}

function fetchNotifications(mysqli $conn, int $userId, int $limit = 20): array
{
    ensureNotificationsTable($conn);

    $stmt = $conn->prepare('
        SELECT id, type, message, meta, is_read, created_at
        FROM user_notifications
        WHERE user_id = ?
        ORDER BY id DESC
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

    $stmt = $conn->prepare('UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}
