<?php
session_start();
header('Content-Type: application/json');

$profileKey = 'registration_profile';
$valuesKey = 'profile_values';
$errorsKey = 'profile_errors';

$defaultValues = [
    'fullname' => '',
    'age' => '',
    'gender' => ''
];

$values = $defaultValues;

if (isset($_SESSION[$valuesKey])) {
    $values = array_merge($values, $_SESSION[$valuesKey]);
} elseif (isset($_SESSION[$profileKey])) {
    $values = array_merge($values, $_SESSION[$profileKey]);
}

$errors = $_SESSION[$errorsKey] ?? [];
$profileComplete = isset($_SESSION[$profileKey]);

unset($_SESSION[$errorsKey]);

echo json_encode([
    'values' => $values,
    'errors' => $errors,
    'profileComplete' => $profileComplete
]);
