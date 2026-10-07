<?php
// Weather for the home page widget. The OpenWeatherMap key stays on the server
// (PHP/weather_config.php, not in git) instead of being shipped in JS/home.js.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$configFile = __DIR__ . '/weather_config.php';
$config = is_file($configFile) ? require $configFile : [];
$apiKey = $config['openweathermap_key'] ?? getenv('OPENWEATHERMAP_KEY') ?: '';
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Weather is not configured.']);
    exit;
}

$lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lon = filter_var($_GET['lon'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lon === false || abs($lat) > 90 || abs($lon) > 180) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid lat and lon are required.']);
    exit;
}

// Cache per location for 10 minutes so page loads don't use up the API quota.
$cacheFile = sys_get_temp_dir() . '/codefly_weather_' . md5(round($lat, 2) . ',' . round($lon, 2)) . '.json';
if (is_file($cacheFile) && time() - filemtime($cacheFile) < 600) {
    echo file_get_contents($cacheFile);
    exit;
}

$url = 'https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
    'lat' => $lat,
    'lon' => $lon,
    'appid' => $apiKey,
    'units' => 'metric',
]);
$response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]));
$data = $response ? json_decode($response, true) : null;
if (!is_array($data) || !isset($data['main'])) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Weather service unavailable.']);
    exit;
}

// Only what the widget shows.
$payload = json_encode([
    'name' => $data['name'] ?? '',
    'main' => $data['main'],
    'weather' => $data['weather'] ?? [],
    'wind' => $data['wind'] ?? [],
]);
@file_put_contents($cacheFile, $payload);
echo $payload;
