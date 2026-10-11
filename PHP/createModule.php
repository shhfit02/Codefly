<?php
// Endpoint to create a module record.
// Accepts JSON or form-data:
//  - title (required)
//  - subtitle
//  - level (default: custom)
//  - description
//  - image (URL/path)
//  - locked (boolean-ish: "on", "true", "1")

header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth_helpers.php';
require_once 'lesson_schema_helpers.php';
require_once 'notification_helpers.php';

requireAdminJson($conn);

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function ensureModuleTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255) NULL,
            level VARCHAR(32) NOT NULL DEFAULT 'custom',
            description TEXT NULL,
            image_path TEXT NULL,
            syllabus_path TEXT NULL,
            locked TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->query($sql);

    // Ensure image_path column exists for older schemas that used image_url
    $hasImagePath = $conn->query("SHOW COLUMNS FROM modules LIKE 'image_path'");
    if ($hasImagePath && $hasImagePath->num_rows === 0) {
        $conn->query("ALTER TABLE modules ADD COLUMN image_path TEXT NULL AFTER description");
        // Migrate existing image_url values if that column exists
        $hasImageUrl = $conn->query("SHOW COLUMNS FROM modules LIKE 'image_url'");
        if ($hasImageUrl && $hasImageUrl->num_rows > 0) {
            $conn->query("UPDATE modules SET image_path = image_url WHERE image_path IS NULL OR image_path = ''");
        }
    }
    $hasSyllabusPath = $conn->query("SHOW COLUMNS FROM modules LIKE 'syllabus_path'");
    if ($hasSyllabusPath && $hasSyllabusPath->num_rows === 0) {
        $conn->query("ALTER TABLE modules ADD COLUMN syllabus_path TEXT NULL AFTER image_path");
    }
}

/**
 * Ensure a lessons table exists that is tied to modules via a foreign key.
 */
function ensureMissionsTable(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            focus VARCHAR(64) NULL UNIQUE,
            description TEXT NULL,
            duration VARCHAR(64) NULL,
            status VARCHAR(16) NULL,
            video_url VARCHAR(512) NULL,
            what_you_learn TEXT NULL,
            learning_outcomes TEXT NULL,
            quiz_questions TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Migrate existing slug column to focus if needed
    $hasFocus = $conn->query("SHOW COLUMNS FROM lessons LIKE 'focus'");
    $hasSlug = $conn->query("SHOW COLUMNS FROM lessons LIKE 'slug'");
    if ($hasFocus && $hasFocus->num_rows === 0 && $hasSlug && $hasSlug->num_rows > 0) {
        $conn->query("ALTER TABLE lessons CHANGE slug focus VARCHAR(64) NULL UNIQUE");
    }

    // Add new columns if missing
    $columns = [
        'video_url' => "ALTER TABLE lessons ADD COLUMN video_url VARCHAR(512) NULL",
        'duration' => "ALTER TABLE lessons ADD COLUMN duration VARCHAR(64) NULL",
        'status' => "ALTER TABLE lessons ADD COLUMN status VARCHAR(16) NULL",
        'what_you_learn' => "ALTER TABLE lessons ADD COLUMN what_you_learn TEXT NULL",
        'learning_outcomes' => "ALTER TABLE lessons ADD COLUMN learning_outcomes TEXT NULL",
        'quiz_questions' => "ALTER TABLE lessons ADD COLUMN quiz_questions TEXT NULL",
    ];
    foreach ($columns as $col => $sql) {
        $result = $conn->query("SHOW COLUMNS FROM lessons LIKE '{$col}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
}

try {
    ensureModuleTable($conn);
    ensureMissionsTable($conn);
    ensureLessonColumnTypes($conn);

    $data = readPayload();
    $title = trim($data['title'] ?? '');
    if ($title === '') {
        respond(400, ['status' => 'error', 'message' => 'Title is required.']);
    }

    $subtitle = trim($data['subtitle'] ?? '');
    $level = trim($data['level'] ?? 'custom');
    $description = trim($data['description'] ?? '');
    $imagePath = trim($data['image'] ?? '');
    $syllabusPath = trim($data['syllabus'] ?? $data['syllabus_path'] ?? '');
    $lockedRaw = $data['locked'] ?? '';
    // Support boolean JSON or string form inputs
    $lockedValue = is_bool($lockedRaw) ? ($lockedRaw ? '1' : '0') : (string)$lockedRaw;
    $locked = in_array(strtolower($lockedValue), ['1', 'true', 'on', 'yes'], true) ? 1 : 0;

    $stmt = $conn->prepare('INSERT INTO modules (title, subtitle, level, description, image_path, syllabus_path, locked) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare statement.');
    }
    $stmt->bind_param('ssssssi', $title, $subtitle, $level, $description, $imagePath, $syllabusPath, $locked);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    // Locked modules stay hidden from students, so they hear about it when it is unlocked.
    if (!$locked) {
        notifySafely(fn () => notifyRole($conn, 'student', 'module', "New module: {$title}", [
            'moduleId' => $newId,
            'url' => '../HTML/Student-ViewLesson.php?id=' . $newId,
        ]));
    }

    respond(200, [
        'status' => 'success',
        'data' => [
            'id' => $newId,
            'title' => $title,
            'subtitle' => $subtitle,
            'level' => $level,
            'description' => $description,
            'image' => $imagePath,
            'syllabus' => $syllabusPath,
            'locked' => (bool)$locked,
        ],
    ]);
} catch (Throwable $e) {
    error_log('createModule failed: ' . $e->getMessage());
    respond(500, ['status' => 'error', 'message' => 'Unable to save module.']);
} finally {
    $conn->close();
}
