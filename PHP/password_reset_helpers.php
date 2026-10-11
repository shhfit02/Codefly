<?php

// A 6-digit TAC has only a million values, so each code allows a few guesses only.
const TAC_MAX_ATTEMPTS = 5;

/**
 * Makes sure password_resets has the used_at and attempts columns that the TAC flow relies on.
 * Older databases created from codefly_database.sql were missing them.
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
            attempts INT NOT NULL DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $hasUsedAt = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'used_at'");
    if ($hasUsedAt && $hasUsedAt->num_rows === 0) {
        if (!$conn->query('ALTER TABLE password_resets ADD COLUMN used_at DATETIME NULL DEFAULT NULL')) {
            throw new RuntimeException('Failed to prepare password reset storage.');
        }
    }

    // Wrong TAC guesses per code; verifyTac.php invalidates the code at TAC_MAX_ATTEMPTS.
    $hasAttempts = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'attempts'");
    if ($hasAttempts && $hasAttempts->num_rows === 0) {
        if (!$conn->query('ALTER TABLE password_resets ADD COLUMN attempts INT NOT NULL DEFAULT 0')) {
            throw new RuntimeException('Failed to prepare password reset storage.');
        }
    }

    $ready = true;
}
