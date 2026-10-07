<?php
// Returns all modules with their lessons. For students it also returns their own progress
// (a lesson counts as completed once its quiz score is recorded, same as the profile pages).
session_start();
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

function ensureMissionTable(mysqli $conn): void {
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
            CONSTRAINT fk_missions_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $columns = [
        'duration' => "ALTER TABLE lessons ADD COLUMN duration VARCHAR(64) NULL AFTER description",
        'status' => "ALTER TABLE lessons ADD COLUMN status VARCHAR(16) NULL AFTER duration",
        'video_url' => "ALTER TABLE lessons ADD COLUMN video_url VARCHAR(512) NULL AFTER status",
        'what_you_learn' => "ALTER TABLE lessons ADD COLUMN what_you_learn TEXT NULL AFTER video_url",
        'learning_outcomes' => "ALTER TABLE lessons ADD COLUMN learning_outcomes TEXT NULL AFTER what_you_learn",
        'quiz_questions' => "ALTER TABLE lessons ADD COLUMN quiz_questions TEXT NULL AFTER learning_outcomes",
    ];
    foreach ($columns as $col => $sql) {
        $result = $conn->query("SHOW COLUMNS FROM lessons LIKE '{$col}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }
}

/**
 * Lesson ids the user has finished (has a quiz_score_<lessonId> entry in user_profile_meta).
 */
function fetchCompletedLessonIds(mysqli $conn, int $userId): array {
    $completed = [];
    $stmt = $conn->prepare("SELECT meta_key, meta_value FROM user_profile_meta WHERE user_id = ? AND meta_key LIKE 'quiz\\_score\\_%'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['meta_value'] === '' || $row['meta_value'] === null) {
            continue;
        }
        $completed[(int)substr($row['meta_key'], strlen('quiz_score_'))] = true;
    }
    $stmt->close();
    return $completed;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, ['status' => 'error', 'message' => 'Authentication required.']);
}

try {
    ensureModuleTable($conn);
    ensureMissionTable($conn);

    $isAdmin = isAdminSession($conn);
    $completedLessons = fetchCompletedLessonIds($conn, (int)$_SESSION['user_id']);

    // Load modules with lesson counts
    $modules = [];
    $res = $conn->query("
        SELECT m.id, m.title, m.subtitle, m.level, m.description, m.image_path, m.syllabus_path, m.locked, m.created_at,
               COUNT(l.id) AS lesson_count
        FROM modules m
        LEFT JOIN lessons l ON l.module_id = m.id
        GROUP BY m.id
        ORDER BY m.id ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $modules[$row['id']] = $row + ['lessons' => []];
        }
    }

    // Attach lessons for each module
    if (!empty($modules)) {
        $lessonRes = $conn->query("
            SELECT id, module_id, title, focus, description, duration, status, video_url, what_you_learn, learning_outcomes, quiz_questions
            FROM lessons
            ORDER BY module_id ASC, id ASC
        ");
        if ($lessonRes) {
            while ($row = $lessonRes->fetch_assoc()) {
                $modId = (int)$row['module_id'];
                if (!isset($modules[$modId])) {
                    continue;
                }
                // Quiz questions include the answers; only admins (module editor) get them.
                if (!$isAdmin) {
                    unset($row['quiz_questions']);
                }
                $row['completed'] = isset($completedLessons[(int)$row['id']]);
                $modules[$modId]['lessons'][] = $row;
            }
        }
    }

    foreach ($modules as &$module) {
        $module['completed_lessons'] = count(array_filter($module['lessons'], fn ($l) => $l['completed']));
    }
    unset($module);

    // Reindex sequentially for output
    $modules = array_values($modules);
    respond(200, ['status' => 'success', 'data' => $modules]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Unable to load modules.']);
} finally {
    $conn->close();
}
