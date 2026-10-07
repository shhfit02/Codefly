<?php
session_start();

require_once 'db.php';
require_once 'profile_helpers.php';

header('Content-Type: text/html; charset=utf-8');

// Auth + admin check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

$requestingId = (int)$_SESSION['user_id'];
$levelStmt = $conn->prepare('SELECT level FROM users WHERE id = ? LIMIT 1');
$levelStmt->bind_param('i', $requestingId);
$levelStmt->execute();
$levelStmt->bind_result($requestingLevel);
$levelStmt->fetch();
$levelStmt->close();

if (strtolower($requestingLevel ?? 'user') !== 'admin') {
    echo "Admin privileges required.";
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/addUser.html');
    exit;
}

// Collect inputs
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$level      = trim($_POST['level'] ?? 'user');
$fullName   = trim($_POST['full_name'] ?? '');
$age        = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
$gender     = trim($_POST['gender'] ?? '');

$errors = [];

if ($username === '') {
    $errors[] = 'Username is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

$level = strtolower($level) === 'admin' ? 'admin' : 'user';

if (!empty($gender)) {
    $gender = strtolower($gender);
}

// Uniqueness checks
if (empty($errors)) {
    $checkUser = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $checkUser->bind_param('s', $username);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows > 0) {
        $errors[] = 'Username already exists.';
    }
    $checkUser->close();
}

if (empty($errors)) {
    $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $errors[] = 'Email already exists.';
    }
    $checkEmail->close();
}

if (!empty($errors)) {
    echo implode('<br>', $errors);
    $conn->close();
    exit;
}

try {
    $conn->begin_transaction();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $insertUser = $conn->prepare('INSERT INTO users (username, email, password, level) VALUES (?, ?, ?, ?)');
    $insertUser->bind_param('ssss', $username, $email, $hashed, $level);
    if (!$insertUser->execute()) {
        throw new RuntimeException('Failed to create user.');
    }
    $userId = $conn->insert_id;
    $insertUser->close();

    // Insert profile
    $profileStmt = $conn->prepare('INSERT INTO user_profiles (user_id, full_name, age, gender) VALUES (?, ?, ?, ?)');
    $profileStmt->bind_param('isis', $userId, $fullName, $age, $gender);
    if (!$profileStmt->execute()) {
        throw new RuntimeException('Failed to save profile.');
    }
    $profileStmt->close();

    // Ensure rank row exists
    ensureRankRecord($conn, $userId);

    $conn->commit();
    $conn->close();

    header('Location: ../HTML/manageUser.html?added=1');
    exit;
} catch (Throwable $e) {
    if (isset($insertUser) && $insertUser instanceof mysqli_stmt) {
        $insertUser->close();
    }
    if (isset($profileStmt) && $profileStmt instanceof mysqli_stmt) {
        $profileStmt->close();
    }
    $conn->rollback();
    $conn->close();
    echo 'Unable to add user right now.';
    exit;
}
