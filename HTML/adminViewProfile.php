<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Admin | View Profile</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" data-page="view-profile">

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

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
              <span id="viewRankChip" class="absolute -bottom-2 -right-2 bg-white/80 text-slate-800 text-xs font-semibold px-2 py-1 rounded-full">Rookie Tier</span>
            </div>
            <div>
              <p class="text-sm uppercase tracking-[0.3em] text-white/70">Pilot Profile</p>
              <h1 id="viewProfileFullName" class="text-3xl font-semibold">Full Name</h1>
              <p id="viewProfileIdentity" class="text-white/80">@username</p>
              <p id="viewProfileTagline" class="text-sm text-white/70 mt-1">Bio / school</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-nowrap gap-4 overflow-x-auto no-scrollbar pb-2">
        <div class="rounded-2xl bg-white shadow p-5 flex items-center gap-4 min-w-[220px] flex-1">
          <div class="flex-shrink-0">
            <img id="viewRankBadge" src="../Assets/RankBronze.png" alt="Rank badge" class="w-14 h-14">
          </div>
          <div>
            <p class="text-sm text-gray-500">Rank</p>
            <p id="viewRankTitle" class="text-2xl font-semibold text-gray-900 leading-tight">Rookie</p>
            <p id="viewRankStatus" class="text-xs font-semibold text-emerald-600">Not Eligible</p>
          </div>
        </div>
        <div class="rounded-2xl bg-white shadow p-5 min-w-[200px] flex-1">
          <p class="text-sm text-gray-500">Streaks</p>
          <p id="viewFlightHours" class="text-3xl font-semibold text-gray-900 mt-2">--</p>
          <p class="text-xs text-emerald-500 mt-1 flex items-center gap-1"><i class='bx bx-up-arrow-alt'></i></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5 min-w-[200px] flex-1">
          <p class="text-sm text-gray-500">Lesson Xp</p>
          <p id="viewMissionScore" class="text-3xl font-semibold text-gray-900 mt-2">--</p>
          <p class="text-xs text-gray-500 mt-1"></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5 min-w-[200px] flex-1">
          <p class="text-sm text-gray-500">Lessons Completed</p>
          <p id="viewMissionsCompleted" class="text-3xl font-semibold text-gray-900 mt-2">--</p>
          <p class="text-xs text-gray-500 mt-1"></p>
        </div>
      </div>

      <div class="rounded-2xl bg-white shadow p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-slate-900">Statistics</h3>
          <span class="text-xs text-slate-500">Live from profile data</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/60">
            <p class="text-sm text-slate-500">Modules</p>
            <p id="statModules" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
            <p class="text-xs text-slate-500">Completed modules</p>
          </div>
          <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/60">
            <p class="text-sm text-slate-500">Lessons</p>
            <p id="statMissions" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
            <p class="text-xs text-slate-500">Completed lessons</p>
          </div>
          <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/60">
            <p class="text-sm text-slate-500">Rank</p>
            <p id="statRank" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
            <p class="text-xs text-slate-500">Current tier</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl bg-white shadow p-5 space-y-2">
          <h3 class="text-lg font-semibold text-slate-900">Profile</h3>
          <p class="text-sm text-slate-600">Full Name: <span id="viewDetailFullName" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">Age: <span id="viewDetailAge" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">Gender: <span id="viewDetailGender" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">School: <span id="viewDetailSchool" class="font-semibold text-slate-900">--</span></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5 space-y-2">
          <h3 class="text-lg font-semibold text-slate-900">Account</h3>
          <p class="text-sm text-slate-600">Username: <span id="accountUsername" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">Email: <span id="accountEmail" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">Member Since: <span id="accountMember" class="font-semibold text-slate-900">--</span></p>
          <p class="text-sm text-slate-600">Level: <span id="accountLevel" class="font-semibold text-slate-900">--</span></p>
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
  <script src="../JS/home.js"></script>
  <script>
    let currentProfileId = "";
    const ENDPOINT = "../PHP/viewPublicProfile.php";

    document.addEventListener("DOMContentLoaded", () => {
      loadProfile();
    });

    function getIdentity() {
      const params = new URLSearchParams(window.location.search);
      return {
        id: params.get("id"),
        user: params.get("user"),
      };
    }

    async function loadProfile() {
      const { id, user } = getIdentity();
      if (!id && !user) {
        await Swal.fire({ icon: "error", title: "Missing profile identifier." });
        window.location.href = "../HTML/manageUser.php";
        return;
      }
      const query = id
        ? `id=${encodeURIComponent(id)}`
        : `user=${encodeURIComponent(user)}`;
      try {
        const res = await fetch(`${ENDPOINT}?${query}`, { credentials: "same-origin", cache: "no-store" });
        const payload = await res.json();
        if (payload?.status !== "success") throw new Error(payload?.message || "Unable to load profile.");
        const data = payload.data || payload;
        currentProfileId = data?.account?.id || id || "";
        populate(data);
      } catch (error) {
        console.error("Profile load failed", error);
        await Swal.fire({ icon: "error", title: "Error", text: error.message || "Unable to load profile." });
        window.location.href = "../HTML/manageUser.php";
      }
    }

    function displayRankTitle(rawTitle) {
      const normalized = (rawTitle || "").toLowerCase();
      const map = {
        bronze: "Rookie",
        silver: "Operator",
        gold: "Specialist",
        ace: "Elite Pilot",
        captain: "Commander",
        commander: "Grand Commander",
      };
      return map[normalized] || rawTitle || "Rookie";
    }

    function populate(data) {
      const profile = data.profile || {};
      const account = data.account || {};
      const stats = data.stats || {};
      const rank = data.rank || {};
      const rankDisplay = displayRankTitle(rank.title);

      setText("viewProfileFullName", profile.fullName || account.username || "User");
      setText("viewProfileIdentity", `@${account.username || "user"}`);
      setText("viewProfileTagline", profile.bio || profile.school || "No bio provided.");
      setSrc("viewProfileAvatar", profile.avatar || "../Assets/StudentProfile.png");
      setSrc("coverImage", profile.cover || "../Assets/background-Profile.jpg");
      setText("viewRankChip", rankDisplay ? `${rankDisplay} Tier` : "Rookie Tier");
      setText("viewRankTitle", rankDisplay);
      setText("viewRankStatus", rank.status || "Not Eligible");
      setSrc("viewRankBadge", rank.badge || "../Assets/RankBronze.png");

      setText("viewFlightHours", stats.flightHours ?? 0);
      // XP comes from rank.score in the database.
      setText("viewMissionScore", rank.score ?? stats.lessonScore ?? 0);
      setText("viewMissionsCompleted", rank.lessons ?? 0);

      const lessonsCompleted = rank.lessons ?? 0;
      const modulesCompleted =
        stats.modules ?? stats.moduleCount ?? stats.completedModules ?? lessonsCompleted;
      setText("statModules", modulesCompleted);
      setText("statMissions", lessonsCompleted);
      setText("statRank", rankDisplay);

      setText("viewDetailFullName", profile.fullName || "--");
      setText("viewDetailAge", profile.age ?? "--");
      setText("viewDetailGender", formatGender(profile.gender));
      setText("viewDetailSchool", profile.school || "--");

      setText("accountUsername", account.username || "--");
      setText("accountEmail", account.email || "--");
      setText("accountMember", account.memberSince || "--");
      setText("accountLevel", account.level || "--");
    }

    function setText(id, value) {
      const el = document.getElementById(id);
      if (el) el.textContent = value ?? "";
    }

    function setSrc(id, value) {
      const el = document.getElementById(id);
      if (el && value) el.src = value;
    }

    function formatGender(value) {
      switch ((value || "").toLowerCase()) {
        case "male":
          return "Male";
        case "female":
          return "Female";
        case "prefer-not":
          return "Prefer not";
        case "other":
          return "Other";
        default:
          return "--";
      }
    }
  </script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
