<?php
// Copy this file to mail_config.php and fill in real values.
// mail_config.php is ignored by git so credentials never get committed.
// For Gmail, "password" must be a 16-character App Password
// (Google Account > Security > 2-Step Verification > App passwords),
// not the normal account password.
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-address@gmail.com',
    'password' => 'your-app-password',
    'from_email' => 'your-address@gmail.com',
    'from_name' => 'CodeFly',
];
