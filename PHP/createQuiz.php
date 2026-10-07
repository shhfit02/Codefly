<?php
// Endpoint to create a quiz question tied to a lesson.
// Accepts JSON or form-data:
//  - lesson_id (required, references lessons.id)
//  - question (required)
//  - options (array of answer choices; optional but recommended)
//  - correct_option (string; optional, should match one of the options)
//  - xp (integer; optional)

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

requireAdminJson($conn);

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function readPayload(): array {
    $raw = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

function ensureMissionsTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(64) NULL UNIQUE,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->query($sql);
}

function ensureQuizzesTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS quizzes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL,
            question TEXT NOT NULL,
            options_json TEXT NULL,
            correct_option VARCHAR(255) NULL,
            xp INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->query($sql);

    // Older schemas declared correct_option as INT, which turned the answer text into 0.
    $col = $conn->query("SHOW COLUMNS FROM quizzes LIKE 'correct_option'");
    $type = $col ? strtolower($col->fetch_assoc()['Type'] ?? '') : '';
    if ($type !== '' && strpos($type, 'varchar') === false) {
        $conn->query('ALTER TABLE quizzes MODIFY correct_option VARCHAR(255) NULL');
    }
}

function lessonExists(mysqli $conn, int $lessonId): bool {
    $stmt = $conn->prepare('SELECT 1 FROM lessons WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
}

$data = readPayload();
$lessonId = isset($data['lesson_id']) ? (int)$data['lesson_id'] : 0;
$question = trim($data['question'] ?? '');
$options = $data['options'] ?? [];
$correctOption = trim($data['correct_option'] ?? '');
$xp = isset($data['xp']) ? (int)$data['xp'] : 0;

if ($lessonId <= 0 || $question === '') {
    respond(400, ['status' => 'error', 'message' => 'lesson_id and question are required.']);
}

// Normalize options to a JSON string.
if (!is_array($options)) {
    $options = [$options];
}
$optionsJson = json_encode(array_values($options));

try {
    ensureMissionsTable($conn);
    ensureQuizzesTable($conn);

    if (!lessonExists($conn, $lessonId)) {
        respond(404, ['status' => 'error', 'message' => 'Lesson not found.']);
    }

    $stmt = $conn->prepare('INSERT INTO quizzes (lesson_id, question, options_json, correct_option, xp) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare statement.');
    }

    $stmt->bind_param('isssi', $lessonId, $question, $optionsJson, $correctOption, $xp);
    $stmt->execute();
    $quizId = $stmt->insert_id;
    $stmt->close();

    respond(200, [
        'status' => 'success',
        'data' => [
            'id' => $quizId,
            'lesson_id' => $lessonId,
            'question' => $question,
            'options' => json_decode($optionsJson, true),
            'correct_option' => $correctOption,
            'xp' => $xp,
        ],
    ]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Failed to save quiz.']);
} finally {
    $conn->close();
}
