<?php
// Server-side gate for PHP pages. Call one of these at the very top of a page,
// before any HTML is sent, so the redirect header can still be set.
// The JSON endpoints keep their own checks (requireAdminJson); this stops the
// page itself from being served to visitors who are not signed in.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/toast_helpers.php';

function requireLoginPage(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        queueToast('warning', 'Please log in to continue.');
        header('Location: ../index.html');
        exit;
    }
}

/**
 * Name and photo of the signed-in user for the sidebar's "signed in as" row.
 * Falls back to the session username and the default avatar if the lookup fails.
 */
function currentUserCard(): array
{
    global $conn;
    $card = [
        'username' => $_SESSION['username'] ?? 'user',
        'name' => $_SESSION['username'] ?? 'User',
        'avatar' => '../Assets/StudentProfile.png',
    ];
    if (!isset($_SESSION['user_id'])) {
        return $card;
    }
    try {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("
            SELECT u.username, p.full_name,
                   (SELECT meta_value FROM user_profile_meta m
                    WHERE m.user_id = u.id AND m.meta_key = 'avatar_path' LIMIT 1) AS avatar
            FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $card['username'] = $row['username'];
            $card['name'] = trim((string)$row['full_name']) !== '' ? $row['full_name'] : $row['username'];
            if (!empty($row['avatar'])) {
                $card['avatar'] = $row['avatar'];
            }
        }
    } catch (Throwable $e) {
        error_log('Sidebar user card failed: ' . $e->getMessage());
    }
    return $card;
}

function requireAdminPage(): void
{
    global $conn;
    requireLoginPage();
    // The level is read from the database, so a demoted admin loses access immediately.
    if (!isAdminSession($conn)) {
        queueToast('error', 'That page is for admins only.');
        header('Location: ../HTML/home.php');
        exit;
    }
}
