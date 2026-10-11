<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="../Assets/logo4.png">
    <title>Codefly Dashboard</title>
    <link rel="stylesheet" href="../CSS/tailwind.build.css" />
    <link rel="stylesheet" href="../CSS/home.css" />
    <link rel="stylesheet" href="../CSS/popup.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <body class="bg-gray-100">
    <div id="toastBox"></div>
    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/student_sidebar.php'; ?>

    <!-- MAIN SECTION -->
    <section class="home min-h-screen">
      <div class="p-6 flex flex-col md:flex-row gap-6 items-start">
        <!-- MAIN COLUMN -->
        <div class="flex-1 min-w-0 flex flex-col gap-6 order-1 md:order-1 w-full">
          <div class="rounded-3xl bg-white shadow-lg p-6 relative overflow-visible">
            <div class="absolute inset-0 bg-gradient-to-r from-sky-50 via-white to-transparent"></div>
            <div class="relative z-10 flex flex-col xl:flex-row gap-8">
              <div class="flex-1 min-w-0 flex flex-col gap-5">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                  <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Lesson Briefing</p>
                    <h1 id="hero-welcome" class="text-3xl font-bold text-slate-900">Welcome back, Pilot!</h1>
                    <p class="text-slate-500 max-w-xl">Ready for today's mission? Your next lesson is waiting below.</p>
                  </div>
                  <div class="flex items-center gap-3 lg:ml-auto">
                    <div class="relative">
                      <button id="notif-btn" class="relative" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-bell text-2xl text-gray-600 hover:text-blue-500"></i>
                        <span id="notif-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 hidden"></span>
                      </button>
                      <div id="notif-panel" class="hidden absolute right-0 mt-3 w-[28rem] max-w-[calc(100vw-2rem)] bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-2xl p-3 z-20 flex flex-col ring-1 ring-slate-100 overflow-hidden">
                        <ul id="notif-list" class="divide-y divide-slate-100 text-sm text-gray-700 pr-1 pb-1 pt-0 space-y-0 overflow-y-auto max-h-64">
                          <li class="text-gray-400 text-xs text-center py-2">No notifications yet.</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Next lesson to continue (filled by home.js from the user's progress) -->
                <a id="continue-card" href="../HTML/module.php" class="hidden group flex items-center gap-4 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 hover:border-sky-300 transition-colors">
                  <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-500 text-white text-2xl"><i class="bx bx-play"></i></span>
                  <span class="min-w-0 flex-1">
                    <span id="continue-eyebrow" class="block text-xs font-semibold uppercase tracking-[0.15em] text-sky-600">Continue learning</span>
                    <span id="continue-title" class="block font-semibold text-slate-900 sm:truncate">--</span>
                    <span id="continue-sub" class="block text-sm text-slate-500 sm:truncate">--</span>
                  </span>
                  <span id="continue-cta" class="hidden sm:inline-flex items-center gap-1 rounded-full bg-sky-500 px-4 py-2 text-sm font-semibold text-white group-hover:bg-sky-600">Resume <i class="bx bx-right-arrow-alt"></i></span>
                </a>
                <div class="relative" id="homeSearchBox">
                  <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                  <input
                    type="text"
                    placeholder="Search lessons or modules..."
                    class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-sky-200 shadow-sm"
                    id="homeSearchInput"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="homeSearchResults"
                  />
                  <ul id="homeSearchResults" role="listbox" class="hidden absolute left-0 right-0 top-full z-30 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl"></ul>
                </div>
                <div class="flex flex-wrap gap-3">
                  <a href="../HTML/workspace.php" id="home-workspace-btn" class="flex items-center gap-2 px-4 py-2 rounded-full bg-purple-100 text-purple-700 text-sm font-semibold"><i class="bx bx-code-alt" id="home-workspace-icon"></i><span id="home-workspace-label">Open Workspace</span></a>
                  <a href="../HTML/leaderboard.php" class="flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 text-amber-700 text-sm font-semibold"><i class="bx bx-trophy"></i>View Ranking</a>
                </div>
              </div>
              <div class="w-full xl:w-72 shrink-0">
                <div id="weather-card" class="relative rounded-[32px] text-white p-5 min-h-[230px] overflow-hidden shadow-lg" style="background: linear-gradient(155deg, #0f172a 0%, #111827 40%, #0ea5e9 120%);">
                  <div class="absolute inset-0 opacity-40" style="background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.25), transparent 55%);"></div>
                  <div class="relative z-10 flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                      <div>
                        <p class="text-[0.65rem] uppercase tracking-[0.4em] text-white/60">Current</p>
                        <p id="weather-location" class="text-sm font-semibold text-white">Detecting...</p>
                        <p id="weather-date" class="text-xs text-white/60 mt-1">--</p>
                      </div>
                      <div class="text-right">
                        <i id="weather-icon" class="bx bx-cloud text-4xl text-white"></i>
                        <p class="text-[0.65rem] text-white/60 mt-1">Updated now</p>
                      </div>
                    </div>
                    <div class="flex items-end gap-3">
                      <p id="weather-temp" class="text-5xl leading-none font-semibold text-white">--&deg;C</p>
                      <div class="flex flex-col">
                        <p id="weather-desc" class="text-sm text-white/80 capitalize">Loading...</p>
                        <p id="weather-feels" class="text-xs text-white/60">Feels like --</p>
                      </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                      <div class="bg-white/10 rounded-2xl px-3 py-3 flex flex-col gap-1">
                        <span class="text-white/60">Wind</span>
                        <span id="weather-wind" class="flex items-center gap-2 text-white">
                          <i id="wind-arrow" class="bx bx-up-arrow-alt text-lg"></i>
                          <span id="wind-speed">-- km/h</span>
                        </span>
                      </div>
                      <div class="bg-white/10 rounded-2xl px-3 py-3 flex flex-col gap-1">
                        <span class="text-white/60">Humidity</span>
                        <span id="weather-humidity" class="text-white text-sm font-semibold">--%</span>
                      </div>
                    </div>
                  </div>
                  <img src="../Assets/satellite.gif" alt="satellite" class="absolute bottom-2 right-2 w-24 opacity-50 pointer-events-none mix-blend-screen drop-shadow-lg" />
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="home-module-grid">
          </div>

        </div>

        <!-- PROFILE COLUMN -->
        <div class="w-full md:w-64 lg:w-72 flex-shrink-0 lg:sticky lg:top-6 self-start lg:h-[calc(100vh-3rem)] lg:max-h-[calc(100vh-3rem)] order-2 md:order-2 md:ml-auto">
          <div
            class="rounded-2xl shadow-lg h-full bg-cover bg-center relative overflow-y-auto profile-card"
            style="background-image: url('../Assets/background-Profile.jpg')"
          >

            <div class="profile-card__menu">
              <button id="profile-menu-btn" class="profile-card__menu-btn">
                <i class="bx bx-dots-vertical-rounded text-xl"></i>
              </button>
              <div
                id="profile-menu"
                class="profile-card__dropdown hidden absolute right-0 mt-2 w-44 bg-white text-gray-700 rounded-xl shadow-lg z-20 overflow-hidden"
              >
                <ul class="py-2 text-sm">
                  <li>
                    <a
                      href="../HTML/view-profile.php"
                      class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100"
                      ><i class="bx bx-user-circle text-lg"></i>View Profile</a
                    >
                  </li>
                  <li>
                    <a
                      href="../HTML/edit-profile.php"
                      class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100"
                      ><i class="bx bx-edit-alt text-lg"></i>Edit Profile</a
                    >
                  </li>
                </ul>
              </div>
            </div>

            <div class="profile-card__content">
              <div class="profile-card__status">
                <span class="profile-card__status-dot"></span>
                Lesson Ready
              </div>

              <div class="profile-card__hero">
                <img
                  id="profile-avatar"
                  src="../Assets/StudentProfile.png"
                  alt="Profile"
                  class="profile-card__avatar"
                />
                <div class="profile-card__identity">
                  <h2 id="profile-name">Pilot Name</h2>
                  <p id="profile-handle">@pilot</p>
                </div>
              </div>

              <div class="profile-card__stats">
                <div class="profile-card__stat">
                  <p class="profile-card__stat-label">Lessons</p>
                  <p class="profile-card__stat-value" data-profile-stat="lessons">0</p>
                </div>
                <div class="profile-card__stat">
                  <p class="profile-card__stat-label">Streak</p>
                  <p class="profile-card__stat-value" data-profile-stat="streak">0d</p>
                </div>
                <div class="profile-card__stat">
                  <p class="profile-card__stat-label">XP</p>
                  <p class="profile-card__stat-value" data-profile-stat="xp">0</p>
                </div>
              </div>
              <!-- XP progress to the next rank (filled by home.js) -->
              <div class="profile-card__xp hidden" id="profile-xp">
                <div class="profile-card__xp-row">
                  <span id="profile-xp-label">Next rank</span>
                  <span id="profile-xp-count">0 / 0 XP</span>
                </div>
                <div class="profile-card__xp-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="profile-xp-bar">
                  <div class="profile-card__xp-fill" id="profile-xp-fill"></div>
                </div>
              </div>

              <div
                class="profile-card__rank"
                onclick="location.href='../HTML/leaderboard.php'"
              >
                <div class="profile-card__rank-badge">
                  <img
                    id="profile-rank-badge"
                    src="../Assets/RankBronze.png"
                    alt="Ranking badge"
                  />
                </div>
                <div class="profile-card__rank-copy">
                  <p class="profile-card__rank-label">Current Rank</p>
                  <h4 id="profile-rank-title">Rookie</h4>
                  <p id="profile-rank-note">Not eligible to fly</p>
                </div>
                <i class="bx bx-chevron-right text-2xl profile-card__rank-arrow"></i>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <script src="../JS/home.js"></script>
    <script src="../JS/popup.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>




