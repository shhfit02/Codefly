<?php
session_start();
require_once 'db.php';
require_once 'notification_helpers.php';
require_once 'profile_helpers.php';
require_once 'remember_helpers.php';
require_once 'toast_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html');
    exit;
}

$identifier = trim($_POST['username'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    queueToast('warning', 'Please fill in both fields.');
    header('Location: ../index.html');
    exit;
}

$sql = 'SELECT id, username, email, password, level FROM users WHERE email = ? OR username = ? LIMIT 1';
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // New session id on login so a pre-planted session cookie cannot be reused (session fixation).
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['level'] = $user['level'] ?? 'user';

        try {
            createNotification($conn, (int)$user['id'], 'login', 'Welcome back to Codefly', [
                'loginAt' => date(DATE_ATOM),
            ]);
        } catch (Throwable $e) {
            // Keep login flow resilient even if notification persistence fails.
        }

        try {
            ensureProfileMetaTable($conn);
            $meta = fetchProfileMeta($conn, (int)$user['id']);

            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
            $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
            $lastLogin = $meta['last_login_date'] ?? null;
            $currentStreak = isset($meta['login_streak']) ? (int)$meta['login_streak'] : 0;

            if ($lastLogin === $today) {
                $newStreak = $currentStreak;
            } elseif ($lastLogin === $yesterday) {
                $newStreak = max(1, $currentStreak + 1);
            } else {
                $newStreak = 1;
            }

            saveProfileMeta($conn, (int)$user['id'], [
                'last_login_date' => $today,
                'login_streak' => $newStreak,
            ]);
        } catch (Throwable $e) {
            // Ignore streak updates if meta storage fails.
        }

        if (!empty($_POST['remember'])) {
            rememberUser($conn, (int)$user['id']);
        } else {
            forgetRememberedUser($conn, null, (int)$user['id']);
        }

        $level = strtolower($user['level'] ?? 'user');
        $redirect = $level === 'admin' ? '../HTML/adminDashboard.php' : '../HTML/home.html';

        header("Location: $redirect");
        exit;
    }

    queueToast('error', 'Invalid password. Please try again.');
} else {
    queueToast('warning', 'No account found with that username or email.');
}

$stmt->close();
$conn->close();

header('Location: ../index.html');
exit;
