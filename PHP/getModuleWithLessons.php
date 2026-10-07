<?php
// Returns a module with its lessons (and quiz JSON) for editing.
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
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
            total_students INT NOT NULL DEFAULT 0,
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
    $hasTotalStudents = $conn->query("SHOW COLUMNS FROM modules LIKE 'total_students'");
    if ($hasTotalStudents && $hasTotalStudents->num_rows === 0) {
        $conn->query("ALTER TABLE modules ADD COLUMN total_students INT NOT NULL DEFAULT 0 AFTER locked");
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

$moduleId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_GET['module_id'] ?? 0);
if ($moduleId <= 0) {
    respond(400, ['status' => 'error', 'message' => 'module_id is required.']);
}

try {
    ensureModuleTable($conn);
    ensureMissionsTable($conn);

    $stmt = $conn->prepare('SELECT id, title, subtitle, level, description, image_path, syllabus_path, locked, total_students FROM modules WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare statement.');
    }
    $stmt->bind_param('i', $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $module = $result->fetch_assoc();
    $stmt->close();

    if (!$module) {
        respond(404, ['status' => 'error', 'message' => 'Module not found.']);
    }

    // Only admins (the module editor) may see answer keys; students are graded in awardXp.php.
    $includeAnswers = isAdminSession($conn);

    $lessons = [];
    $stmtMissions = $conn->prepare('SELECT id, module_id, title, focus, description, duration, status, video_url, what_you_learn, learning_outcomes, quiz_questions FROM lessons WHERE module_id = ? ORDER BY id ASC');
    if ($stmtMissions) {
        $stmtMissions->bind_param('i', $moduleId);
        $stmtMissions->execute();
        $resMissions = $stmtMissions->get_result();
        while ($row = $resMissions->fetch_assoc()) {
            $quiz = json_decode($row['quiz_questions'] ?? '', true);
            $quiz = is_array($quiz) ? $quiz : [];
            if (!$includeAnswers) {
                $quiz = array_map(function ($item) {
                    if (is_array($item)) {
                        unset($item['correct_index'], $item['correctIndex']);
                    }
                    return $item;
                }, $quiz);
                $row['quiz_questions'] = $quiz ? json_encode($quiz) : '';
            }
            $row['quiz'] = $quiz;
            $lessons[] = $row;
        }
        $stmtMissions->close();
    }

    respond(200, [
        'status' => 'success',
        'data' => [
            'module' => $module,
            'lessons' => $lessons,
        ],
    ]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Unable to load module.']);
} finally {
    $conn->close();
}
