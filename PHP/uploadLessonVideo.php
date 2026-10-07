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

if (empty($_FILES['video']) || !isset($_FILES['video']['tmp_name'])) {
    respond(400, ['status' => 'error', 'message' => 'No video file provided.']);
}

$file = $_FILES['video'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(400, ['status' => 'error', 'message' => 'Upload failed. Please try again.']);
}

$maxBytes = 50 * 1024 * 1024; // 50 MB
if ($file['size'] > $maxBytes) {
    respond(413, ['status' => 'error', 'message' => 'File is too large (max 50 MB).']);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
if ($finfo) {
    finfo_close($finfo);
}

$allowedMimes = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv',
];

if (!$mime || !isset($allowedMimes[$mime])) {
    respond(415, ['status' => 'error', 'message' => 'Only MP4, WebM, or Ogg video is allowed.']);
}

$uploadDir = realpath(__DIR__ . '/../Assets');
if ($uploadDir === false) {
    respond(500, ['status' => 'error', 'message' => 'Upload path unavailable.']);
}
$uploadDir .= '/uploads/lesson-videos';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    respond(500, ['status' => 'error', 'message' => 'Unable to prepare upload directory.']);
}

$ext = $allowedMimes[$mime];
$filename = sprintf('lesson-%s.%s', bin2hex(random_bytes(8)), $ext);
$targetPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    respond(500, ['status' => 'error', 'message' => 'Failed to store the uploaded file.']);
}

$publicPath = sprintf('../Assets/uploads/lesson-videos/%s', $filename);

respond(200, [
    'status' => 'success',
    'message' => 'Video uploaded.',
    'data' => [
        'path' => $publicPath,
    ],
]);
