<?php
function queueToast(string $type, string $message): void
{
    if (!isset($_SESSION['toast_messages']) || !is_array($_SESSION['toast_messages'])) {
        $_SESSION['toast_messages'] = [];
    }

    $_SESSION['toast_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}
