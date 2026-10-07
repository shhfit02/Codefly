<?php
// Updates a module and syncs its lessons (and quiz questions) with provided data.
// Lessons sent with an existing id are updated in place; new ones are inserted; missing ones are deleted.
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

function ensureModuleTable(mysqli $conn): void {
    $conn->query("
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
    ");
    $hasImagePath = $conn->query("SHOW COLUMNS FROM modules LIKE 'image_path'");
    if ($hasImagePath && $hasImagePath->num_rows === 0) {
        $conn->query("ALTER TABLE modules ADD COLUMN image_path TEXT NULL AFTER description");
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

    $columns = [
        'duration' => "ALTER TABLE lessons ADD COLUMN duration VARCHAR(64) NULL AFTER description",
        'status' => "ALTER TABLE lessons ADD COLUMN status VARCHAR(16) NULL",
        'video_url' => "ALTER TABLE lessons ADD COLUMN video_url VARCHAR(512) NULL",
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
    if (!$stmt) return false;
    $stmt->bind_param('i', $moduleId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : ('lesson-' . time());
}

function normalizeQuizItems($raw): array {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) return [];
    return array_values(array_filter($raw, function ($item) {
        return is_array($item) && !empty($item['question']);
    }));
}

function focusExists(mysqli $conn, string $focus, int $excludeLessonId = 0): bool {
    $stmt = $conn->prepare('SELECT 1 FROM lessons WHERE focus = ? AND id <> ? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('si', $focus, $excludeLessonId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function ensureUniqueFocus(mysqli $conn, string $baseFocus, array &$used, int $excludeLessonId = 0): string {
    $focus = $baseFocus;
    $suffix = 1;
    while (isset($used[$focus]) || focusExists($conn, $focus, $excludeLessonId)) {
        $suffix += 1;
        $focus = $baseFocus . '-' . $suffix;
    }
    $used[$focus] = true;
    return $focus;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
}

$data = readPayload();
$moduleId = isset($data['module_id']) ? (int)$data['module_id'] : 0;
$lessons = isset($data['lessons']) && is_array($data['lessons']) ? $data['lessons'] : [];
$title = trim($data['title'] ?? '');
$subtitle = trim($data['subtitle'] ?? '');
$level = trim($data['level'] ?? 'custom');
$description = trim($data['description'] ?? '');
$imagePath = trim($data['image'] ?? '');
$syllabusPath = trim($data['syllabus'] ?? $data['syllabus_path'] ?? '');
$lockedRaw = $data['locked'] ?? '';
$lockedValue = is_bool($lockedRaw) ? ($lockedRaw ? '1' : '0') : (string)$lockedRaw;
$locked = in_array(strtolower($lockedValue), ['1', 'true', 'on', 'yes'], true) ? 1 : 0;

if ($moduleId <= 0 || $title === '' || empty($lessons)) {
    respond(400, ['status' => 'error', 'message' => 'module_id, title, and at least one lesson are required.']);
}

try {
    ensureModuleTable($conn);
    ensureMissionsTable($conn);
    ensureLessonColumnTypes($conn);
    ensureMissionQuizTable($conn);

    if (!moduleExists($conn, $moduleId)) {
        respond(404, ['status' => 'error', 'message' => 'Module not found.']);
    }

    $inTx = $conn->begin_transaction();

    $stmt = $conn->prepare('UPDATE modules SET title = ?, subtitle = ?, level = ?, description = ?, image_path = ?, syllabus_path = ?, locked = ? WHERE id = ?');
    if (!$stmt) throw new RuntimeException('Failed to prepare module update.');
    $stmt->bind_param('ssssssii', $title, $subtitle, $level, $description, $imagePath, $syllabusPath, $locked, $moduleId);
    if (!$stmt->execute()) throw new RuntimeException('Failed to update module.');
    $stmt->close();

    // Existing lessons keep their ids so student progress (quiz_score_<lessonId>) stays attached.
    $existingIds = [];
    $stmtIds = $conn->prepare('SELECT id FROM lessons WHERE module_id = ?');
    if (!$stmtIds) throw new RuntimeException('Failed to prepare lesson lookup.');
    $stmtIds->bind_param('i', $moduleId);
    $stmtIds->execute();
    $resIds = $stmtIds->get_result();
    while ($row = $resIds->fetch_assoc()) {
        $existingIds[(int)$row['id']] = true;
    }
    $stmtIds->close();

    $stmtMission = $conn->prepare('INSERT INTO lessons (module_id, title, focus, description, duration, status, video_url, what_you_learn, learning_outcomes, quiz_questions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmtMission) throw new RuntimeException('Failed to prepare lesson insert.');
    $stmtUpdate = $conn->prepare('UPDATE lessons SET title = ?, focus = ?, description = ?, duration = ?, status = ?, video_url = ?, what_you_learn = ?, learning_outcomes = ?, quiz_questions = ? WHERE id = ? AND module_id = ?');
    if (!$stmtUpdate) throw new RuntimeException('Failed to prepare lesson update.');
    $stmtQuizClear = $conn->prepare('DELETE FROM lesson_quiz_questions WHERE lesson_id = ?');
    if (!$stmtQuizClear) throw new RuntimeException('Failed to prepare quiz cleanup.');
    $stmtQuiz = $conn->prepare('INSERT INTO lesson_quiz_questions (lesson_id, question, choice1, choice2, choice3, choice4, correct_choice) VALUES (?, ?, ?, ?, ?, ?, ?)');

    $usedFocuses = [];
    $keptIds = [];
    foreach ($lessons as $lesson) {
        $lessonTitle = trim($lesson['title'] ?? '');
        if ($lessonTitle === '') continue;
        $requestedId = isset($lesson['id']) ? (int)$lesson['id'] : 0;
        $existingId = ($requestedId > 0 && isset($existingIds[$requestedId]) && !isset($keptIds[$requestedId])) ? $requestedId : 0;
        $focusInput = trim($lesson['focus'] ?? '');
        $focusBase = $focusInput !== '' ? slugify($focusInput) : slugify($lessonTitle);
        $focus = ensureUniqueFocus($conn, $focusBase, $usedFocuses, $existingId);
        $lessonDesc = trim($lesson['description'] ?? '');
        $duration = trim($lesson['duration'] ?? '');
        $status = trim($lesson['status'] ?? '');
        $videoUrl = trim($lesson['videoUrl'] ?? $lesson['video_url'] ?? '');
        $what = trim($lesson['whatYouLearn'] ?? $lesson['what_you_learn'] ?? '');
        $outcomes = trim($lesson['learningOutcomes'] ?? $lesson['learning_outcomes'] ?? '');
        $quizRaw = $lesson['quizQuestions'] ?? $lesson['quiz_questions'] ?? '';
        $quizArray = normalizeQuizItems($quizRaw);
        $quizJson = $quizArray ? json_encode($quizArray) : '';

        if ($existingId > 0) {
            $stmtUpdate->bind_param(
                'sssssssssii',
                $lessonTitle,
                $focus,
                $lessonDesc,
                $duration,
                $status,
                $videoUrl,
                $what,
                $outcomes,
                $quizJson,
                $existingId,
                $moduleId
            );
            if (!$stmtUpdate->execute()) throw new RuntimeException('Failed to update lesson.');
            $lessonId = $existingId;

            $stmtQuizClear->bind_param('i', $lessonId);
            if (!$stmtQuizClear->execute()) throw new RuntimeException('Failed to reset lesson quiz.');
        } else {
            $stmtMission->bind_param(
                'isssssssss',
                $moduleId,
                $lessonTitle,
                $focus,
                $lessonDesc,
                $duration,
                $status,
                $videoUrl,
                $what,
                $outcomes,
                $quizJson
            );
            if (!$stmtMission->execute()) throw new RuntimeException('Failed to insert lesson.');
            $lessonId = $stmtMission->insert_id;
        }
        $keptIds[$lessonId] = true;

        if ($stmtQuiz && $quizArray) {
            foreach ($quizArray as $mcq) {
                $q = trim($mcq['question'] ?? '');
                $choices = $mcq['choices'] ?? [];
                $c1 = trim($choices[0] ?? '');
                $c2 = trim($choices[1] ?? '');
                $c3 = trim($choices[2] ?? '');
                $c4 = trim($choices[3] ?? '');
                $correct = isset($mcq['correct_index']) ? (int)$mcq['correct_index'] : -1;
                if ($q === '' || $c1 === '' || $c2 === '' || $correct < 0 || $correct > 3) {
                    continue;
                }
                $stmtQuiz->bind_param('isssssi', $lessonId, $q, $c1, $c2, $c3, $c4, $correct);
                $stmtQuiz->execute();
            }
        }
    }

    $stmtMission->close();
    $stmtUpdate->close();
    $stmtQuizClear->close();
    if ($stmtQuiz) $stmtQuiz->close();

    // Lessons removed in the editor are deleted (cascades to lesson_quiz_questions).
    $removedIds = array_diff(array_keys($existingIds), array_keys($keptIds));
    if ($removedIds) {
        $stmtDel = $conn->prepare('DELETE FROM lessons WHERE id = ? AND module_id = ?');
        if (!$stmtDel) throw new RuntimeException('Failed to prepare lesson delete.');
        foreach ($removedIds as $removedId) {
            $stmtDel->bind_param('ii', $removedId, $moduleId);
            if (!$stmtDel->execute()) throw new RuntimeException('Failed to delete lesson.');
        }
        $stmtDel->close();
        deleteLessonProgress($conn, $removedIds);
    }

    if ($inTx) {
        $conn->commit();
    }

    respond(200, ['status' => 'success', 'data' => ['module_id' => $moduleId]]);
} catch (Throwable $e) {
    if (!empty($inTx)) {
        $conn->rollback();
    }
    respond(500, ['status' => 'error', 'message' => 'Unable to update module.']);
} finally {
    $conn->close();
}
