<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

const XP_PER_CORRECT_ANSWER = 10;

$userId = (int)$_SESSION['user_id'];

try {
    ensureProfileMetaTable($conn);
    ensureProfileRecord($conn, $userId);

    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        throw new InvalidArgumentException('Invalid payload.');
    }

    $lessonId = (int)($body['lesson_id'] ?? 0);
    $answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];
    $reason = $body['reason'] ?? 'completed';

    if ($lessonId <= 0) {
        throw new InvalidArgumentException('lesson_id is required.');
    }

    // Grade on the server against the stored quiz so the client cannot choose its own XP.
    $stmt = $conn->prepare('SELECT module_id, quiz_questions FROM lessons WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$lesson) {
        throw new InvalidArgumentException('Lesson not found.');
    }

    $quiz = json_decode($lesson['quiz_questions'] ?? '', true);
    $quiz = is_array($quiz) ? array_values($quiz) : [];
    $total = count($quiz);

    $score = 0;
    $answered = 0;
    foreach ($quiz as $idx => $item) {
        if (!array_key_exists($idx, $answers) && !array_key_exists((string)$idx, $answers)) {
            continue;
        }
        $answered++;
        $given = $answers[$idx] ?? $answers[(string)$idx];
        $correct = is_array($item) ? ($item['correct_index'] ?? $item['correctIndex'] ?? null) : null;
        if (is_numeric($correct) && is_numeric($given) && (int)$given === (int)$correct) {
            $score++;
        }
    }

    $percentage = $total > 0 ? (int)round(($score / $total) * 100) : 0;
    $attemptXp = $score * XP_PER_CORRECT_ANSWER;

    // Only the best attempt per lesson counts, so retaking a quiz cannot farm XP.
    $meta = fetchProfileMeta($conn, $userId);
    $xpKey = 'quiz_xp_' . $lessonId;
    $scoreKey = 'quiz_score_' . $lessonId;
    $previousBestXp = isset($meta[$xpKey]) ? (int)$meta[$xpKey] : 0;
    $previousBestScore = isset($meta[$scoreKey]) && $meta[$scoreKey] !== '' ? (int)$meta[$scoreKey] : null;
    $xpAwarded = max(0, $attemptXp - $previousBestXp);

    $currentXp = isset($meta['xp_total']) ? (int)$meta['xp_total'] : 0;
    $newXp = $currentXp + $xpAwarded;

    $metaToSave = [
        $scoreKey => (string)max($percentage, $previousBestScore ?? 0),
        $xpKey => (string)max($attemptXp, $previousBestXp),
        'xp_total' => (string)$newXp,
    ];
    saveProfileMeta($conn, $userId, $metaToSave);

    // Sync rank with XP thresholds
    syncRankToScore($conn, $userId, $newXp, array_merge($meta, $metaToSave));

    echo json_encode([
        'status' => 'success',
        'data' => [
          'xp_awarded' => $xpAwarded,
          'attempt_xp' => $attemptXp,
          'best_xp' => max($attemptXp, $previousBestXp),
          'xp_total' => $newXp,
          'score' => $score,
          'answered' => $answered,
          'total' => $total,
          'percentage' => $percentage,
          'lesson_id' => $lessonId,
          'module_id' => (int)$lesson['module_id'],
          'reason' => $reason,
        ],
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to award XP.']);
} finally {
    $conn->close();
}
