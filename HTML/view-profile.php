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

      <div class="relative rounded-[32px] overflow-hidden shadow-xl border border-white/20">
        <img id="coverImage" src="../Assets/background-Profile.jpg" alt="Cover" class="w-full h-56 object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent"></div>
        <div class="absolute inset-0 flex flex-col md:flex-row items-center md:items-end justify-between gap-6 p-6">
          <div class="flex items-center gap-4 text-white">
            <div class="relative">
              <img id="viewProfileAvatar" src="../Assets/StudentProfile.png" alt="Profile" class="w-28 h-28 rounded-3xl border-4 border-white/40 object-cover shadow-lg">
              <img id="viewRankBadge" src="../Assets/RankBronze.png" alt="Rookie badge" class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full bg-white/90 p-1 shadow">
            </div>
            <div>
              <p class="text-sm uppercase tracking-[0.3em] text-white/70">Pilot Profile</p>
              <h1 id="viewProfileFullName" class="text-3xl font-semibold">MOHAMAD SHAH FITRI</h1>
              <p id="viewProfileIdentity" class="text-white/80">@shah02  &middot;  Malaysia</p>
              <p id="viewProfileTagline" class="text-sm text-white/70 mt-1">Student  &middot;  Drone Programming Learner</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-3">
            <button onclick="window.location.href='../HTML/edit-profile.php'" class="px-5 py-2 rounded-full bg-white text-slate-900 font-semibold shadow-md flex items-center gap-2">
              <i class='bx bx-edit-alt'></i>Edit Profile
            </button>
            <button id="uploadCoverBtn" class="px-5 py-2 rounded-full bg-white/20 text-white backdrop-blur-lg border border-white/40 flex items-center gap-2">
              <i class='bx bx-upload'></i> Change Cover
            </button>
            <input type="file" id="coverInput" accept="image/*" class="hidden">
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">Missions Completed</p>
          <p id="viewFlightHours" class="text-3xl font-semibold text-gray-900 mt-2">142</p>
          <p class="text-xs text-emerald-500 mt-1 flex items-center gap-1"><i class='bx bx-up-arrow-alt'></i>+6 this week</p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">XP</p>
          <p id="viewMissionScore" class="text-3xl font-semibold text-gray-900 mt-2">8,420</p>
          <p class="text-xs text-gray-500 mt-1">Top 12% of pilots</p>
        </div>
      </div>

      <div>
        <div class="rounded-2xl bg-white shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Flight dossier</h3>
            <a href="../HTML/edit-profile.php" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Update Info</a>
          </div>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
            <div>
              <dt class="uppercase text-xs tracking-widest text-gray-400">Full name</dt>
              <dd id="viewDetailFullName" class="text-lg font-semibold text-gray-900">MOHAMAD SHAH FITRI</dd>
            </div>
            <div>
              <dt class="uppercase text-xs tracking-widest text-gray-400">Age</dt>
              <dd id="viewDetailAge" class="text-lg font-semibold text-gray-900">22</dd>
            </div>
            <div>
              <dt class="uppercase text-xs tracking-widest text-gray-400">Gender</dt>
              <dd id="viewDetailGender" class="text-lg font-semibold text-gray-900">Male</dd>
            </div>
            <div>
              <dt class="uppercase text-xs tracking-widest text-gray-400">School</dt>
              <dd id="viewDetailSchool" class="text-lg font-semibold text-gray-900">SMK Seri Permata</dd>
            </div>
            <div>
              <dt class="uppercase text-xs tracking-widest text-gray-400">Phone</dt>
              <dd id="viewDetailPhone" class="text-lg font-semibold text-gray-900">+6012-3456789</dd>
            </div>
            <div class="md:col-span-2">
              <dt class="uppercase text-xs tracking-widest text-gray-400">Bio</dt>
              <dd id="viewDetailBio" class="text-base leading-relaxed text-gray-700">
                I'm a passionate student learning drone programming through CodeFly. I love exploring how block-based coding can control flight dynamics and sensor automation.
              </dd>
            </div>
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

