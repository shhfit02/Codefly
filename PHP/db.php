<?php
// Hosted sites put their database login in db_config.php (not in git; copy
// db_config.example.php). Without it, the XAMPP defaults below are used.
$configFile = __DIR__ . '/db_config.php';
$config = is_file($configFile) ? require $configFile : [];
$host = $config['host'] ?? "localhost";
$user = $config['user'] ?? "root";
$pass = $config['password'] ?? "";
$dbname = $config['database'] ?? "codefly";
unset($configFile, $config);

// Endpoints answer with JSON; PHP warnings printed into the response would corrupt it.
// Errors still go to the Apache/PHP error log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
$expectsJson = stripos($accept, 'application/json') !== false
    || stripos($accept, 'text/json') !== false
    || $xhr === 'XMLHttpRequest';

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new RuntimeException("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    // Throw on any failed query so a write that did not reach the database is reported
    // as an error (and rolled back) instead of the endpoint replying "success".
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} catch (Throwable $e) {
    error_log('Codefly DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed.',
        ]);
    } else {
        echo "Database connection failed.";
    }
    exit;
}
?>
