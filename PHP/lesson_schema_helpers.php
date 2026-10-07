<?php

/**
 * Older databases (created from codefly_database.sql) declared lessons.duration and
 * lessons.quiz_questions as INT, which silently turned "6 min" into 6 and quiz JSON into 0.
 * Widen those columns to the types the endpoints actually write.
 */
function ensureLessonColumnTypes(mysqli $conn): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $expected = [
        'duration' => ['match' => 'varchar', 'sql' => 'ALTER TABLE lessons MODIFY duration VARCHAR(64) NULL'],
        'quiz_questions' => ['match' => 'text', 'sql' => 'ALTER TABLE lessons MODIFY quiz_questions TEXT NULL'],
        'video_url' => ['match' => 'varchar(512)', 'sql' => 'ALTER TABLE lessons MODIFY video_url VARCHAR(512) NULL'],
    ];

    foreach ($expected as $column => $spec) {
        $result = $conn->query("SHOW COLUMNS FROM lessons LIKE '{$column}'");
        if (!$result || $result->num_rows === 0) {
            continue;
        }
        $type = strtolower($result->fetch_assoc()['Type'] ?? '');
        if (strpos($type, $spec['match']) === false) {
            $conn->query($spec['sql']);
            if ($column === 'quiz_questions') {
                // The INT column stored 0 in place of every quiz; those rows really have no quiz.
                $conn->query("UPDATE lessons SET quiz_questions = NULL WHERE quiz_questions = '0'");
            }
        }
    }

    $ready = true;
}

/**
 * Remove students' quiz progress (quiz_score_<id>, quiz_xp_<id>) for lessons that are being deleted,
 * so "lessons completed" never counts lessons that no longer exist. Earned XP (xp_total) is kept.
 */
function deleteLessonProgress(mysqli $conn, array $lessonIds): void
{
    $keys = [];
    foreach ($lessonIds as $lessonId) {
        $keys[] = 'quiz_score_' . (int)$lessonId;
        $keys[] = 'quiz_xp_' . (int)$lessonId;
    }
    if (!$keys) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $conn->prepare("DELETE FROM user_profile_meta WHERE meta_key IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($keys)), ...$keys);
    $stmt->execute();
    $stmt->close();
}
