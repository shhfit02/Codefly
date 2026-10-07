<?php
session_start();
header('Content-Type: application/json');

$profileKey = 'registration_profile';
$valuesKey = 'register_values';
$errorsKey = 'register_errors';
$agreeKey = 'register_agree';

$defaultValues = [
    'username' => '',
    'email' => ''
];

$profile = $_SESSION[$profileKey] ?? null;
$values = $_SESSION[$valuesKey] ?? $defaultValues;
$errors = $_SESSION[$errorsKey] ?? [];
$agreeChecked = $_SESSION[$agreeKey] ?? false;

unset($_SESSION[$errorsKey], $_SESSION[$valuesKey], $_SESSION[$agreeKey]);

$response = [
    'profile' => $profile,
    'values' => $values,
    'errors' => $errors,
    'agreeChecked' => $agreeChecked
];

if ($profile === null) {
    $response['redirect'] = '../HTML/register-profile.html';
}

echo json_encode($response);
