<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

requireAdminJson($conn);

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_FILES['cover']) || !isset($_FILES['cover']['tmp_name'])) {
    respond(400, ['status' => 'error', 'message' => 'No cover file provided.']);
}

$file = $_FILES['cover'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(400, ['status' => 'error', 'message' => 'Upload failed. Please try again.']);
}

$maxBytes = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxBytes) {
    respond(413, ['status' => 'error', 'message' => 'File is too large (max 5 MB).']);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
if ($finfo) {
    finfo_close($finfo);
}

$allowedMimes = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/jpg' => 'jpg',
    'image/webp' => 'webp',
];

if (!$mime || !isset($allowedMimes[$mime])) {
    respond(415, ['status' => 'error', 'message' => 'Only PNG, JPG/JPEG, or WebP images are allowed.']);
}

$uploadDir = realpath(__DIR__ . '/../Assets');
if ($uploadDir === false) {
    respond(500, ['status' => 'error', 'message' => 'Upload path unavailable.']);
}
$uploadDir .= '/uploads/module-covers';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    respond(500, ['status' => 'error', 'message' => 'Unable to prepare upload directory.']);
}

$ext = $allowedMimes[$mime];
$filename = sprintf('cover-%s.%s', bin2hex(random_bytes(8)), $ext);
$targetPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    respond(500, ['status' => 'error', 'message' => 'Failed to store the uploaded file.']);
}

$publicPath = sprintf('../Assets/uploads/module-covers/%s', $filename);

respond(200, [
    'status' => 'success',
    'message' => 'Cover uploaded.',
    'data' => [
        'path' => $publicPath,
    ],
]);
