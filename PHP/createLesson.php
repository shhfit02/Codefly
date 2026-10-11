<?php
// Endpoint to create a lesson record linked to a module.
// Accepts JSON or form-data:
//  - module_id (required, references modules.id)
//  - title (required)
//  - focus (optional, unique; auto-generated from title if missing)
//  - description (optional)
//  - video_url (optional)
//  - what_you_learn (optional)
//  - learning_outcomes (optional)
//  - quiz_questions (optional)
//    Expected format from NewModule.php: JSON array of
//      { question: string, choices: [choice1..choice4], correct_index: 0-3 }

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/lesson_schema_helpers.php';

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
    // Create table if missing
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

function ensureMissionQuizTable(mysqli $conn): void {
    // Dedicated table for parsed MCQs so they can be queried individually
    $conn->query("
        CREATE TABLE IF NOT EXISTS lesson_quiz_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL,
            question TEXT NOT NULL,
            choice1 VARCHAR(255) NOT NULL,
            choice2 VARCHAR(255) NOT NULL,
            choice3 VARCHAR(255) NOT NULL,
            choice4 VARCHAR(255) NOT NULL,
            correct_choice TINYINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_lqq_mission (lesson_id),
            CONSTRAINT fk_lqq_mission FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

function moduleExists(mysqli $conn, int $moduleId): bool {
    $stmt = $conn->prepare('SELECT 1 FROM modules WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $moduleId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function focusExists(mysqli $conn, string $focus): bool {
    $stmt = $conn->prepare('SELECT 1 FROM lessons WHERE focus = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $focus);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function ensureUniqueFocus(mysqli $conn, string $baseFocus): string {
    $focus = $baseFocus;
    $suffix = 1;
    while (focusExists($conn, $focus)) {
        $suffix += 1;
        $focus = $baseFocus . '-' . $suffix;
    }
    return $focus;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
}

$data = readPayload();
$moduleId = isset($data['module_id']) ? (int)$data['module_id'] : 0;
$title = trim($data['title'] ?? '');
$focusInput = trim($data['focus'] ?? $data['slug'] ?? '');
$description = trim($data['description'] ?? '');
$duration = trim($data['duration'] ?? '');
$status = trim($data['status'] ?? '');
$videoUrl = trim($data['video_url'] ?? '');
$whatYouLearn = trim($data['what_you_learn'] ?? '');
$learningOutcomes = trim($data['learning_outcomes'] ?? '');
$quizQuestions = trim($data['quiz_questions'] ?? '');

if ($moduleId <= 0 || $title === '') {
    respond(400, ['status' => 'error', 'message' => 'module_id and title are required.']);
}

// Normalize focus (URL-friendly)
$focus = $focusInput !== '' ? $focusInput : $title;
$focus = strtolower(trim($focus));
$focus = preg_replace('/[^a-z0-9-]+/', '-', $focus);
$focus = trim($focus, '-');
if ($focus === '') {
    $focus = null;
}

try {
    ensureMissionsTable($conn);
    ensureLessonColumnTypes($conn);
    ensureMissionQuizTable($conn);

    if (!moduleExists($conn, $moduleId)) {
        respond(404, ['status' => 'error', 'message' => 'Module not found.']);
    }

    if ($focus !== null) {
        $focus = ensureUniqueFocus($conn, $focus);
    }
    $stmt = $conn->prepare('INSERT INTO lessons (module_id, title, focus, description, duration, status, video_url, what_you_learn, learning_outcomes, quiz_questions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare statement.');
    }

    $stmt->bind_param(
        'isssssssss',
        $moduleId,
        $title,
        $focus,
        $description,
        $duration,
        $status,
        $videoUrl,
        $whatYouLearn,
        $learningOutcomes,
        $quizQuestions
    );
    $stmt->execute();
    $lessonId = $stmt->insert_id;
    $stmt->close();

    // Persist parsed MCQs into lesson_quiz_questions for easier querying
    if ($quizQuestions !== '') {
        $decoded = json_decode($quizQuestions, true);
        if (is_array($decoded)) {
            $insert = $conn->prepare('INSERT INTO lesson_quiz_questions (lesson_id, question, choice1, choice2, choice3, choice4, correct_choice) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($insert) {
                foreach ($decoded as $mcq) {
                    $question = trim($mcq['question'] ?? '');
                    $choices = $mcq['choices'] ?? [];
                    $c1 = trim($choices[0] ?? '');
                    $c2 = trim($choices[1] ?? '');
                    $c3 = trim($choices[2] ?? '');
                    $c4 = trim($choices[3] ?? '');
                    $correct = isset($mcq['correct_index']) ? (int)$mcq['correct_index'] : -1;
                    if ($question === '' || $c1 === '' || $c2 === '' || $correct < 0 || $correct > 3) {
                        continue;
                    }
                    $insert->bind_param('isssssi', $lessonId, $question, $c1, $c2, $c3, $c4, $correct);
                    $insert->execute();
                }
                $insert->close();
            }
        }
    }

    respond(200, [
        'status' => 'success',
        'data' => [
            'id' => $lessonId,
        'module_id' => $moduleId,
        'title' => $title,
        'focus' => $focus,
        'description' => $description,
        'duration' => $duration,
        'status' => $status,
        'video_url' => $videoUrl,
        'what_you_learn' => $whatYouLearn,
        'learning_outcomes' => $learningOutcomes,
        'quiz_questions' => $quizQuestions,
    ],
    ]);
} catch (mysqli_sql_exception $e) {
    $message = 'Failed to save lesson.';
    if ((int)$e->getCode() === 1062) { // duplicate entry
        $message = 'Focus already exists. Please choose another.';
    }
    respond(500, ['status' => 'error', 'message' => $message]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Failed to save lesson.']);
} finally {
    $conn->close();
}
