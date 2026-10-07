<?php

/**
 * Makes sure password_resets has the used_at column that the TAC flow relies on.
 * Older databases created from codefly_database.sql were missing it.
 */
function ensurePasswordResetsTable(mysqli $conn): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $hasUsedAt = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'used_at'");
    if ($hasUsedAt && $hasUsedAt->num_rows === 0) {
        if (!$conn->query('ALTER TABLE password_resets ADD COLUMN used_at DATETIME NULL DEFAULT NULL')) {
            throw new RuntimeException('Failed to prepare password reset storage.');
        }
    }

    $ready = true;
}
