<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_helpers.php';

if (!isset($_SESSION['user_id'])) {
    respondJson(401, [
        'status' => 'error',
        'message' => 'Not authenticated.',
    ]);
    exit;
}

$payload = readRequestBody();

$lessonId = trim($payload['lessonId'] ?? '');
$score = isset($payload['score']) ? (int)$payload['score'] : null;
$totalQuestions = isset($payload['totalQuestions']) ? (int)$payload['totalQuestions'] : null;
$xp = isset($payload['xp']) ? (int)$payload['xp'] : null;
$bestXp = isset($payload['bestXp']) ? (int)$payload['bestXp'] : $xp;

if ($lessonId === '' || $score === null || $totalQuestions === null || $xp === null || $totalQuestions <= 0) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'Invalid payload.',
    ]);
    exit;
}

// The questions for this flow live only in JS/basic.js, so the server cannot grade them.
// Clamp the client numbers to what a real quiz could produce (10 XP per answer + 100 perfect bonus).
$totalQuestions = min($totalQuestions, 50);
$score = max(0, min($score, $totalQuestions));
$maxXp = $totalQuestions * 10 + 100;
$xp = max(0, min($xp, $maxXp));
$bestXp = max(0, min((int)$bestXp, $maxXp));

// Normalize lesson key for storage
$lessonKey = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($lessonId));
// Only the lessons defined in JS/basic.js exist; anything else would mint unlimited extra XP slots.
if (!preg_match('/^lesson-0[1-8]$/', $lessonKey)) {
    respondJson(400, [
        'status' => 'error',
        'message' => 'Unknown lesson.',
    ]);
    exit;
}
$scoreKey = "quiz_score_{$lessonKey}";
$xpKey = "quiz_xp_{$lessonKey}";
$correctKey = "quiz_correct_{$lessonKey}";
$userId = (int)$_SESSION['user_id'];

try {
    ensureProfileMetaTable($conn);
    $meta = fetchProfileMeta($conn, $userId);

    $previousBestXp = isset($meta[$xpKey]) ? (int)$meta[$xpKey] : 0;
    $safeBestXp = max($bestXp, $previousBestXp, 0);
    $previousTotal = isset($meta['xp_total']) ? (int)$meta['xp_total'] : 0;
    $newTotal = max(0, $previousTotal - $previousBestXp + $safeBestXp);

    $earnedPercentage = (int)round(($score / max($totalQuestions, 1)) * 100);
    $metaToSave = [
        $scoreKey => $earnedPercentage,
        $correctKey => $score,
        $xpKey => $safeBestXp,
        'xp_total' => $newTotal,
    ];

    saveProfileMeta($conn, $userId, $metaToSave);

    $mergedMeta = array_merge($meta, $metaToSave);
    $lessonsCompleted = updateRankMissions($conn, $userId, $mergedMeta);
    syncRankToScore($conn, $userId, $newTotal, $mergedMeta);

    if ($safeBestXp > $previousBestXp && $safeBestXp > 0) {
        $message = sprintf('Lesson %s completed with %d%% score.', $lessonId, $earnedPercentage);
        try {
            createNotification($conn, $userId, 'lesson', $message, [
                'lessonId' => $lessonId,
                'score' => $score,
                'totalQuestions' => $totalQuestions,
                'percentage' => $earnedPercentage,
                'xp' => $safeBestXp,
            ]);
        } catch (Throwable $e) {
            // Do not block quiz persistence if notification storage fails.
        }
    }

    respondJson(200, [
        'status' => 'success',
        'data' => [
          'lessonId' => $lessonId,
          'score' => $score,
          'totalQuestions' => $totalQuestions,
          'percentage' => $metaToSave[$scoreKey],
          'earnedXp' => $xp,
          'storedXp' => $safeBestXp,
          'xpTotal' => $newTotal,
          'lessonsCompleted' => $lessonsCompleted,
        ],
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'status' => 'error',
        'message' => 'Failed to save quiz result.',
    ]);
}
