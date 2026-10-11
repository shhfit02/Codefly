<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Codefly Dashboard</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</head>
<body class="bg-gray-100">

  <!-- Sidebar -->
  <?php $showModeToggle = true; include __DIR__ . '/partials/student_sidebar.php'; ?>

  

  <script src="../JS/home.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
