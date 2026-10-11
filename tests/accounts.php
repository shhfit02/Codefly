<?php
// Test accounts for the Playwright suite.
//
//   php tests/accounts.php setup     create (or reset) one student and one admin test account
//   php tests/accounts.php cleanup   delete them and their data
//
// Passwords are random and written OUTSIDE the web root (your home folder), because this
// project lives under htdocs and anything inside it can be downloaded through Apache.
// Delete the accounts with `cleanup` before deploying or handing the database over.

if (PHP_SAPI !== 'cli') {
    // Never runnable through the web server.
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/../PHP/profile_helpers.php';

const TEST_ACCOUNTS = [
    'student' => ['username' => 'zz_test_student', 'email' => 'zz_test_student@example.invalid', 'level' => 'user'],
    'admin'   => ['username' => 'zz_test_admin',   'email' => 'zz_test_admin@example.invalid',   'level' => 'admin'],
];

function credentialsFile(): string
{
    $home = getenv('USERPROFILE') ?: getenv('HOME');
    if (!$home) {
        fwrite(STDERR, "Cannot find your home folder (USERPROFILE/HOME not set).\n");
        exit(1);
    }
    return rtrim($home, '\\/') . DIRECTORY_SEPARATOR . '.codefly-test-accounts.json';
}

function findUserId(mysqli $conn, string $username): ?int
{
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id);
    $found = $stmt->fetch() ? (int)$id : null;
    $stmt->close();
    return $found;
}

function setup(mysqli $conn): void
{
    $credentials = [];
    foreach (TEST_ACCOUNTS as $role => $account) {
        $password = bin2hex(random_bytes(12));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = findUserId($conn, $account['username']);

        $conn->begin_transaction();
        if ($userId === null) {
            // Same steps as PHP/addUser.php: users row, profile row, rank row.
            $stmt = $conn->prepare('INSERT INTO users (username, email, password, level) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $account['username'], $account['email'], $hash, $account['level']);
            $stmt->execute();
            $userId = $conn->insert_id;
            $stmt->close();

            $fullName = 'Test ' . ucfirst($role);
            $age = 20;
            $gender = 'prefer-not';
            $stmt = $conn->prepare('INSERT INTO user_profiles (user_id, full_name, age, gender) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isis', $userId, $fullName, $age, $gender);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare('UPDATE users SET password = ?, level = ? WHERE id = ?');
            $stmt->bind_param('ssi', $hash, $account['level'], $userId);
            $stmt->execute();
            $stmt->close();
        }
        ensureRankRecord($conn, $userId);
        $conn->commit();

        $credentials[$role] = ['username' => $account['username'], 'password' => $password];
        echo "  {$role}: {$account['username']} (id {$userId})\n";
    }

    $file = credentialsFile();
    file_put_contents($file, json_encode($credentials, JSON_PRETTY_PRINT));
    echo "Credentials written to {$file}\n";
}

function cleanup(mysqli $conn): void
{
    // Same per-user tables as PHP/deleteAccount.php, plus activity tables.
    $tables = ['user_tokens', 'user_ranks', 'user_profile_meta', 'user_settings', 'user_profiles',
               'user_notifications', 'user_lesson_scores', 'module_visits', 'password_resets'];
    foreach (TEST_ACCOUNTS as $role => $account) {
        $userId = findUserId($conn, $account['username']);
        if ($userId === null) {
            echo "  {$role}: not present\n";
            continue;
        }
        $conn->begin_transaction();
        foreach ($tables as $table) {
            if ($conn->query("SHOW TABLES LIKE '{$table}'")->num_rows === 0) {
                continue;
            }
            $hasColumn = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE 'user_id'");
            if (!$hasColumn || $hasColumn->num_rows === 0) {
                continue; // table missing, or not keyed by user
            }
            $stmt = $conn->prepare("DELETE FROM `{$table}` WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        echo "  {$role}: deleted {$account['username']} (id {$userId})\n";
    }
    $file = credentialsFile();
    if (is_file($file)) {
        unlink($file);
        echo "Removed {$file}\n";
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$command = $argv[1] ?? '';
if ($command === 'setup') {
    setup($conn);
} elseif ($command === 'cleanup') {
    cleanup($conn);
} else {
    fwrite(STDERR, "Usage: php tests/accounts.php setup|cleanup\n");
    exit(1);
}
