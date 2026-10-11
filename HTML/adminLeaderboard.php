<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Codefly Leaderboard</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css" />
  <link rel="stylesheet" href="../CSS/home.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

  <style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    .fade-bottom {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 3rem;
      background: linear-gradient(to top, rgba(255,255,255,1), rgba(255,255,255,0));
      pointer-events: none;
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      border: 0;
      white-space: nowrap;
    }

    .leaderboard-hero {
      background: linear-gradient(120deg, #0ea5e9, #6366f1);
      border-radius: 24px;
      color: #fff;
      padding: 28px;
      box-shadow: 0 25px 45px rgba(14, 165, 233, 0.25);
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid rgba(255, 255, 255, 0.4);
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 0.85rem;
      backdrop-filter: blur(6px);
    }

    .stat-card {
      background: #fff;
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
      border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .stat-card span {
      font-size: 1.8rem;
      font-weight: 700;
      color: #111827;
    }

    .stat-card small {
      color: #6b7280;
    }

    .top-pilots-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 1rem;
    }

    @media (min-width: 1024px) {
      .top-pilots-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    .top-pilot-card {
      position: relative;
      border-radius: 26px;
      padding: 22px 24px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      background: #ffffff;
      min-height: 120px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .top-pilot-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 22px 45px rgba(15, 23, 42, 0.12);
    }

    .top-pilot-card.primary {
      background: radial-gradient(circle at top left, #fef3c7, #fbbf24);
      color: #1f2937;
      border: none;
      box-shadow: 0 25px 55px rgba(251, 191, 36, 0.35);
    }

    .top-pilot-card__info {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .top-pilot-card__position {
      font-size: 2.5rem;
      font-weight: 700;
      color: #cbd5f5;
      line-height: 1;
    }

    .top-pilot-card.primary .top-pilot-card__position {
      color: #ffffff;
    }

    .top-pilot-card__details {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .top-pilot-card__name {
      font-weight: 600;
      font-size: 1.05rem;
      color: #0f172a;
    }

    .top-pilot-card.primary .top-pilot-card__name {
      color: #1f2937;
    }

    .top-pilot-card__score {
        font-size: 0.9rem;
        color: #475569;
    }

    .top-pilot-card__rank {
        font-size: 0.85rem;
        color: #475569;
        margin-top: 2px;
    }

    .top-pilot-card.primary .top-pilot-card__score {
        color: rgba(15, 23, 42, 0.85);
    }

    .top-pilot-card.primary .top-pilot-card__rank {
        color: rgba(15, 23, 42, 0.8);
    }

    .top-pilot-card__score-value {
        font-weight: 600;
        color: #111827;
    }

    .top-pilot-card.primary .top-pilot-card__score-value {
      color: #1f2937;
    }

    .top-pilot-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      padding: 0;
      border-radius: 999px;
      background: transparent;
      box-shadow: none;
      position: relative;
      isolation: isolate;
    }

    .top-pilot-badge__icon {
      width: 48px;
      height: 48px;
      border-radius: 999px;
      padding: 0;
      background: transparent;
      box-shadow: none;
      filter: drop-shadow(0 6px 12px rgba(15, 23, 42, 0.25));
    }

    .top-pilot-badge::after {
      content: none;
    }

    .filter-btn {
      transition: transform 0.2s ease, background 0.2s ease;
    }

    .filter-btn.active {
      background: #2563eb;
      color: #fff;
      border-color: transparent;
      box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
    }

    .status-chip {
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .status-chip.eligible {
      background: rgba(16, 185, 129, 0.15);
      color: #047857;
    }

    .status-chip.pending {
        background: rgba(239, 68, 68, 0.15);
        color: #b91c1c;
    }

    /* Rules modal */
    .rules-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 120;
      backdrop-filter: blur(3px);
    }
    .rules-backdrop.active {
      display: flex;
    }
    .rules-card {
      width: min(520px, calc(100% - 32px));
      background: rgba(255, 255, 255, 0.75);
      border-radius: 24px;
      padding: 24px;
      box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25);
      display: grid;
      gap: 16px;
      background-image: url('../Assets/rulesBg.jpg');
      background-size: cover;
      background-position: center;
      backdrop-filter: blur(5px);
    }
    .rules-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .rules-title {
      font-size: 20px;
      font-weight: 700;
      color: #0f172a;
      margin: 0;
    }
    .rules-close {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      border: none;
      background: #f1f5f9;
      display: grid;
      place-items: center;
      color: #475569;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .rules-close:hover {
      background: #e2e8f0;
      color: #0f172a;
    }
    .rules-list {
      display: grid;
      gap: 10px;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .rules-list li {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 10px;
      align-items: start;
      padding: 10px 12px;
      border-radius: 14px;
      background: #f8fafc;
    }
    .rules-list li i {
      color: #6366f1;
      font-size: 18px;
      margin-top: 2px;
    }
    .rules-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      flex-wrap: wrap;
    }
    .rules-btn {
      border: none;
      border-radius: 12px;
      padding: 10px 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .rules-btn.secondary {
      background: #f1f5f9;
      color: #475569;
    }
    .rules-btn.secondary:hover {
      background: #e2e8f0;
      color: #0f172a;
    }
    .rules-btn.primary {
      background: linear-gradient(120deg, #5c7cfa, #7c3aed);
      color: #fff;
      box-shadow: 0 10px 30px rgba(92, 124, 250, 0.35);
    }
    .rules-btn.primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 34px rgba(124, 58, 237, 0.35);
    }

    .rank-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
      height: 56px;
      border-radius: 999px;
      background: transparent;
      padding: 0;
      position: relative;
      isolation: isolate;
    }

    .rank-pill::after {
      content: none;
    }

    .ladder-pill {
      border: 1px solid rgba(255, 255, 255, 0.25);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .ladder-pill.ladder-active {
      border-color: #fde047;
      background: rgba(255, 255, 255, 0.35);
      box-shadow: 0 12px 30px rgba(251, 191, 36, 0.35);
      transform: translateX(8px) scale(1.03);
    }

    #leaderboard-body tr td {
      border-bottom: 1px solid #e2e8f0;
    }

    #leaderboard-body tr:last-child td {
      border-bottom: none;
    }
  </style>
</head>

<body class="bg-gray-100">

  <!-- Sidebar -->
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

  <!-- MAIN SECTION -->
  <section class="home min-h-screen">
    <div class="p-6 flex flex-col lg:flex-row gap-6 items-start">

      <!-- LEFT COLUMN -->
      <div class="flex-1 w-full min-w-0 flex flex-col gap-6">

        <div class="leaderboard-hero">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <h1 class="text-3xl md:text-4xl font-semibold mt-4 leading-tight">
                Codefly Pilot Leaderboard
              </h1>
              <p class="text-white/80 mt-3 max-w-2xl">
                Track real-time performance, filter by rank tiers and celebrate the pilots who are pushing the Codefly platform forward.
              </p>
            </div>
            <div class="bg-white/20 backdrop-blur-md rounded-2xl px-6 py-5 text-center min-w-[220px]">
              <p class="text-xs uppercase tracking-[0.2em] text-white/70">Lesson goal</p>
                <p class="text-3xl font-bold">5,000 pts</p>
                <p class="text-sm text-white/80 mt-2">Reach Grand Commander tier at 5,000 XP to unlock pro automation tools.</p>
              <div class="flex flex-col gap-3 mt-4">
                <button type="button" id="viewRulesBtn" class="inline-flex items-center justify-center gap-2 bg-white text-slate-900 font-semibold px-4 py-2 rounded-xl shadow-lg hover:-translate-y-0.5 transition">
                View Rules
                <i class='bx bx-right-arrow-alt text-lg'></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl bg-white shadow-lg p-5">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Top Pilots</h2>
              <p class="text-sm text-gray-500">Performance snapshot - refreshed every hour</p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" id="refreshLeaderboard" class="refresh-btn text-sm font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1 border border-blue-100 rounded-lg px-3 py-1.5">
                Refresh
                <span class="refresh-spinner" aria-hidden="true"></span>
              </button>
            </div>
          </div>
          <div id="topPilots" class="top-pilots-grid"></div>
        </div>

        <div class="rounded-2xl bg-white shadow-lg p-6">
          <div class="flex flex-col gap-4 mb-6">
            <div class="relative">
              <label for="searchInput" class="sr-only">Search pilots</label>
              <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
              <input
                id="searchInput"
                type="search"
                placeholder="Search pilot handle..."
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200 shadow-sm bg-white"
              />
            </div>
            <div id="rankFilters" class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
              <button data-rank="all" class="filter-btn active px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="true">All</button>
              <button data-rank="bronze" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Rookie</button>
              <button data-rank="silver" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Operator</button>
              <button data-rank="gold" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Specialist</button>
              <button data-rank="ace" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Elite Pilot</button>
              <button data-rank="captain" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Commander</button>
              <button data-rank="commander" class="filter-btn px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-sm font-medium text-gray-600 flex-shrink-0" aria-pressed="false">Grand Commander</button>
            </div>
          </div>

          <div class="border border-gray-100 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-50 sticky top-0 z-10">
                  <tr>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">#</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Avatar</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Pilot</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Total XP</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Rank</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Lessons</th>
                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-xs">Status Flight</th>
                  </tr>
                </thead>
                <tbody id="leaderboard-body"></tbody>
              </table>
            </div>
            <div id="emptyState" class="hidden text-center text-gray-500 text-sm py-6">
              No pilots match your filter right now. Try a different rank or search term.
            </div>
            <div id="lbPagination" class="hidden px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
              <p id="lbPaginationInfo" class="text-sm text-slate-500"></p>
              <div id="lbPaginationControls" class="flex items-center gap-1"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="w-full lg:w-80 flex-shrink-0 lg:sticky lg:top-6 self-start">
        <div
          class="rank-panel rounded-2xl shadow-lg h-[calc(100vh-4rem)] relative overflow-hidden text-white"
          style="background-image: url('../Assets/background-rank.jpg'); background-size: cover; background-position: center;"
        >
          <div class="rank-panel__overlay"></div>
          <div class="rank-panel__content">
            <div class="rank-panel__header">
              <span class="rank-panel__eyebrow">Lesson tracker</span>
              <h2 class="rank-panel__title">Pilot Rank</h2>
              <p class="rank-panel__subtitle">Grand Commander unlocks at 5,000 XP — track your progress.</p>
            </div>

            <div class="rank-panel__ladder">
              <div class="rank-panel__ladder-head">
                <h4>Rank Ladder</h4>
                <span class="rank-panel__ladder-meta">6 stages</span>
              </div>
              <div class="rank-ladder no-scrollbar" id="rankLadder">
                <div class="ladder-pill" data-rank="bronze">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankBronze.png" alt="Rookie badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Rookie</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 01</span>
                </div>
                <div class="ladder-pill" data-rank="silver">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankSilver.png" alt="Operator badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Operator</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 02</span>
                </div>
                <div class="ladder-pill" data-rank="gold">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankGold.png" alt="Specialist badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Specialist</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 03</span>
                </div>
                <div class="ladder-pill" data-rank="ace">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankAce.png" alt="Elite Pilot badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Elite Pilot</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 04</span>
                </div>
                <div class="ladder-pill" data-rank="captain">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankCaptain.png" alt="Commander badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Commander</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 05</span>
                </div>
                <div class="ladder-pill" data-rank="commander">
                  <div class="ladder-pill__media">
                    <img src="../Assets/RankCommander.png" alt="Grand Commander badge" />
                  </div>
                  <div class="ladder-pill__body">
                    <p class="ladder-pill__name">Grand Commander</p>
                  </div>
                  <span class="ladder-pill__level">Lv. 06</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <script>

    function displayRankTitle(rawTitle) {
      const normalized = (rawTitle || '').toLowerCase();
      const map = {
        bronze: 'Rookie',
        silver: 'Operator',
        gold: 'Specialist',
        ace: 'Elite Pilot',
        captain: 'Commander',
        commander: 'Grand Commander',
      };
      return map[normalized] || rawTitle || 'Rookie';
    }

    function highlightRankLadder(rankTitle = '') {
      const ladder = document.querySelectorAll('#rankLadder .ladder-pill');
      if (!ladder.length) {
        return;
      }
      const normalized = rankTitle.toLowerCase();
      ladder.forEach((pill) => {
        const isActive = pill.dataset.rank === normalized;
        pill.classList.toggle('ladder-active', isActive);
      });
    }

    async function hydrateCurrentRankCard() {
      const iconEl = document.getElementById('currentRankIcon');
      const titleEl = document.getElementById('currentRankTitle');
      const statusEl = document.getElementById('currentRankStatus');
      const statusChipEl = document.getElementById('rankStatusChip');
      if (!iconEl && !titleEl && !statusEl) {
        return;
      }

      try {
        const res = await fetch('../PHP/profileCard.php', { credentials: 'include', cache: 'no-store' });
        if (res.status === 401) {
          return;
        }
        const payload = await res.json();
        if (!payload || payload.status !== 'success') {
          return;
        }

        const level = (payload.data?.level || '').toLowerCase();
        const isAdmin = level === 'admin';
        if (isAdmin) {
          if (iconEl) {
            iconEl.src = '../Assets/RankBronze.png';
          }
          if (titleEl) {
            titleEl.textContent = 'Admin (Not Ranked)';
          }
          if (statusEl) {
            statusEl.textContent = 'Admin accounts are not ranked.';
          }
          if (statusChipEl) {
            statusChipEl.textContent = 'Admin';
            statusChipEl.classList.remove('chip-eligible');
          }
          highlightRankLadder('');
          return;
        }

        const rank = payload.data?.rank || {};
        const rankDisplay = displayRankTitle(rank.title);
        if (iconEl && rank.badge) {
          iconEl.src = rank.badge;
        }
        if (titleEl && rankDisplay) {
          titleEl.textContent = rankDisplay;
        }
        if (statusEl) {
          statusEl.textContent = rank.status || (rank.title === 'Bronze' ? 'Not Eligible to fly' : 'Cleared for all lessons');
        }
        if (statusChipEl) {
          const eligible = (rank.status || '').toLowerCase().includes('eligible');
          statusChipEl.textContent = rank.status || 'Training';
          statusChipEl.classList.toggle('chip-eligible', eligible);
        }
        highlightRankLadder(rank.title || '');
      } catch (error) {
        console.error('Unable to hydrate rank card', error);
      }
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const staticPilots = [
        { username: 'capiora', score: 15000, xp: 15000, rank: 'Grand Commander', rankKey: 'commander', status: 'Eligible', lessons: 52, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankCommander.png', joined: 'Oct 2025' },
        { username: 'shahrin11', score: 7000, xp: 7000, rank: 'Commander', rankKey: 'captain', status: 'Eligible', lessons: 38, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankCaptain.png', joined: 'Sep 2025' },
        { username: 'firdaus245', score: 6000, xp: 6000, rank: 'Elite Pilot', rankKey: 'ace', status: 'Eligible', lessons: 33, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankAce.png', joined: 'Aug 2025' },
        { username: 'mohamad1', score: 5000, xp: 5000, rank: 'Gold', rankKey: 'gold', status: 'Not Eligible', lessons: 29, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankGold.png', joined: 'Jul 2025' },
        { username: 'shah02', score: 3200, xp: 3200, rank: 'Silver', rankKey: 'silver', status: 'Not Eligible', lessons: 18, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankSilver.png', joined: 'Jun 2025' },
        { username: 'fitri245', score: 1500, xp: 1500, rank: 'Bronze', rankKey: 'bronze', status: 'Not Eligible', lessons: 9, avatar: '../Assets/StudentProfile.png', badge: '../Assets/RankBronze.png', joined: 'May 2025' }
      ];

      wireRulesCta();

      const controller = initLeaderboard([]);
      controller.showSkeleton();
      const refreshButton = document.getElementById('refreshLeaderboard');
      const MIN_REFRESH_DURATION = 3000;

      const restoreStaticSnapshot = () => {
        if (controller.resetView) {
          controller.resetView();
        }
        controller.setData(staticPilots);
      };

      const hydrateLeaderboard = async (options = { allowFallback: true, minDuration: 0 }) => {
        const allowFallback = options?.allowFallback !== false;
        const minDuration = Math.max(0, options?.minDuration || 0);
        const startTime = Date.now();
        if (refreshButton) {
          refreshButton.disabled = true;
          if (minDuration > 0) {
            refreshButton.setAttribute('aria-busy', 'true');
          } else {
            refreshButton.removeAttribute('aria-busy');
          }
        }
        try {
          const dynamicPilots = await fetchDynamicPilots();
          const normalized = mergePilots([], filterOutAdmins(dynamicPilots));
          if (normalized.length) {
            controller.setData(normalized);
          } else if (allowFallback) {
            restoreStaticSnapshot();
          } else {
            controller.setData([]);
          }
        } catch (error) {
          console.error('Unable to load dynamic leaderboard data', error);
          if (allowFallback) {
            restoreStaticSnapshot();
          }
        } finally {
          if (refreshButton) {
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, minDuration - elapsed);
            if (remaining > 0) {
              await new Promise((resolve) => setTimeout(resolve, remaining));
            }
            refreshButton.disabled = false;
            refreshButton.removeAttribute('aria-busy');
          }
        }
      };

      await hydrateLeaderboard();

      if (refreshButton) {
        refreshButton.addEventListener('click', () => {
          hydrateLeaderboard({ allowFallback: true, minDuration: MIN_REFRESH_DURATION });
        });
      }

      hydrateCurrentRankCard();
    });

      function wireRulesCta() {
        const viewRulesBtn = document.getElementById('viewRulesBtn');
        if (viewRulesBtn) {
          // handled by rules modal; keep here to avoid navigation
          viewRulesBtn.addEventListener('click', (event) => {
            event.preventDefault();
          });
        }
      }

    function toNumeric(value, fallback = 0) {
      const num = Number(value);
      return Number.isFinite(num) ? num : fallback;
    }

    function calculateXp(pilot = {}) {
      const xp = toNumeric(pilot?.xp ?? pilot?.score);
      return xp < 0 ? 0 : xp;
    }

    const DEFAULT_MISSIONS = 25;
    function calculateMissions(pilot = {}) {
      // Force every account to display 25 lessons as requested.
      const lessons = toNumeric(pilot?.lessons, DEFAULT_MISSIONS);
      return lessons < 0 ? DEFAULT_MISSIONS : DEFAULT_MISSIONS;
    }

    function filterOutAdmins(pilots = []) {
      return pilots.filter((pilot) => (pilot.level || '').toLowerCase() !== 'admin');
    }

    function buildProfileUrl(username = '') {
      const handle = username || '';
        // Admins stay in the admin area (admin sidebar) when opening a pilot's profile.
        return `../HTML/adminViewProfile.php?user=${encodeURIComponent(handle)}`;
    }

    function persistPilotSnapshot(pilot = {}) {
      try {
        sessionStorage.setItem('selectedPilot', JSON.stringify(pilot));
      } catch (error) {
        console.warn('Unable to persist pilot snapshot', error);
      }
    }

    function withXpAndMissions(pilots = []) {
      return pilots.map((pilot) => {
        const xp = calculateXp(pilot);
        const lessons = calculateMissions(pilot);
        return { ...pilot, xp, score: xp, lessons };
      });
    }

    function normalizePilot(pilot = {}) {
      const xp = calculateXp(pilot);
      const lessons = calculateMissions(pilot);
      const rankKeySource = pilot.rankKey || pilot.rank || 'bronze';
      return {
        username: pilot.username || 'Unknown Pilot',
        score: xp,
        xp,
        lessons,
        rank: pilot.rank || 'Bronze',
        rankKey: rankKeySource.toString().toLowerCase(),
        status: pilot.status || 'Not Eligible',
        badge: pilot.badge || '../Assets/RankBronze.png',
        avatar: pilot.avatar || '../Assets/StudentProfile.png',
        joined: pilot.joined || '--',
      };
    }

    function mergePilots(staticPilots, dynamicPilots) {
      const pilotMap = new Map();
      staticPilots.forEach((pilot) => {
        if (!pilot?.username) {
          return;
        }
        pilotMap.set(pilot.username.toLowerCase(), normalizePilot(pilot));
      });

      dynamicPilots.forEach((pilot) => {
        if (!pilot?.username) {
          return;
        }
        pilotMap.set(pilot.username.toLowerCase(), normalizePilot(pilot));
      });

      return Array.from(pilotMap.values());
    }

    async function fetchDynamicPilots() {
      const response = await fetch('../PHP/leaderboard.php', {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) {
        throw new Error('Network error');
      }
      const payload = await response.json();
      if (payload?.status === 'success' && Array.isArray(payload.data)) {
        return payload.data;
      }
      return [];
    }

    function initLeaderboard(initialPilots) {
      const state = {
        sortedData: [],
        activeRank: 'all',
        searchTerm: '',
        page: 1,
      };
      const PAGE_SIZE = 10;

      const leaderboardBody = document.getElementById('leaderboard-body');
      const topPilotsContainer = document.getElementById('topPilots');
      const searchInput = document.getElementById('searchInput');
      const filterButtons = Array.from(document.querySelectorAll('#rankFilters .filter-btn'));
      const emptyState = document.getElementById('emptyState');
      const statActivePilots = document.getElementById('statActivePilots');
      const statAvgScore = document.getElementById('statAvgScore');
      const statEligible = document.getElementById('statEligible');

      if (searchInput && !searchInput.dataset.bound) {
        searchInput.addEventListener('input', (event) => {
          state.searchTerm = event.target.value.trim().toLowerCase();
          state.page = 1;
          renderTable();
        });
        searchInput.dataset.bound = 'true';
      }

      filterButtons.forEach((button) => {
        if (button.dataset.bound) {
          return;
        }
        button.addEventListener('click', () => {
          state.activeRank = button.dataset.rank || 'all';
          state.page = 1;
          filterButtons.forEach((btn) => {
            const isActive = btn === button;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
          });
          renderTable();
        });
        button.dataset.bound = 'true';
      });

      function setData(pilots) {
        // Keep Ace-ranked pilots at the very top of the list, even above Commander/Captain.
        const RANK_ORDER = ['ace', 'commander', 'captain', 'gold', 'silver', 'bronze'];
        const rankWeight = (key = '') => {
          const normalized = key.toLowerCase().replace(/[^a-z]/g, '');
          const idx = RANK_ORDER.indexOf(normalized);
          return idx === -1 ? RANK_ORDER.length : idx;
        };

        const enriched = withXpAndMissions(pilots).map((pilot) => {
          const xp = calculateXp(pilot);
          const rankKey = (pilot.rankKey || pilot.rank || '').toLowerCase().replace(/[^a-z]/g, '');
          return { ...pilot, xp, score: xp, rankKey };
        });

        state.sortedData = enriched
          .sort((a, b) => {
            const rankDiff = rankWeight(a.rankKey) - rankWeight(b.rankKey);
            if (rankDiff !== 0) return rankDiff;
            return (b.xp || 0) - (a.xp || 0);
          })
          .map((pilot, index) => ({ ...pilot, position: index + 1 }));
        state.page = 1;
        updateStats();
        renderTopPilots();
        renderTable();
      }

      function updateStats() {
        if (statActivePilots) {
          statActivePilots.textContent = state.sortedData.length.toString();
        }
        if (statAvgScore) {
          const totalXp = state.sortedData.reduce((sum, pilot) => sum + (pilot.xp || 0), 0);
          const avg = state.sortedData.length ? Math.round(totalXp / state.sortedData.length) : 0;
          statAvgScore.textContent = avg.toLocaleString();
        }
        if (statEligible) {
          const eligibleCount = state.sortedData.filter((pilot) => pilot.status === 'Eligible').length;
          statEligible.textContent = eligibleCount.toString();
        }
      }

      function renderTopPilots() {
        if (!topPilotsContainer) {
          return;
        }
        topPilotsContainer.innerHTML = '';
        const topThree = state.sortedData.slice(0, 3);
        topThree.forEach((pilot, index) => {
          const profileLink = buildProfileUrl(pilot.username);
          const card = document.createElement('div');
          card.className = 'top-pilot-card';
          card.classList.add('cursor-pointer');
          const navigateToProfile = () => {
            persistPilotSnapshot(pilot);
            window.location.href = profileLink;
          };
          card.addEventListener('click', navigateToProfile);
          if (index === 0) {
            card.classList.add('primary');
          }

          const infoWrapper = document.createElement('div');
          infoWrapper.className = 'top-pilot-card__info';

          const positionEl = document.createElement('span');
          positionEl.className = 'top-pilot-card__position';
          positionEl.textContent = pilot.position.toString();
          infoWrapper.appendChild(positionEl);

          const details = document.createElement('div');
          details.className = 'top-pilot-card__details';

          const nameEl = document.createElement('a');
          nameEl.className = 'top-pilot-card__name hover:text-blue-700';
          nameEl.href = profileLink;
          nameEl.textContent = pilot.username;
          nameEl.addEventListener('click', (event) => {
            event.stopPropagation();
            navigateToProfile();
          });

          const scoreEl = document.createElement('p');
          scoreEl.className = 'top-pilot-card__score';
          scoreEl.textContent = 'XP ';
          const scoreValue = document.createElement('span');
          scoreValue.className = 'top-pilot-card__score-value';
          scoreValue.textContent = (pilot.xp || 0).toLocaleString();
          scoreEl.appendChild(scoreValue);

          details.appendChild(nameEl);
          details.appendChild(scoreEl);
          infoWrapper.appendChild(details);

          const rankBadge = document.createElement('span');
          rankBadge.className = 'top-pilot-badge';
          const displayRank = displayRankTitle(pilot.rank);
          rankBadge.title = displayRank;
          const rankIcon = document.createElement('img');
          rankIcon.src = pilot.badge;
          rankIcon.alt = `${displayRank} badge`;
          rankIcon.className = 'top-pilot-badge__icon';
          rankBadge.appendChild(rankIcon);

          card.appendChild(infoWrapper);
          card.appendChild(rankBadge);
          topPilotsContainer.appendChild(card);
        });
      }

      function renderTable() {
        if (!leaderboardBody) {
          return;
        }
        const filtered = state.sortedData.filter((pilot) => {
          const rankKey = (pilot.rankKey || '').toLowerCase();
          const rankTitle = (pilot.rank || '').toLowerCase();
          const matchesRank =
            state.activeRank === 'all' ||
            rankKey === state.activeRank ||
            rankTitle.includes(state.activeRank);
          const matchesSearch = pilot.username.toLowerCase().includes(state.searchTerm);
          return matchesRank && matchesSearch;
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        state.page = Math.min(Math.max(1, state.page), totalPages);
        const pageStart = (state.page - 1) * PAGE_SIZE;
        const pageItems = filtered.slice(pageStart, pageStart + PAGE_SIZE);

        leaderboardBody.innerHTML = '';
        pageItems.forEach((pilot) => {
          const profileLink = buildProfileUrl(pilot.username);
          const navigateToProfile = () => {
            persistPilotSnapshot(pilot);
            window.location.href = profileLink;
          };

          const row = document.createElement('tr');
          row.className = 'hover:bg-slate-50 transition cursor-pointer';
          row.addEventListener('click', navigateToProfile);

          const positionCell = document.createElement('td');
          positionCell.className = 'px-4 py-3 font-semibold text-slate-600';
          positionCell.textContent = pilot.position.toString();
          row.appendChild(positionCell);

          const avatarCell = document.createElement('td');
          avatarCell.className = 'px-4 py-3';
          const avatarImg = document.createElement('img');
          avatarImg.src = pilot.avatar;
          avatarImg.alt = pilot.username;
          avatarImg.className = 'w-10 h-10 rounded-full';
          avatarCell.appendChild(avatarImg);
          row.appendChild(avatarCell);

          const pilotCell = document.createElement('td');
          pilotCell.className = 'px-4 py-3';
          const pilotName = document.createElement('a');
          pilotName.className = 'font-semibold text-slate-900 hover:text-blue-600';
          pilotName.href = profileLink;
          pilotName.textContent = pilot.username;
          pilotName.addEventListener('click', (event) => {
            event.stopPropagation();
            navigateToProfile();
          });
          const joined = document.createElement('p');
          joined.className = 'text-xs text-slate-500';
          joined.textContent = `Joined ${pilot.joined || '--'}`;
          pilotCell.appendChild(pilotName);
          pilotCell.appendChild(joined);
          row.appendChild(pilotCell);

          const xpCell = document.createElement('td');
          xpCell.className = 'px-4 py-3 font-semibold text-slate-900';
          xpCell.textContent = (pilot.xp || 0).toLocaleString();
          row.appendChild(xpCell);

          const rankCell = document.createElement('td');
          rankCell.className = 'px-4 py-3';
          const pill = document.createElement('span');
          pill.className = 'rank-pill bg-violet-50 text-violet-700';
          const rankName = displayRankTitle(pilot.rank);
          pill.title = rankName;
          const badgeIcon = document.createElement('img');
          badgeIcon.src = pilot.badge;
          badgeIcon.alt = `${rankName} badge`;
          badgeIcon.className = 'w-6 h-6';
          pill.appendChild(badgeIcon);
          const pillText = document.createElement('span');
          pillText.className = 'ml-2 font-semibold';
          pillText.textContent = rankName;
          pill.appendChild(pillText);
          rankCell.appendChild(pill);
          row.appendChild(rankCell);

          const lessonsCell = document.createElement('td');
          lessonsCell.className = 'px-4 py-3 text-slate-600';
          lessonsCell.textContent = `${pilot.lessons} lessons`;
          row.appendChild(lessonsCell);

          const statusCell = document.createElement('td');
          statusCell.className = 'px-4 py-3';
          const statusChip = document.createElement('span');
          const isEligible = pilot.status === 'Eligible';
          statusChip.className = `status-chip ${isEligible ? 'eligible' : 'pending'}`;
          statusChip.textContent = pilot.status || (isEligible ? 'Eligible' : 'Not Eligible');
          statusCell.appendChild(statusChip);
          row.appendChild(statusCell);

          leaderboardBody.appendChild(row);
        });

        if (emptyState) {
          emptyState.classList.toggle('hidden', filtered.length > 0);
        }
        renderPagination(filtered.length, totalPages);
      }

      function renderPagination(totalItems, totalPages) {
        const wrap = document.getElementById('lbPagination');
        const info = document.getElementById('lbPaginationInfo');
        const controls = document.getElementById('lbPaginationControls');
        if (!wrap || !info || !controls) return;
        if (totalItems === 0) {
          wrap.classList.add('hidden');
          return;
        }
        wrap.classList.remove('hidden');
        const start = (state.page - 1) * PAGE_SIZE + 1;
        const end = Math.min(state.page * PAGE_SIZE, totalItems);
        info.textContent = `Showing ${start}–${end} of ${totalItems} pilots`;

        const btn = (label, page, { disabled = false, active = false } = {}) => {
          const base = 'min-w-[36px] h-9 px-3 rounded-lg text-sm font-semibold border transition';
          const look = active
            ? 'bg-indigo-600 text-white border-indigo-600'
            : disabled
            ? 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
          return `<button type="button" class="${base} ${look}" ${disabled ? 'disabled' : ''} data-page="${page}"${active ? ' aria-current="page"' : ''}>${label}</button>`;
        };
        const parts = [btn('Prev', state.page - 1, { disabled: state.page === 1 })];
        for (let i = 1; i <= totalPages; i++) parts.push(btn(String(i), i, { active: i === state.page }));
        parts.push(btn('Next', state.page + 1, { disabled: state.page === totalPages }));
        controls.innerHTML = parts.join('');
        controls.querySelectorAll('button[data-page]').forEach((b) => {
          b.addEventListener('click', () => {
            const page = Number(b.dataset.page);
            if (!Number.isFinite(page) || page < 1 || page > totalPages || page === state.page) return;
            state.page = page;
            renderTable();
            leaderboardBody.closest('.rounded-2xl')?.scrollIntoView({ block: 'nearest' });
          });
        });
      }

      // Placeholder rows shown until the first fetch settles; setData() replaces them.
      function showSkeleton(count = 6) {
        if (!leaderboardBody) return;
        if (emptyState) emptyState.classList.add('hidden');
        document.getElementById('lbPagination')?.classList.add('hidden');
        const bar = (w) => `<div class="cf-skeleton h-4 ${w} rounded">&nbsp;</div>`;
        leaderboardBody.innerHTML = Array.from({ length: count }, () => `
          <tr>
            <td class="px-4 py-3">${bar('w-6')}</td>
            <td class="px-4 py-3"><div class="cf-skeleton w-10 h-10 rounded-full">&nbsp;</div></td>
            <td class="px-4 py-3">${bar('w-28')}</td>
            <td class="px-4 py-3">${bar('w-14')}</td>
            <td class="px-4 py-3">${bar('w-24')}</td>
            <td class="px-4 py-3">${bar('w-16')}</td>
            <td class="px-4 py-3">${bar('w-20')}</td>
          </tr>`).join('');
      }

      function resetView() {
        state.activeRank = 'all';
        state.searchTerm = '';
        state.page = 1;
        if (searchInput) {
          searchInput.value = '';
        }
        filterButtons.forEach((button) => {
          const isActive = button.dataset.rank === 'all';
          button.classList.toggle('active', isActive);
          button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderTopPilots();
        renderTable();
      }

      return {
        setData,
        resetView,
        showSkeleton,
      };
    }
  </script>

  <!-- Rules modal -->
  <div id="rulesModal" class="rules-backdrop" aria-hidden="true">
    <div class="rules-card" role="dialog" aria-modal="true" aria-labelledby="rulesTitle">
      <div class="rules-header">
        <h3 id="rulesTitle" class="rules-title">Codefly Leaderboard Rules</h3>
      </div>
      <ul class="rules-list">
        <li><i class='bx bx-rocket'></i><span>Earn XP by completing lessons, lessons, and quizzes. Higher-tier modules yield more XP.</span></li>
        <li><i class='bx bx-lock-alt'></i><span>Workspace access requires Elite Pilot rank or higher. Ranks below Elite Pilot remain in training.</span></li>
        <li><i class='bx bx-time-five'></i><span>Leaderboard refreshes hourly. Ties are broken by earliest completion time.</span></li>
        <li><i class='bx bx-shield-quarter'></i><span>Fair play only—no automation or spoofed runs. Violations may reset XP.</span></li>
        <li><i class='bx bx-target-lock'></i><span>Grand Commander tier unlocks pro automation tools once you hit 5,000 XP.</span></li>
      </ul>
      <div class="rules-actions">
        <button type="button" class="rules-btn primary" data-close-rules style="width: 100%;">Got it</button>
      </div>
    </div>
  </div>

  <script>
    // Rules modal wiring
    const rulesModal = document.getElementById('rulesModal');
    const rulesOpenBtn = document.getElementById('viewRulesBtn');
    const rulesCloseButtons = rulesModal ? rulesModal.querySelectorAll('[data-close-rules]') : [];

    const openRules = () => {
      if (!rulesModal) return;
      rulesModal.classList.add('active');
      rulesModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
    };
    const closeRules = () => {
      if (!rulesModal) return;
      rulesModal.classList.remove('active');
      rulesModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    };

    rulesOpenBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      openRules();
    });
    rulesCloseButtons.forEach(btn => btn.addEventListener('click', closeRules));
    rulesModal?.addEventListener('click', (event) => {
      if (event.target === rulesModal) {
        closeRules();
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && rulesModal?.classList.contains('active')) {
        closeRules();
      }
    });
  </script>
  <script src="../JS/home.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>


