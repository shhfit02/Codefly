<?php
// Deletes a module (cascades lessons via FK).
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
            locked TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

$data = readPayload();
$moduleId = isset($data['module_id']) ? (int)$data['module_id'] : 0;
if ($moduleId <= 0) {
    respond(400, ['status' => 'error', 'message' => 'module_id is required.']);
}

try {
    ensureModuleTable($conn);

    // Lessons go with the module (FK cascade); collect their ids first to clear students' quiz progress.
    $lessonIds = [];
    $stmtIds = $conn->prepare('SELECT id FROM lessons WHERE module_id = ?');
    $stmtIds->bind_param('i', $moduleId);
    $stmtIds->execute();
    $resIds = $stmtIds->get_result();
    while ($row = $resIds->fetch_assoc()) {
        $lessonIds[] = (int)$row['id'];
    }
    $stmtIds->close();

    $stmt = $conn->prepare('DELETE FROM modules WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare delete.');
    }
    $stmt->bind_param('i', $moduleId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        respond(404, ['status' => 'error', 'message' => 'Module not found.']);
    }
    deleteLessonProgress($conn, $lessonIds);

    respond(200, ['status' => 'success', 'data' => ['module_id' => $moduleId]]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Unable to delete module.']);
} finally {
    $conn->close();
}
