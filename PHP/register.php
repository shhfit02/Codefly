<?php
session_start();
require_once 'toast_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/register.html');
    exit;
}

$profileKey = 'registration_profile';
$errorsKey = 'register_errors';
$valuesKey = 'register_values';
$agreeKey = 'register_agree';

$profile = $_SESSION[$profileKey] ?? null;
if ($profile === null) {
    header('Location: ../HTML/register-profile.html');
    exit;
}

require_once 'db.php';
require_once 'profile_helpers.php';

$values = [
    'username' => trim($_POST['username'] ?? ''),
    'email' => trim($_POST['email'] ?? '')
];
$password = $_POST['password'] ?? '';
$agreeChecked = isset($_POST['agree']);

$errors = [];

if ($values['username'] === '') {
    $errors[] = 'Username is required.';
}

if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if (!$agreeChecked) {
    $errors[] = 'You must agree to the terms & conditions.';
}

if (empty($errors)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $values['username']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Username is already taken.';
    }
    $stmt->close();
}

if (empty($errors)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $values['email']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email is already registered.';
    }
    $stmt->close();
}

if (!empty($errors)) {
    $_SESSION[$errorsKey] = $errors;
    $_SESSION[$valuesKey] = $values;
    $_SESSION[$agreeKey] = $agreeChecked;
    $conn->close();
    queueToast('warning', 'Please resolve the highlighted issues before continuing.');
    header('Location: ../HTML/register.html');
    exit;
}

try {
    $conn->begin_transaction();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $level = 'user';
    $stmt = $conn->prepare('INSERT INTO users (username, email, password, level) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $values['username'], $values['email'], $hashedPassword, $level);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create user.');
    }

    $userId = $conn->insert_id;
    $stmt->close();

    $fullName = $profile['fullname'] ?? '';
    $age = isset($profile['age']) ? (int)$profile['age'] : null;
    $gender = $profile['gender'] ?? 'prefer-not';

    $profileStmt = $conn->prepare('INSERT INTO user_profiles (user_id, full_name, age, gender) VALUES (?, ?, ?, ?)');
    $profileStmt->bind_param('isis', $userId, $fullName, $age, $gender);

    if (!$profileStmt->execute()) {
        throw new Exception('Failed to save profile details.');
    }

    $profileStmt->close();

    ensureRankRecord($conn, $userId);
    $conn->commit();

    notifySafely(fn () => notifyRole($conn, 'admin', 'user', "New student registered: {$values['username']}", [
        'url' => '../HTML/adminViewProfile.php?user=' . rawurlencode($values['username']),
    ]));
    $conn->close();

    unset($_SESSION[$profileKey], $_SESSION[$valuesKey], $_SESSION[$errorsKey], $_SESSION[$agreeKey]);
    queueToast('success', 'Registration successful! Please log in.');
    header('Location: ../index.html');
    exit;
} catch (Exception $e) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($profileStmt) && $profileStmt instanceof mysqli_stmt) {
        $profileStmt->close();
    }
    $conn->rollback();
    $conn->close();

    $_SESSION[$errorsKey] = ['Registration failed. Please try again.'];
    queueToast('error', 'Registration failed. Please try again.');
}

$_SESSION[$valuesKey] = $values;
$_SESSION[$agreeKey] = $agreeChecked;

header('Location: ../HTML/register.html');
exit;
