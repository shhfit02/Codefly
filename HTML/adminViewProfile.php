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

      <!-- The cover sits behind the content, so the card grows with long names on phones. -->
      <div class="relative rounded-[32px] overflow-hidden shadow-xl border border-white/20">
        <img id="coverImage" src="../Assets/background-Profile.jpg" alt="" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-black/10"></div>
        <div class="relative min-h-[14rem] flex flex-col md:flex-row md:items-end justify-end md:justify-between gap-4 p-6">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-white min-w-0">
            <div class="relative shrink-0 self-start sm:self-auto">
              <img id="viewProfileAvatar" src="../Assets/StudentProfile.png" alt="Profile photo" class="w-24 h-24 md:w-28 md:h-28 rounded-3xl border-4 border-white/40 object-cover shadow-lg">
              <img id="viewRankChip" src="../Assets/RankBronze.png" alt="" class="absolute -bottom-2 -right-2 w-10 h-10 drop-shadow-lg">
            </div>
            <div class="min-w-0">
              <p class="text-xs uppercase tracking-[0.3em] text-white/70">Pilot profile</p>
              <h1 id="viewProfileFullName" class="text-2xl md:text-3xl font-semibold break-words">Full Name</h1>
              <p class="text-white/80 text-sm flex flex-wrap items-center gap-x-2">
                <span id="viewProfileIdentity">@username</span>
                <span aria-hidden="true">·</span>
                <span id="viewProfileRole">Student</span>
              </p>
              <p id="viewProfileTagline" class="text-sm text-white/75 mt-1"></p>
            </div>
          </div>
          <div class="flex gap-2 shrink-0">
            <a href="../HTML/manageUser.php" id="backLink" class="inline-flex items-center gap-1 rounded-xl bg-slate-900/45 border border-white/40 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900/60 backdrop-blur">
              <i class='bx bx-arrow-back'></i> Back
            </a>
            <a href="#" id="editUserLink" class="hidden inline-flex items-center gap-1 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-slate-100">
              <i class='bx bx-edit-alt'></i> Edit user
            </a>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white shadow p-5 flex items-center gap-4">
          <img id="viewRankBadge" src="../Assets/RankBronze.png" alt="" class="w-14 h-14 shrink-0">
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
          <p class="text-3xl font-semibold text-gray-900 mt-1"><span id="viewMissionsCompleted">--</span><span id="viewLessonsTotal" class="text-base font-medium text-gray-400"></span></p>
          <p id="viewModulesDone" class="text-xs text-gray-500 mt-1.5"></p>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <p class="text-sm text-gray-500">Login streak</p>
          <p class="text-3xl font-semibold text-gray-900 mt-1"><span id="viewStreak">--</span><span class="text-base font-medium text-gray-400"> days</span></p>
          <p id="viewLastLogin" class="text-xs text-gray-500 mt-1.5"></p>
        </div>
      </div>

      <div class="rounded-2xl bg-white shadow p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-4">
          <h3 class="text-lg font-semibold text-slate-900">Module progress</h3>
          <span class="text-xs text-slate-500">A lesson counts once its quiz is done</span>
        </div>
        <ul id="moduleProgress" class="space-y-4">
          <li class="text-sm text-slate-400">Loading…</li>
        </ul>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl bg-white shadow p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-3">Profile</h3>
          <dl class="grid grid-cols-1 sm:grid-cols-[8rem_1fr] sm:gap-y-2 text-sm">
            <dt class="text-slate-500">Full name</dt><dd id="viewDetailFullName" class="mb-2 sm:mb-0 font-semibold text-slate-900 break-words">--</dd>
            <dt class="text-slate-500">Age</dt><dd id="viewDetailAge" class="mb-2 sm:mb-0 font-semibold text-slate-900">--</dd>
            <dt class="text-slate-500">Gender</dt><dd id="viewDetailGender" class="mb-2 sm:mb-0 font-semibold text-slate-900">--</dd>
            <dt class="text-slate-500">School</dt><dd id="viewDetailSchool" class="mb-2 sm:mb-0 font-semibold text-slate-900 break-words">--</dd>
          </dl>
        </div>
        <div class="rounded-2xl bg-white shadow p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-3">Account</h3>
          <dl class="grid grid-cols-1 sm:grid-cols-[8rem_1fr] sm:gap-y-2 text-sm">
            <dt class="text-slate-500">Username</dt><dd id="accountUsername" class="mb-2 sm:mb-0 font-semibold text-slate-900 break-words">--</dd>
            <dt class="text-slate-500">Email</dt><dd id="accountEmail" class="mb-2 sm:mb-0 font-semibold text-slate-900 [overflow-wrap:anywhere]">--</dd>
            <dt class="text-slate-500">Member since</dt><dd id="accountMember" class="mb-2 sm:mb-0 font-semibold text-slate-900">--</dd>
            <dt class="text-slate-500">Role</dt><dd id="accountLevel" class="mb-2 sm:mb-0 font-semibold text-slate-900">--</dd>
          </dl>
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
  <script src="../JS/home.js"></script>
  <script>
    const ENDPOINT = "../PHP/viewPublicProfile.php";
    const RANK_NAMES = {
      bronze: "Rookie",
      silver: "Operator",
      gold: "Specialist",
      ace: "Elite Pilot",
      captain: "Commander",
      commander: "Grand Commander",
    };

    document.addEventListener("DOMContentLoaded", () => {
      // Go back to wherever the admin came from (leaderboard or Manage Users).
      const back = document.getElementById("backLink");
      if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) {
        back.addEventListener("click", (e) => {
          e.preventDefault();
          history.back();
        });
      }
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
        populate(payload.data || payload);
      } catch (error) {
        console.error("Profile load failed", error);
        await Swal.fire({ icon: "error", title: "Error", text: error.message || "Unable to load profile." });
        window.location.href = "../HTML/manageUser.php";
      }
    }

    function displayRankTitle(rawTitle) {
      return RANK_NAMES[(rawTitle || "").toLowerCase()] || rawTitle || "Rookie";
    }

    function populate(data) {
      const profile = data.profile || {};
      const account = data.account || {};
      const stats = data.stats || {};
      const rank = data.rank || {};
      const rankDisplay = displayRankTitle(rank.title);
      const isAdminAccount = (account.level || "").toLowerCase() === "admin";

      setText("viewProfileFullName", profile.fullName || account.username || "User");
      setText("viewProfileIdentity", `@${account.username || "user"}`);
      setText("viewProfileRole", isAdminAccount ? "Admin" : "Student");
      setText("viewProfileTagline", profile.bio || profile.school || "");
      setSrc("viewProfileAvatar", profile.avatar || "../Assets/StudentProfile.png");
      setSrc("coverImage", profile.cover || "../Assets/background-Profile.jpg");
      setSrc("viewRankChip", rank.badge || "../Assets/RankBronze.png");
      document.getElementById("viewRankChip").alt = `${rankDisplay} badge`;
      document.title = `Admin | ${account.username || "View Profile"}`;

      if (account.id) {
        const edit = document.getElementById("editUserLink");
        edit.href = `../HTML/editUser.php?id=${encodeURIComponent(account.id)}`;
        edit.classList.remove("hidden");
      }

      // Rank: "Eligible" means cleared to fly; green only when that is true.
      setText("viewRankTitle", rankDisplay);
      setSrc("viewRankBadge", rank.badge || "../Assets/RankBronze.png");
      const eligible = (rank.status || "").toLowerCase() === "eligible";
      const statusEl = document.getElementById("viewRankStatus");
      statusEl.className = `text-xs font-semibold mt-1 inline-flex items-center gap-1 ${eligible ? "text-emerald-600" : "text-amber-600"}`;
      statusEl.innerHTML = `<i class='bx ${eligible ? "bx-check-circle" : "bx-time-five"}'></i>`;
      statusEl.append(eligible ? "Cleared to fly" : "Still in training");

      // XP and progress towards the next rank.
      const xp = Number(rank.score ?? stats.xp ?? 0);
      setText("viewMissionScore", xp.toLocaleString());
      const next = stats.nextRank;
      if (next) {
        const span = Math.max(1, next.minXp - next.fromXp);
        const pct = Math.min(100, Math.max(0, ((xp - next.fromXp) / span) * 100));
        document.getElementById("viewXpBar").style.width = `${pct}%`;
        setText("viewXpNext", `${(next.minXp - xp).toLocaleString()} XP to ${displayRankTitle(next.key)}`);
      } else {
        document.getElementById("viewXpBar").style.width = "100%";
        setText("viewXpNext", "Top rank reached");
      }

      const lessonsDone = stats.lessonsCompleted ?? rank.lessons ?? 0;
      setText("viewMissionsCompleted", lessonsDone);
      setText("viewLessonsTotal", stats.lessonsTotal ? ` / ${stats.lessonsTotal}` : "");
      const modules = Array.isArray(stats.modules) ? stats.modules : [];
      setText("viewModulesDone", modules.length ? `${stats.modulesCompleted ?? 0} of ${modules.length} modules finished` : "");

      setText("viewStreak", stats.loginStreak ?? 0);
      setText("viewLastLogin", stats.lastLogin ? `Last login ${formatDate(stats.lastLogin)}` : "Never logged in");

      renderModuleProgress(modules);

      setText("viewDetailFullName", profile.fullName || "--");
      setText("viewDetailAge", profile.age ?? "--");
      setText("viewDetailGender", formatGender(profile.gender));
      setText("viewDetailSchool", profile.school || "--");

      setText("accountUsername", account.username || "--");
      setText("accountEmail", account.email || "--");
      setText("accountMember", account.memberSince ? formatDate(account.memberSince) : "--");
      setText("accountLevel", isAdminAccount ? "Admin" : "Student");
    }

    function renderModuleProgress(modules) {
      const list = document.getElementById("moduleProgress");
      list.innerHTML = "";
      if (!modules.length) {
        list.innerHTML = '<li class="text-sm text-slate-400">No modules yet.</li>';
        return;
      }
      modules.forEach((mod) => {
        const pct = mod.lessons ? Math.round((mod.completed / mod.lessons) * 100) : 0;
        const done = mod.lessons > 0 && mod.completed === mod.lessons;
        const li = document.createElement("li");
        const head = document.createElement("div");
        head.className = "flex items-start justify-between gap-3 text-sm";
        const title = document.createElement("span");
        title.className = "font-semibold text-slate-800 min-w-0";
        title.textContent = mod.title || "Module";
        const count = document.createElement("span");
        count.className = `shrink-0 ${done ? "text-emerald-600 font-semibold" : "text-slate-500"}`;
        count.textContent = done ? "Completed" : `${mod.completed} / ${mod.lessons} lessons`;
        head.append(title, count);
        const bar = document.createElement("div");
        bar.className = "mt-2 h-2 rounded-full bg-slate-100 overflow-hidden";
        bar.setAttribute("role", "progressbar");
        bar.setAttribute("aria-valuenow", String(pct));
        bar.setAttribute("aria-valuemin", "0");
        bar.setAttribute("aria-valuemax", "100");
        bar.setAttribute("aria-label", `${mod.title}: ${pct}%`);
        const fill = document.createElement("div");
        fill.className = `h-full rounded-full ${done ? "bg-emerald-500" : "bg-gradient-to-r from-sky-400 to-indigo-500"}`;
        fill.style.width = `${pct}%`;
        bar.appendChild(fill);
        li.append(head, bar);
        list.appendChild(li);
      });
    }

    function formatDate(value) {
      const date = new Date(`${value}T00:00:00`);
      if (Number.isNaN(date.getTime())) return value;
      return date.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
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
          return "Prefer not to say";
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
