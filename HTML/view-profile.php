<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Codefly | View Profile</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" data-page="view-profile">

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/partials/student_sidebar.php'; ?>

  <!-- MAIN SECTION -->
  <section class="home min-h-screen">
    <div class="p-6 space-y-6">
      <p id="viewProfileStatus" class="text-sm font-semibold text-red-600 hidden"></p>

      <!-- The cover sits behind the content, so the card grows with long names on phones. -->
      <div class="relative rounded-[32px] overflow-hidden shadow-xl border border-white/20">
        <img id="coverImage" src="../Assets/background-Profile.jpg" alt="" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-black/10"></div>
        <div class="relative min-h-[14rem] flex flex-col md:flex-row md:items-end justify-end md:justify-between gap-4 p-6">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-white min-w-0">
            <div class="relative shrink-0 self-start sm:self-auto">
              <img id="viewProfileAvatar" src="../Assets/StudentProfile.png" alt="Profile photo" class="w-24 h-24 md:w-28 md:h-28 rounded-3xl border-4 border-white/40 object-cover shadow-lg">
              <img id="viewRankBadge" src="../Assets/RankBronze.png" alt="" class="absolute -bottom-2 -right-2 w-10 h-10 drop-shadow-lg">
            </div>
            <div class="min-w-0">
              <p class="text-xs uppercase tracking-[0.3em] text-white/70">Pilot profile</p>
              <h1 id="viewProfileFullName" class="text-2xl md:text-3xl font-semibold break-words">&nbsp;</h1>
              <p id="viewProfileIdentity" class="text-white/80 text-sm">&nbsp;</p>
              <p id="viewProfileTagline" class="text-sm text-white/75 mt-1"></p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0">
            <button onclick="window.location.href='../HTML/edit-profile.php'" class="hidden px-4 py-2 rounded-xl bg-white text-slate-900 text-sm font-semibold shadow hover:bg-slate-100 inline-flex items-center gap-1">
              <i class='bx bx-edit-alt'></i> Edit profile
            </button>
            <button id="uploadCoverBtn" class="hidden px-4 py-2 rounded-xl bg-slate-900/45 border border-white/40 text-white text-sm font-semibold backdrop-blur hover:bg-slate-900/60 inline-flex items-center gap-1">
              <i class='bx bx-image-add'></i> Change cover
            </button>
            <input type="file" id="coverInput" accept="image/*" class="hidden">
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white shadow p-5 flex items-center gap-4">
          <img id="viewRankBadgeLarge" src="../Assets/RankBronze.png" alt="" class="w-14 h-14 shrink-0">
          <div class="min-w-0">
            <p class="text-sm text-gray-500">Rank</p>
            <p id="viewRankTitle" class="text-2xl font-semibold text-gray-900 leading-tight">--</p>
            <p id="viewRankStatus" class="text-xs font-semibold mt-1 inline-flex items-center gap-1"></p>
          </div>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">Total XP</p>
          <p id="viewMissionScore" class="text-3xl font-semibold text-gray-900 mt-1">--</p>
          <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden" aria-hidden="true">
            <div id="viewXpBar" class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500" style="width:0%"></div>
          </div>
          <p id="viewXpNext" class="text-xs text-gray-500 mt-1.5"></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">Lessons completed</p>
          <p class="text-3xl font-semibold text-gray-900 mt-1"><span id="viewFlightHours">--</span><span id="viewLessonsTotal" class="text-base font-medium text-gray-400"></span></p>
          <p id="viewModulesDone" class="text-xs text-gray-500 mt-1.5"></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">Login streak</p>
          <p class="text-3xl font-semibold text-gray-900 mt-1"><span id="viewStreak">--</span><span class="text-base font-medium text-gray-400"> days</span></p>
          <p id="viewLastLogin" class="text-xs text-gray-500 mt-1.5"></p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3 rounded-2xl bg-white shadow p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Module progress</h3>
            <span class="text-xs text-gray-500">A lesson counts once its quiz is done</span>
          </div>
          <ul id="moduleProgress" class="space-y-4">
            <li class="text-sm text-slate-400">Loading…</li>
          </ul>
        </div>

        <div class="xl:col-span-2 rounded-2xl bg-white shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Flight dossier</h3>
            <a href="../HTML/edit-profile.php" id="updateInfoLink" class="hidden text-sm font-semibold text-blue-600 hover:text-blue-700">Update info</a>
          </div>
          <dl class="grid grid-cols-1 sm:grid-cols-[7rem_1fr] sm:gap-y-3 text-sm">
            <dt class="text-gray-400">Full name</dt><dd id="viewDetailFullName" class="mb-3 sm:mb-0 font-semibold text-gray-900 break-words">--</dd>
            <dt class="text-gray-400">Age</dt><dd id="viewDetailAge" class="mb-3 sm:mb-0 font-semibold text-gray-900">--</dd>
            <dt class="text-gray-400">Gender</dt><dd id="viewDetailGender" class="mb-3 sm:mb-0 font-semibold text-gray-900">--</dd>
            <dt class="text-gray-400">School</dt><dd id="viewDetailSchool" class="mb-3 sm:mb-0 font-semibold text-gray-900 break-words">--</dd>
            <dt class="text-gray-400">Phone</dt><dd id="viewDetailPhone" class="mb-3 sm:mb-0 font-semibold text-gray-900">--</dd>
            <dt class="text-gray-400">Bio</dt><dd id="viewDetailBio" class="text-gray-700 leading-relaxed break-words">--</dd>
          </dl>
        </div>
      </div>
    </div>
  </section>
  <script src="../JS/home.js"></script>
  <script src="../JS/profilePages.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>

