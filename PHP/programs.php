<?php
// Saved Code Block programs, one list per signed-in user (HTML/workspace.php).
//   GET                      list: [{id, name, updatedAt}]
//   GET  ?id=N               one program with its Blockly JSON
//   POST {name, program}     save (a program with the same name is overwritten)
//   POST {action:"delete", id}
// Programs were kept in one browser-wide localStorage slot before, so on a shared lab
// computer every user saw (and overwrote) the same program.
session_start();
require_once 'db.php';
require_once 'profile_helpers.php';

const PROGRAM_MAX_BYTES = 200000;  // a large Blockly program is ~20 KB
const PROGRAM_MAX_PER_USER = 50;
const PROGRAM_NAME_MAX = 60;

if (!isset($_SESSION['user_id'])) {
    respondJson(401, ['status' => 'error', 'message' => 'Please sign in to use saved programs.']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

function ensureProgramsTable(mysqli $conn): void
{
    $exists = $conn->query("SHOW TABLES LIKE 'user_programs'");
    if ($exists && $exists->num_rows > 0) {
        return;
    }
    $conn->query("
        CREATE TABLE user_programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(60) NOT NULL,
            program MEDIUMTEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_program (user_id, name),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

try {
    ensureProgramsTable($conn);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare('SELECT id, name, program, updated_at FROM user_programs WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            respondJson(404, ['status' => 'error', 'message' => 'Program not found.']);
            exit;
        }
        respondJson(200, ['status' => 'success', 'data' => [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'program' => json_decode($row['program'], true),
            'updatedAt' => $row['updated_at'],
        ]]);
        exit;
    }

    if ($method === 'GET') {
        $stmt = $conn->prepare('SELECT id, name, updated_at FROM user_programs WHERE user_id = ? ORDER BY updated_at DESC, id DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = ['id' => (int)$row['id'], 'name' => $row['name'], 'updatedAt' => $row['updated_at']];
        }
        $stmt->close();
        respondJson(200, ['status' => 'success', 'data' => $programs]);
        exit;
    }

    if ($method !== 'POST') {
        respondJson(405, ['status' => 'error', 'message' => 'Unsupported request method.']);
        exit;
    }

    $body = readRequestBody();

    if (($body['action'] ?? '') === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM user_programs WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        respondJson($deleted ? 200 : 404, $deleted
            ? ['status' => 'success', 'message' => 'Program deleted.']
            : ['status' => 'error', 'message' => 'Program not found.']);
        exit;
    }

    $name = trim((string)($body['name'] ?? ''));
    $program = $body['program'] ?? null;
    if ($name === '' || mb_strlen($name) > PROGRAM_NAME_MAX) {
        respondJson(422, ['status' => 'error', 'message' => 'Give the program a name of 1-' . PROGRAM_NAME_MAX . ' characters.']);
        exit;
    }
    if (!is_array($program)) {
        respondJson(422, ['status' => 'error', 'message' => 'Nothing to save.']);
        exit;
    }
    $programJson = json_encode($program, JSON_UNESCAPED_UNICODE);
    if ($programJson === false || strlen($programJson) > PROGRAM_MAX_BYTES) {
        respondJson(413, ['status' => 'error', 'message' => 'This program is too large to save.']);
        exit;
    }

    // Overwriting an existing name is always allowed; only new names count towards the limit.
    $stmt = $conn->prepare('SELECT id FROM user_programs WHERE user_id = ? AND name = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $name);
    $stmt->execute();
    $stmt->bind_result($existingId);
    $exists = $stmt->fetch();
    $stmt->close();

    if (!$exists) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM user_programs WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count >= PROGRAM_MAX_PER_USER) {
            respondJson(409, ['status' => 'error', 'message' => 'You have ' . PROGRAM_MAX_PER_USER . ' saved programs. Delete one to save a new name.']);
            exit;
        }
    }

    $stmt = $conn->prepare('
        INSERT INTO user_programs (user_id, name, program) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE program = VALUES(program), updated_at = CURRENT_TIMESTAMP
    ');
    $stmt->bind_param('iss', $userId, $name, $programJson);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('SELECT id FROM user_programs WHERE user_id = ? AND name = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $name);
    $stmt->execute();
    $stmt->bind_result($savedId);
    $stmt->fetch();
    $stmt->close();

    respondJson(200, ['status' => 'success', 'message' => $exists ? 'Program updated.' : 'Program saved.', 'data' => [
        'id' => (int)$savedId,
        'name' => $name,
        'overwritten' => (bool)$exists,
    ]]);
} catch (Throwable $e) {
    error_log('programs.php failed: ' . $e->getMessage());
    respondJson(500, ['status' => 'error', 'message' => 'Saved programs are unavailable right now.']);
} finally {
    $conn->close();
}
