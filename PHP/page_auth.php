<?php
// Server-side gate for PHP pages. Call one of these at the very top of a page,
// before any HTML is sent, so the redirect header can still be set.
// The JSON endpoints keep their own checks (requireAdminJson); this stops the
// page itself from being served to visitors who are not signed in.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/toast_helpers.php';

function requireLoginPage(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        queueToast('warning', 'Please log in to continue.');
        header('Location: ../index.html');
        exit;
    }
}

function requireAdminPage(): void
{
    global $conn;
    requireLoginPage();
    // The level is read from the database, so a demoted admin loses access immediately.
    if (!isAdminSession($conn)) {
        queueToast('error', 'That page is for admins only.');
        header('Location: ../HTML/home.html');
        exit;
    }
}
