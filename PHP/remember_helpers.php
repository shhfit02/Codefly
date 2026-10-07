<?php
if (!defined('REMEMBER_COOKIE_NAME')) {
    define('REMEMBER_COOKIE_NAME', 'cf_remember');
}

if (!defined('REMEMBER_COOKIE_LIFETIME')) {
    define('REMEMBER_COOKIE_LIFETIME', 60 * 60 * 24 * 30); // 30 days
}

function rememberUser(mysqli $conn, int $userId): void
{
    forgetRememberedUser($conn, null, $userId);

    $selector = bin2hex(random_bytes(6)); // 12 chars
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTime('+30 days'))->format('Y-m-d H:i:s');

    $insert = $conn->prepare('INSERT INTO user_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    $insert->bind_param('isss', $userId, $selector, $tokenHash, $expiresAt);
    $insert->execute();
    $insert->close();

    $cookieValue = $selector . ':' . $token;
    setcookie(REMEMBER_COOKIE_NAME, $cookieValue, [
        'expires' => time() + REMEMBER_COOKIE_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}

function forgetRememberedUser(mysqli $conn, ?string $selector = null, ?int $userId = null): void
{
    if ($selector !== null) {
        $stmt = $conn->prepare('DELETE FROM user_tokens WHERE selector = ?');
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $stmt->close();
    } elseif ($userId !== null) {
        $stmt = $conn->prepare('DELETE FROM user_tokens WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    clearRememberCookie();
}

function clearRememberCookie(): void
{
    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}

function getRememberCookieParts(): ?array
{
    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return null;
    }

    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]);
    if (count($parts) !== 2) {
        return null;
    }

    return $parts;
}

function attemptRememberedLogin(mysqli $conn): ?array
{
    if (isset($_SESSION['user_id'])) {
        return null;
    }

    $cookieParts = getRememberCookieParts();
    if (!$cookieParts) {
        return null;
    }

    [$selector, $token] = $cookieParts;

    $stmt = $conn->prepare('SELECT user_id, token_hash, expires_at FROM user_tokens WHERE selector = ? LIMIT 1');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        forgetRememberedUser($conn, $selector, null);
        return null;
    }

    if (strtotime($row['expires_at']) < time()) {
        forgetRememberedUser($conn, $selector, null);
        return null;
    }

    $tokenHash = hash('sha256', $token);
    if (!hash_equals($row['token_hash'], $tokenHash)) {
        forgetRememberedUser($conn, $selector, null);
        return null;
    }

    $userStmt = $conn->prepare('SELECT id, username, email, level FROM users WHERE id = ? LIMIT 1');
    $userStmt->bind_param('i', $row['user_id']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();

    if (!$user) {
        forgetRememberedUser($conn, $selector, null);
        return null;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['level'] = $user['level'] ?? 'user';

    rememberUser($conn, (int)$user['id']); // rotate token

    return $user;
}
