<?php
// Returns aggregated module analysis for admin dashboards.
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

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
}

function ensureMissionsTable(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            focus VARCHAR(64) NULL UNIQUE,
            description TEXT NULL,
            video_url VARCHAR(512) NULL,
            what_you_learn TEXT NULL,
            learning_outcomes TEXT NULL,
            quiz_questions TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

try {
    ensureModuleTable($conn);
    ensureMissionsTable($conn);

    $sql = "
        SELECT
            m.id,
            m.title,
            m.subtitle,
            m.level,
            m.locked,
            m.created_at,
            m.syllabus_path,
            COUNT(l.id) AS lessons_count,
            SUM(CASE WHEN l.quiz_questions IS NOT NULL AND l.quiz_questions != '' AND l.quiz_questions != '0' THEN 1 ELSE 0 END) AS lessons_with_quiz
        FROM modules m
        LEFT JOIN lessons l ON l.module_id = m.id
        GROUP BY m.id
        ORDER BY m.id DESC
    ";

    $data = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'],
                'level' => $row['level'],
                'locked' => (bool)$row['locked'],
                'created_at' => $row['created_at'],
                'syllabus_path' => $row['syllabus_path'],
                'lessons_count' => (int)$row['lessons_count'],
                'lessons_with_quiz' => (int)($row['lessons_with_quiz'] ?? 0),
                // Student metrics not tracked in current schema; default to 0 for dashboards.
                'students_total' => 0,
                'students_completed' => 0,
            ];
        }
    }

    respond(200, ['status' => 'success', 'data' => $data]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Unable to load module analysis.']);
} finally {
    $conn->close();
}
