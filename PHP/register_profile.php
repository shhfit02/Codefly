<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/register-profile.html');
    exit;
}

$profileKey = 'registration_profile';
$valuesKey = 'profile_values';
$errorsKey = 'profile_errors';

$values = [
    'fullname' => trim($_POST['fullname'] ?? ''),
    'age' => trim($_POST['age'] ?? ''),
    'gender' => $_POST['gender'] ?? ''
];

$errors = [];

if ($values['fullname'] === '') {
    $errors[] = 'Full name is required.';
}

$ageOptions = ['options' => ['min_range' => 13, 'max_range' => 100]];
if ($values['age'] === '' || filter_var($values['age'], FILTER_VALIDATE_INT, $ageOptions) === false) {
    $errors[] = 'Age must be a number between 13 and 100.';
}

if ($values['gender'] === '') {
    $errors[] = 'Please select your gender.';
} else {
    $allowedGender = ['male', 'female', 'prefer-not'];
    if (!in_array($values['gender'], $allowedGender, true)) {
        $errors[] = 'Please select a valid gender option.';
    }
}

if (empty($errors)) {
    $values['age'] = (int)$values['age'];
}

if (!empty($errors)) {
    $_SESSION[$errorsKey] = $errors;
    $_SESSION[$valuesKey] = $values;
    header('Location: ../HTML/register-profile.html');
    exit;
}

$_SESSION[$profileKey] = $values;
unset($_SESSION[$errorsKey], $_SESSION[$valuesKey]);

header('Location: ../HTML/register.html');
exit;
