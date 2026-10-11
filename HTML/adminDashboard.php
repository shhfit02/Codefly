<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
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
    <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>
    <!-- MAIN SECTION -->
    <section class="home min-h-screen">
      <div class="p-6 flex gap-6">
        <!-- LEFT COLUMN -->
        <div class="flex-1 flex flex-col gap-6">
          <div class="rounded-3xl shadow-lg p-6 relative overflow-visible" style="background: linear-gradient(130deg, #0ea5e9, #6366f1); color: white;">
            <div class="relative z-10 flex flex-col xl:flex-row gap-8">
              <div class="flex-1 flex flex-col gap-5">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                  <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-white/70">Command Center</p>
                    <h1 id="hero-welcome" class="text-3xl font-bold text-white">Welcome back, Admin.</h1>
                    <p class="text-white/80 max-w-2xl">Monitor users and platform health in real time.</p>
                  </div>
                </div>
                <div class="relative">
                  <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-white/70 text-xl"></i>
                  <input
                    type="text"
                    placeholder="Search modules, lessons & pages..."
                    class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white/15 border border-white/30 text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/60 shadow-sm"
                    id="homeSearchInput"
                    autocomplete="off"
                  />
                </div>
                
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100">
              <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Total Users</p>
              <div class="mt-3">
                <h3 id="stat-total-users" class="text-3xl font-semibold text-slate-900 cf-skeleton" aria-live="polite">--</h3>
              </div>
              <p class="text-sm text-slate-500 mt-2">All registered accounts.</p>
            </div>
            <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100">
              <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Active Today</p>
              <div class="mt-3">
                <h3 id="stat-active-today" class="text-3xl font-semibold text-slate-900 cf-skeleton" aria-live="polite">--</h3>
              </div>
              <p class="text-sm text-slate-500 mt-2">Distinct logins recorded today.</p>
            </div>
            <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100">
              <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Total Modules</p>
              <div class="mt-3">
                <h3 id="stat-new-month" class="text-3xl font-semibold text-slate-900 cf-skeleton" aria-live="polite">--</h3>
              </div>
              <p class="text-sm text-slate-500 mt-2">Published modules available to learners.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100 xl:col-span-2">
              <div class="flex flex-col gap-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Growth</p>
                    <h3 class="text-xl font-semibold text-slate-900">Latest Signups</h3>
                    <p class="text-sm text-slate-500">Live from your database (last 30 days)</p>
                  </div>
                  <a
                    href="../HTML/manageUser.php"
                    class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors"
                  >Manage Users <i class='bx bx-right-arrow-alt'></i></a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Last 7 Days</p>
                    <div class="flex items-center gap-3 mt-1">
                      <p id="signup-week" class="text-3xl font-semibold text-slate-900 cf-skeleton" aria-live="polite">--</p>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Last 7 days</p>
                  </div>
                  <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">This Month</p>
                    <div class="flex items-center gap-3 mt-1">
                      <p id="signup-month" class="text-3xl font-semibold text-slate-900 cf-skeleton" aria-live="polite">--</p>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Last 30 days</p>
                  </div>
                </div>

                <div class="rounded-xl border border-slate-100 bg-gradient-to-r from-sky-50 to-indigo-50 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-slate-700">Signup Trend (last 30 days)</p>
                    <p id="signup-growth-empty" class="text-xs text-slate-500 hidden">No signup data yet.</p>
                  </div>
                  <div class="h-64">
                    <canvas id="signup-growth-chart" aria-label="Signup growth chart" role="img"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Ranks</p>
                  <h3 class="text-xl font-semibold text-slate-900">Distribution</h3>
                </div>
                <a href="adminLeaderboard.php" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors">
                  View Ladder <i class='bx bx-right-arrow-alt'></i>
                </a>
              </div>
              <div class="flex flex-col gap-4">
                <div class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white" style="height: 320px; min-height: 320px;">
                  <div id="rank-slider" class="relative">
                    <div class="rank-slide block" data-slide="0" role="tabpanel" aria-label="Rank distribution chart">
                      <div class="flex flex-col gap-6 p-4">
                        <div class="relative w-64 h-64 mx-auto">
                          <svg id="rank-donut" class="w-full h-full -rotate-90" viewBox="0 0 120 120" aria-label="Rank distribution chart" role="img"></svg>
                          <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Total</p>
                            <p id="rank-total" class="text-3xl font-bold text-slate-800 cf-skeleton">--</p>
                          </div>
                          <div id="rank-tooltip" class="hidden absolute z-10 px-3 py-2 text-xs font-semibold text-slate-900 bg-white border border-slate-200 rounded-lg shadow-lg pointer-events-none"></div>
                        </div>
                      </div>
                    </div>
                    <div class="rank-slide hidden" data-slide="1" role="tabpanel" aria-label="Rank summary">
                      <div class="flex flex-col gap-4 p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                          <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Rank Tiers</p>
                            <p id="rank-tiers" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                          </div>
                          <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Top 3 Share</p>
                            <p id="rank-top3" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                          </div>
                          <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Most Common</p>
                            <p id="rank-most-common" class="text-base font-semibold text-slate-900 mt-1 truncate">--</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-center gap-2" role="tablist" aria-label="Rank panel slides">
                  <button type="button" class="rank-dot w-2 h-2 rounded-full bg-slate-300" data-target="0" role="tab" aria-selected="true" aria-label="Rank distribution chart"></button>
                  <button type="button" class="rank-dot w-2 h-2 rounded-full bg-slate-200" data-target="1" role="tab" aria-selected="false" aria-label="Rank summary"></button>
                </div>

                <p id="rankEmpty" class="text-sm text-slate-500">Loading...</p>
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100">
              <div class="flex items-center justify-between mb-5 gap-4">
                <div>
                  <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Learning</p>
                  <h3 class="text-xl font-semibold text-slate-900">Module Progress</h3>
                </div>
                <a href="../HTML/AddModule.php" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors">
                  Manage Modules <i class='bx bx-right-arrow-alt'></i>
                </a>
              </div>
            <div class="space-y-5">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Total Modules</p>
                  <p id="module-stat-total" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                  <p class="text-xs text-slate-500 mt-1">Published</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Active Learners</p>
                  <p id="module-stat-active" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                  <p class="text-xs text-slate-500 mt-1">Total users</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Avg Completion</p>
                  <p id="module-stat-avg" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                  <p class="text-xs text-slate-500 mt-1">Across modules</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Total Completions</p>
                  <p id="module-stat-completed" class="text-2xl font-semibold text-slate-900 mt-1">--</p>
                  <p class="text-xs text-slate-500 mt-1">Learners finished</p>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-100 bg-white p-4">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-sm font-semibold text-slate-800">Completion by module</p>
                  <p id="module-chart-empty" class="text-xs text-slate-500 hidden">No module data yet.</p>
                </div>
                <div class="h-64">
                  <canvas id="module-completion-chart" aria-label="Module completion chart" role="img"></canvas>
                </div>
              </div>

              <div id="module-progress-cards" class="flex flex-col gap-3">
                <div class="text-sm text-slate-500">Loading module data...</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <script src="../JS/adminDashboard.js"></script>
    <script src="../JS/home.js"></script>
    <script src="../JS/popup.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
