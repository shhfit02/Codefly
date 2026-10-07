<?php
session_start();

require_once 'db.php';
require_once 'profile_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
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
    echo json_encode(['status' => 'error', 'message' => 'Admin privileges required.']);
    $conn->close();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user id.']);
        $conn->close();
        exit;
    }
    $stmt = $conn->prepare('
        SELECT u.id, u.username, u.email, u.level, p.full_name, p.age, p.gender
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        $conn->close();
        exit;
    }
    echo json_encode(['status' => 'success', 'data' => $row]);
    $conn->close();
    exit;
}

if ($method !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported method.']);
    $conn->close();
    exit;
}

$id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$level     = trim($_POST['level'] ?? 'user');
$fullName  = trim($_POST['full_name'] ?? '');
$age       = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
$gender    = trim($_POST['gender'] ?? '');

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user id.']);
    $conn->close();
    exit;
}

$errors = [];

if ($username === '') {
    $errors[] = 'Username is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}

$level = strtolower($level) === 'admin' ? 'admin' : 'user';

// Uniqueness checks excluding current user
if (empty($errors)) {
    $checkUser = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $checkUser->bind_param('si', $username, $id);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows > 0) {
        $errors[] = 'Username already exists.';
    }
    $checkUser->close();
}

if (empty($errors)) {
    $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $checkEmail->bind_param('si', $email, $id);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $errors[] = 'Email already exists.';
    }
    $checkEmail->close();
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    $conn->close();
    exit;
}

try {
    $conn->begin_transaction();

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updateUser = $conn->prepare('UPDATE users SET username = ?, email = ?, level = ?, password = ? WHERE id = ? LIMIT 1');
        $updateUser->bind_param('ssssi', $username, $email, $level, $hashed, $id);
    } else {
        $updateUser = $conn->prepare('UPDATE users SET username = ?, email = ?, level = ? WHERE id = ? LIMIT 1');
        $updateUser->bind_param('sssi', $username, $email, $level, $id);
    }

    if (!$updateUser->execute()) {
        throw new RuntimeException('Failed to update user.');
    }
    $updateUser->close();

    // Remove any existing profile rows for this user to avoid duplicates, then insert a fresh one.
    $deleteProfile = $conn->prepare('DELETE FROM user_profiles WHERE user_id = ?');
    $deleteProfile->bind_param('i', $id);
    if (!$deleteProfile->execute()) {
        throw new RuntimeException('Failed to clean existing profile rows.');
    }
    $deleteProfile->close();

    $genderValue = $gender === '' ? null : $gender;
    $profileStmt = $conn->prepare('INSERT INTO user_profiles (user_id, full_name, age, gender) VALUES (?, ?, ?, ?)');
    $profileStmt->bind_param('isis', $id, $fullName, $age, $genderValue);
    if (!$profileStmt->execute()) {
        throw new RuntimeException('Failed to update profile.');
    }
    $profileStmt->close();

    $conn->commit();
    $conn->close();

    echo json_encode(['status' => 'success', 'message' => 'User updated.']);
    exit;
} catch (Throwable $e) {
    if (isset($updateUser) && $updateUser instanceof mysqli_stmt) {
        $updateUser->close();
    }
    if (isset($deleteProfile) && $deleteProfile instanceof mysqli_stmt) {
        $deleteProfile->close();
    }
    if (isset($profileStmt) && $profileStmt instanceof mysqli_stmt) {
        $profileStmt->close();
    }
    $conn->rollback();
    $conn->close();
    echo json_encode(['status' => 'error', 'message' => 'Unable to update user right now.']);
    exit;
}
