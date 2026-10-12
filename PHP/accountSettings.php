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

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $profileBundle = fetchUserProfile($conn, $userId);
        $settings = fetchUserSettings($conn, $userId);
        $rank = $profileBundle['rank'] ?? PROFILE_DEFAULT_RANK;
        $meta = $profileBundle['meta'] ?? [];
        $lessons = updateRankMissions($conn, $userId, $meta);

        respondJson(200, [
            'status' => 'success',
            'data' => [
                'username' => $profileBundle['account']['username'],
                'email' => $profileBundle['account']['email'],
                'fullName' => $profileBundle['profile']['fullName'] ?? $profileBundle['account']['username'],
                'avatar' => $profileBundle['profile']['avatar'] ?? PROFILE_DEFAULT_AVATAR,
                'inAppNotifications' => $settings['inAppNotifications'],
                'rank' => $rank,
                'lessons' => $lessons,
                'xp' => $rank['score'] ?? 0,
            ],
        ]);
    } catch (Throwable $e) {
        respondJson(500, [
            'status' => 'error',
            'message' => 'Failed to load account settings.',
        ]);
    } finally {
        $conn->close();
    }
    exit;
}

if ($method !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported request method.',
    ]);
    exit;
}

$input = readRequestBody();
$values = [
    'username' => trim($input['username'] ?? ''),
    'email' => trim($input['email'] ?? ''),
    'password' => $input['password'] ?? '',
    'confirmPassword' => $input['confirmPassword'] ?? '',
    'currentPassword' => $input['currentPassword'] ?? '',
    'inAppNotifications' => !empty($input['inAppNotifications']),
];

$errors = [];

if ($values['username'] === '') {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $values['username'])) {
    $errors[] = 'Username must be 3-32 characters and only contain letters, numbers, dots, dashes or underscores.';
}

if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}

if ($values['password'] !== '' && strlen($values['password']) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($values['password'] !== $values['confirmPassword']) {
    $errors[] = 'Passwords do not match.';
}

if (empty($errors)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $values['username'], $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Username is already taken.';
    }
    $stmt->close();
}

if (empty($errors)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $values['email'], $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email is already in use.';
    }
    $stmt->close();
}

// A new password or email needs the current password, as in changePassword.php: otherwise
// anyone at an unlocked, signed-in computer could take the account over (a new email
// also lets them reset the password with a TAC).
$account = null;
if (empty($errors)) {
    $stmt = $conn->prepare('SELECT email, password FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $emailChanged = $account && strcasecmp($account['email'], $values['email']) !== 0;
    if ($account && ($values['password'] !== '' || $emailChanged)) {
        if ($values['currentPassword'] === '') {
            $errors[] = 'Enter your current password to change your email or password.';
        } elseif (!password_verify($values['currentPassword'], $account['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }
}

if (!empty($errors)) {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Update failed. See errors for details.',
        'errors' => $errors,
    ]);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE id = ? LIMIT 1');
    $stmt->bind_param('ssi', $values['username'], $values['email'], $userId);
    $stmt->execute();
    $stmt->close();

    if ($values['password'] !== '') {
        $hashed = password_hash($values['password'], PASSWORD_DEFAULT);
        $pwdStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1');
        $pwdStmt->bind_param('si', $hashed, $userId);
        $pwdStmt->execute();
        $pwdStmt->close();
    }

    // Email, SMS and 2FA were never implemented, so only the in-app switch is offered.
    saveUserSettings($conn, $userId, [
        'inAppNotifications' => $values['inAppNotifications'],
    ]);

    $conn->commit();

    // Same as changePassword.php: sign out "remember me" on other devices.
    if ($values['password'] !== '') {
        if (getRememberCookieParts()) {
            rememberUser($conn, $userId);
        } else {
            forgetRememberedUser($conn, null, $userId);
        }
    }

    $_SESSION['username'] = $values['username'];

    respondJson(200, [
        'status' => 'success',
        'message' => 'Account settings updated.',
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to update account settings right now.',
    ]);
} finally {
    $conn->close();
}
