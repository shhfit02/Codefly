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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respondJson(405, [
        'status' => 'error',
        'message' => 'Unsupported request method.',
    ]);
    exit;
}

$type = $_POST['type'] ?? '';
$allowedTypes = ['avatar', 'cover'];
if (!in_array($type, $allowedTypes, true)) {
    respondJson(422, [
        'status' => 'error',
        'message' => 'Unknown upload target.',
    ]);
    exit;
}

if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'No file provided.',
    ]);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'Upload failed. Please try again.',
    ]);
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    respondJson(413, [
        'status' => 'error',
        'message' => 'File is too large (max 5 MB).',
    ]);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
if ($finfo) {
    finfo_close($finfo);
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!$mime || !isset($allowedMimes[$mime])) {
    respondJson(415, [
        'status' => 'error',
        'message' => 'Only JPG, PNG, or WEBP images are allowed.',
    ]);
    exit;
}

$subDir = $type === 'avatar' ? 'avatars' : 'covers';
$uploadDir = realpath(__DIR__ . '/../Assets');
if ($uploadDir === false) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Upload path unavailable.',
    ]);
    exit;
}
$uploadDir .= '/uploads/' . $subDir;

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to prepare upload directory.',
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$ext = $allowedMimes[$mime];
$filename = sprintf('user-%d-%s.%s', $userId, bin2hex(random_bytes(8)), $ext);
$targetPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Failed to store the uploaded file.',
    ]);
    exit;
}

$publicPath = sprintf('../Assets/uploads/%s/%s', $subDir, $filename);
$metaKey = $type === 'avatar' ? 'avatar_path' : 'cover_path';

try {
    saveProfileMeta($conn, $userId, [$metaKey => $publicPath]);
    respondJson(200, [
        'status' => 'success',
        'message' => ucfirst($type) . ' updated.',
        'data' => [
            'path' => $publicPath,
        ],
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Unable to save the new image.',
    ]);
} finally {
    $conn->close();
}
