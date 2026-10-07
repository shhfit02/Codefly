<?php
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';

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
        $profile = $profileBundle['profile'] ?? [];
        $account = $profileBundle['account'] ?? [];

        respondJson(200, [
            'status' => 'success',
            'data' => array_merge($profile, [
                'username' => $account['username'] ?? '',
            ]),
        ]);
    } catch (Throwable $e) {
        respondJson(500, [
            'status' => 'error',
            'message' => 'Failed to load profile details.',
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
    'full_name' => trim($input['fullName'] ?? $input['full_name'] ?? ''),
    'age' => trim((string)($input['age'] ?? '')),
    'gender' => strtolower(trim($input['gender'] ?? '')),
    'school' => trim($input['school'] ?? ''),
    'phone' => trim($input['phone'] ?? ''),
    'location' => trim($input['location'] ?? ''),
    'bio' => trim($input['bio'] ?? ''),
];

$errors = [];

if ($values['full_name'] === '') {
    $errors[] = 'Full name is required.';
}

if ($values['age'] !== '') {
    $age = filter_var($values['age'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 13, 'max_range' => 100],
    ]);
    if ($age === false) {
        $errors[] = 'Age must be between 13 and 100.';
    } else {
        $values['age'] = (string)$age;
    }
} else {
    $values['age'] = null;
}

$allowedGender = ['male', 'female', 'prefer-not', 'other'];
if ($values['gender'] !== '' && !in_array($values['gender'], $allowedGender, true)) {
    $errors[] = 'Please select a valid gender option.';
}

if ($values['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $values['phone'])) {
    $errors[] = 'Please enter a valid phone number.';
}

if (strlen($values['school']) > 120) {
    $errors[] = 'School name is too long.';
}

if (strlen($values['location']) > 120) {
    $errors[] = 'Location is too long.';
}

if (strlen($values['bio']) > 600) {
    $errors[] = 'Bio must be 600 characters or fewer.';
}

if (!empty($errors)) {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Please fix the highlighted fields.',
        'errors' => $errors,
    ]);
    exit;
}

try {
    ensureProfileRecord($conn, $userId);

    $gender = $values['gender'] !== '' ? $values['gender'] : 'prefer-not';
    $age = $values['age'] !== null ? (int)$values['age'] : null;

    $stmt = $conn->prepare('UPDATE user_profiles SET full_name = ?, age = ?, gender = ? WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('sisi', $values['full_name'], $age, $gender, $userId);
    $stmt->execute();
    $stmt->close();

    $meta = [
        'school' => $values['school'],
        'phone' => $values['phone'],
        'location' => $values['location'],
        'bio' => $values['bio'],
    ];

    saveProfileMeta($conn, $userId, $meta);

    respondJson(200, [
        'status' => 'success',
        'message' => 'Profile updated successfully.',
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to update your profile. Please try again.',
    ]);
} finally {
    $conn->close();
}
