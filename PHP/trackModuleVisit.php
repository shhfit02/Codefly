<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, ['status' => 'error', 'message' => 'Not authenticated.']);
}

$userId = (int)$_SESSION['user_id'];
$moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : (int)($_GET['module_id'] ?? 0);
if ($moduleId <= 0) {
    respond(400, ['status' => 'error', 'message' => 'module_id is required.']);
}

try {
    // ensure modules has total_students column
    $conn->query("ALTER TABLE modules ADD COLUMN IF NOT EXISTS total_students INT NOT NULL DEFAULT 0");

    // ensure visit table
    $conn->query("
        CREATE TABLE IF NOT EXISTS module_visits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_module_user (module_id, user_id),
            INDEX idx_module (module_id),
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // insert or touch visit
    $stmt = $conn->prepare("INSERT INTO module_visits (module_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = created_at");
    $stmt->bind_param('ii', $moduleId, $userId);
    $stmt->execute();
    $stmt->close();

    // count distinct users
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM module_visits WHERE module_id = ?");
    $stmt->bind_param('i', $moduleId);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    $total = (int)$total;
    $update = $conn->prepare("UPDATE modules SET total_students = ? WHERE id = ?");
    $update->bind_param('ii', $total, $moduleId);
    $update->execute();
    $update->close();

    respond(200, [
        'status' => 'success',
        'data' => [
            'total_students' => $total,
        ],
    ]);
} catch (Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Unable to register visit.']);
} finally {
    $conn->close();
}
