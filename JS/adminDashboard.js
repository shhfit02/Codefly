const ADMIN_DASHBOARD_ENDPOINT = "../PHP/adminDashboard.php";
const PASSWORD_CHANGE_ENDPOINT = "../PHP/changePassword.php";
const DEFAULT_AVATAR = "../Assets/StudentProfile.png";

// Chart.js tooltip styling (match card text, white background)
if (typeof Chart !== "undefined" && Chart.defaults?.plugins?.tooltip) {
  Chart.defaults.color = "#0f172a";
  Chart.defaults.plugins.tooltip.backgroundColor = "#ffffff";
  Chart.defaults.plugins.tooltip.borderColor = "rgba(15,23,42,0.12)";
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.titleColor = "#0f172a";
  Chart.defaults.plugins.tooltip.bodyColor = "#0f172a";
  Chart.defaults.plugins.tooltip.titleFont = { weight: "700" };
}

function displayRankTitle(title) {
  const t = (title || "").toLowerCase();
  if (t === "bronze") return "Rookie";
  if (t === "silver") return "Operator";
  if (t === "gold") return "Specialist";
  if (t === "ace") return "Elite Pilot";
  if (t === "captain") return "Commander";
  if (t === "commander") return "Grand Commander";
  return title || "Rookie";
}

let recentUsersData = [];
let moduleProgressCache = [];
let moduleProgressCohort = 1;
let signupGrowthChart = null;
let moduleCompletionChart = null;

document.addEventListener("DOMContentLoaded", () => {
  hydrateAdminDashboard();
  wirePasswordForm();
});

function hydrateAdminDashboard() {
  fetch(ADMIN_DASHBOARD_ENDPOINT, {
    credentials: "same-origin",
    cache: "no-store",
  })
    .then((res) => {
      if (res.status === 401) {
        window.location.href = "../index.html";
        return null;
      }
      if (res.status === 403) {
        window.location.href = "../HTML/home.html";
        return null;
      }
      return res.json();
    })
    .then((payload) => {
      if (!payload) return;
      if (payload.status !== "success") {
        throw new Error(payload.message || "Unable to load admin data.");
      }

      const data = payload.data || {};
      const totals = data.totals || {};

      setText("stat-total-users", formatNumber(totals.users));
      setText("stat-active-today", formatNumber(totals.activeToday));
      // "Total Modules" card; prefer modules key, fall back to legacy newThisMonth.
      setText("stat-new-month", formatNumber(totals.modules ?? totals.newThisMonth));
      setText("stat-admins", formatNumber(totals.admins));
      setText("stat-avg-score", formatNumber(totals.avgScore));
      setText("stat-lessons-completed", formatNumber(totals.lessonsCompleted));
      setText("stat-completed-lessons", formatNumber(totals.completedMissions));

      try { renderRankDistribution(data.rankDistribution || []); } catch (e) { console.warn("Rank chart error:", e); }
      try { renderTopPilots(data.topPilots || []); } catch (e) { console.warn("Top pilots error:", e); }
      try { renderRecentUsers(data.recentUsers || []); } catch (e) { console.warn("Recent users error:", e); }
      try { renderSignupGrowth(data.signupGrowth?.series || [], data.signupGrowth?.summary || {}); } catch (e) { console.warn("Signup growth error:", e); }
      try { renderModuleProgress(data.moduleStats || [], totals.users || 1); } catch (e) { console.warn("Module progress error:", e); }
    })
    .catch((err) => {
      console.error("Admin dashboard failed to load", err);
      
      const moduleList = document.getElementById("module-progress-list");
      if (moduleList) {
        moduleList.innerHTML = `<div class="col-span-full text-center text-rose-500 py-8">Unable to load data.</div>`;
      }

      if (typeof showToast === "function") {
        showToast("<ion-icon name=\"alert-circle\"></ion-icon>Unable to load admin stats right now.", "error");
      }
    });
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = value ?? "--";
}

function formatNumber(value) {
  if (typeof value !== "number" || Number.isNaN(value)) {
    return "--";
  }
  return value.toLocaleString();
}

function formatShortDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "";
  }
  return date.toLocaleDateString(undefined, { month: "short", day: "numeric" });
}

function formatChange(current, previous) {
  if (typeof current !== "number" || typeof previous !== "number") {
    return null;
  }
  if (previous === 0) {
    return current === 0 ? 0 : 100;
  }
  return ((current - previous) / Math.abs(previous)) * 100;
}

function updateBadge(id, value, change) {
  const valueEl = document.getElementById(id);
  const changeEl = document.getElementById(`${id}-change`);
  if (valueEl) valueEl.textContent = formatNumber(value);
  if (!changeEl) return;

  if (change === null) {
    changeEl.textContent = "--";
    changeEl.className = "text-xs font-semibold text-slate-500";
    return;
  }

  const rounded = Number.isFinite(change) ? change.toFixed(2) : "0.00";
  const prefix = change >= 0 ? "+ " : "- ";
  const absValue = Math.abs(change);
  changeEl.textContent = `${prefix}${absValue.toFixed(2)}%`;
  changeEl.className = "text-xs font-semibold text-slate-600";
}

function renderActivity(activity) {
  const summary = activity.summary || {};
  const series = Array.isArray(activity.series) ? activity.series : [];

  updateBadge("active-monthly", summary.monthly?.current, formatChange(summary.monthly?.current, summary.monthly?.previous ?? null));
  updateBadge("active-weekly", summary.weekly?.current, formatChange(summary.weekly?.current, summary.weekly?.previous ?? null));
  updateBadge("active-daily", summary.daily?.current, formatChange(summary.daily?.current, summary.daily?.previous ?? null));

  renderActivityChart(series);
}

function renderModuleProgress(modules, totalUsers) {
  moduleProgressCache = Array.isArray(modules) ? modules.slice() : [];
  moduleProgressCohort = Number(totalUsers);
  renderModuleStats(moduleProgressCache, moduleProgressCohort);
  renderModuleChart(moduleProgressCache, moduleProgressCohort);
  renderModuleCards(moduleProgressCache, moduleProgressCohort);
}

function computePercent(completed, totalUsers) {
  const total = Number(totalUsers) || 1;
  const percent = ((Number(completed) || 0) / total) * 100;
  return clampPercent(percent);
}

function clampPercent(value) {
  if (!Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(100, Math.round(value)));
}

function getProgressState(percent) {
  if (!Number.isFinite(percent)) percent = 0;

  if (percent >= 75) {
    return {
      label: "On track",
      pillClass: "bg-emerald-50 text-emerald-700 border border-emerald-100",
      pillLabelClass: "text-emerald-600",
      dotClass: "bg-emerald-400",
      barClass: "from-emerald-500 to-sky-500",
    };
  }

  if (percent >= 40) {
    return {
      label: "In motion",
      pillClass: "bg-sky-50 text-sky-700 border border-sky-100",
      pillLabelClass: "text-sky-600",
      dotClass: "bg-sky-400",
      barClass: "from-sky-500 to-indigo-500",
    };
  }

  return {
    label: "Needs push",
    pillClass: "bg-amber-50 text-amber-700 border border-amber-100",
    pillLabelClass: "text-amber-600",
    dotClass: "bg-amber-400",
    barClass: "from-amber-500 to-rose-500",
  };
}

function paintModuleProgressRows(list) {
}

function wireModuleProgressSearch() {
  const input = document.getElementById("module-progress-search");
  if (!input || input.dataset.wired === "true") return;
  input.dataset.wired = "true";
  input.addEventListener("input", (event) => {
    const term = (event.target.value || "").trim().toLowerCase();
    const filtered = !term
      ? moduleProgressCache
      : moduleProgressCache.filter((mod) => (mod.title || "").toLowerCase().includes(term));
    renderModuleCards(filtered, moduleProgressCohort);
    renderModuleChart(filtered, moduleProgressCohort);
    renderModuleStats(filtered, moduleProgressCohort);
  });
}

function getModuleStatus(rawStatus) {
  const status = (rawStatus || "").toString().toLowerCase();
  if (status === "published" || status === "live" || status === "active") {
    return {
      label: "Published",
      className: "bg-emerald-50 text-emerald-700 border border-emerald-100",
      dot: "bg-emerald-400",
    };
  }
  if (status === "draft" || status === "pending") {
    return {
      label: "Draft",
      className: "bg-amber-50 text-amber-700 border border-amber-100",
      dot: "bg-amber-400",
    };
  }
  return {
    label: "Unspecified",
    className: "bg-slate-50 text-slate-600 border border-slate-200",
    dot: "bg-slate-300",
  };
}

function formatModuleRating(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) return "--";
  return `${num.toFixed(1)} <i class='bx bxs-star text-amber-500 align-middle'></i>`;
}

function renderModuleStats(modules, totalUsers) {
  const totalModules = Array.isArray(modules) ? modules.length : 0;
  const safeTotalUsers = Number(totalUsers) || 1;
  const completedSum = Array.isArray(modules)
    ? modules.reduce((sum, mod) => sum + (Number(mod.completed) || 0), 0)
    : 0;
  const avgCompletion =
    totalModules > 0
      ? Math.round(
          modules.reduce((sum, mod) => sum + computePercent(mod.completed, safeTotalUsers), 0) / totalModules
        )
      : 0;

  setText("module-stat-total", formatNumber(totalModules));
  setText("module-stat-active", formatNumber(safeTotalUsers));
  setText("module-stat-avg", Number.isFinite(avgCompletion) ? `${avgCompletion}%` : "--");
  setText("module-stat-completed", formatNumber(completedSum));
}

function renderModuleCards(modules, totalUsers) {
  const container = document.getElementById("module-progress-cards");
  if (!container) return;
  container.innerHTML = "";

  if (!Array.isArray(modules) || modules.length === 0) {
    container.innerHTML = `<div class="text-sm text-slate-500">No module data available yet.</div>`;
    return;
  }

  const safeTotal = Number(totalUsers) || 1;
  const sortedModules = modules
    .slice()
    .sort((a, b) => computePercent(b.completed, safeTotal) - computePercent(a.completed, safeTotal));

  sortedModules.forEach((mod, index) => {
    const completed = Number(mod.completed) || 0;
    const percentValue = computePercent(completed, safeTotal);
    const accentClass = ["from-sky-500 to-indigo-500", "from-amber-500 to-rose-500", "from-emerald-500 to-sky-400", "from-purple-500 to-pink-500"][index % 4];
    const cover = mod.image || mod.image_path || mod.image_url || "../Assets/basicModule.png";

    const card = document.createElement("div");
    card.className = "flex items-center gap-4 p-4 rounded-xl border border-slate-100 bg-slate-50";
    card.innerHTML = `
      <div class="w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-br ${accentClass} border border-white shadow-sm">
        <img src="${escapeHtml(cover)}" alt="${escapeHtml(mod.title || "Module")} cover" class="w-full h-full object-cover">
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-slate-900 truncate">${escapeHtml(mod.title || "Untitled module")}</p>
        <p class="text-xs text-slate-500">${formatNumber(completed)} completions</p>
        <div class="flex items-center gap-3 mt-2">
          <div class="flex-1 h-2 bg-white rounded-full overflow-hidden border border-slate-200">
            <div class="h-2 rounded-full bg-gradient-to-r ${accentClass}" style="width:${percentValue}%;"></div>
          </div>
          <span class="text-sm font-semibold text-slate-800 tabular-nums">${percentValue}%</span>
        </div>
      </div>
    `;
    container.appendChild(card);
  });
}

function renderModuleChart(modules, totalUsers) {
  const canvas = document.getElementById("module-completion-chart");
  const empty = document.getElementById("module-chart-empty");
  if (!canvas || typeof Chart === "undefined") return;

  if (!Array.isArray(modules) || modules.length === 0) {
    if (empty) empty.classList.remove("hidden");
    if (moduleCompletionChart) {
      moduleCompletionChart.destroy();
      moduleCompletionChart = null;
    }
    return;
  }

  if (empty) empty.classList.add("hidden");

  const safeTotal = Number(totalUsers) || 1;
  const labels = modules.map((m) => m.title || "Module");
  const data = modules.map((m) => computePercent(m.completed, safeTotal));

  if (moduleCompletionChart) {
    moduleCompletionChart.destroy();
  }

  const ctx = canvas.getContext("2d");
  moduleCompletionChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: "Completion %",
          data,
          backgroundColor: "rgba(99, 102, 241, 0.7)",
          borderRadius: 8,
          maxBarThickness: 32,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `${context.parsed.y}%`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: "#94a3b8" },
        },
        y: {
          beginAtZero: true,
          max: 100,
          grid: { color: "rgba(148,163,184,0.25)", drawBorder: false },
          ticks: { color: "#94a3b8" },
        },
      },
    },
  });
}

function getModuleIcon(title) {
  const t = (title || "").toLowerCase();
  if (t.includes("react")) return "bxl-react";
  if (t.includes("type") || t.includes("ts")) return "bxl-typescript";
  if (t.includes("node") || t.includes("next")) return "bxl-nodejs";
  if (t.includes("java") || t.includes("js")) return "bxl-javascript";
  if (t.includes("python")) return "bxl-python";
  if (t.includes("data") || t.includes("sql") || t.includes("db")) return "bx-data";
  if (t.includes("design") || t.includes("css") || t.includes("ui")) return "bx-palette";
  if (t.includes("html")) return "bxl-html5";
  if (t.includes("git")) return "bxl-git";
  if (t.includes("security") || t.includes("auth")) return "bx-shield";
  return "bx-layer";
}

function renderActivityChart(series) {
  const canvas = document.getElementById("activeStudentsChart");
  if (!canvas) return;

  const labels = series.map((item) => item.label || "");
  const monthlyData = series.map((item) => Number(item.monthly) || 0);
  const weeklyData = series.map((item) => Number(item.weekly) || 0);
  const dailyData = series.map((item) => Number(item.daily) || 0);

  if (window.activeStudentsChart) {
    window.activeStudentsChart.destroy();
  }

  const ctx = canvas.getContext("2d");
  window.activeStudentsChart = new Chart(ctx, {
    data: {
      labels,
      datasets: [
        {
          type: "bar",
          label: "Monthly",
          data: monthlyData,
          backgroundColor: "rgba(6, 182, 212, 0.8)",
          borderRadius: 8,
          maxBarThickness: 28,
        },
        {
          type: "line",
          label: "Weekly",
          data: weeklyData,
          borderColor: "rgba(163, 163, 163, 0.9)",
          backgroundColor: "rgba(163, 163, 163, 0.15)",
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 0,
        },
        {
          type: "line",
          label: "Daily",
          data: dailyData,
          borderColor: "#6366f1",
          backgroundColor: "rgba(99, 102, 241, 0.12)",
          fill: true,
          tension: 0.45,
          borderWidth: 3,
          pointRadius: 0,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: "#94a3b8", font: { size: 12 } },
        },
        y: {
          grid: { color: "rgba(148, 163, 184, 0.25)", drawBorder: false },
          ticks: { color: "#94a3b8", font: { size: 12 }, beginAtZero: true },
        },
      },
      interaction: {
        mode: "index",
        intersect: false,
      },
    },
  });
}

function getRankBadge(label) {
  const normalized = (label || "").toLowerCase();
  const map = {
    bronze: "../Assets/RankBronze.png",
    silver: "../Assets/RankSilver.png",
    gold: "../Assets/RankGold.png",
    ace: "../Assets/RankAce.png",
    captain: "../Assets/RankCaptain.png",
    commander: "../Assets/RankCommander.png",
  };
  return map[normalized] || "../Assets/RankBronze.png";
}

function renderRankDistribution(items) {
  const svg = document.getElementById("rank-donut");
  const empty = document.getElementById("rankEmpty");
  const totalEl = document.getElementById("rank-total");
  const tiersEl = document.getElementById("rank-tiers");
  const top3El = document.getElementById("rank-top3");
  const mostCommonEl = document.getElementById("rank-most-common");
  const tooltip = document.getElementById("rank-tooltip");
  if (!svg) return;

  svg.innerHTML = "";

  if (!Array.isArray(items) || items.length === 0) {
    if (totalEl) totalEl.textContent = "--";
    if (tiersEl) tiersEl.textContent = "--";
    if (top3El) top3El.textContent = "--";
    if (mostCommonEl) mostCommonEl.textContent = "--";
    if (tooltip) tooltip.classList.add("hidden");
    if (empty) {
      empty.classList.remove("hidden");
      empty.textContent = "No rank data yet.";
    }
    return;
  }

  const rankColors = {
    bronze: "#f97316",
    silver: "#94a3b8",
    gold: "#f59e0b",
    ace: "#06b6d4",
    captain: "#3b82f6",
    commander: "#8b5cf6",
  };

  const safeItems = items.map((item) => ({
    label: item.label || "Unknown",
    count: Number(item.count) || 0,
    color: rankColors[(item.label || "").toLowerCase().trim()] || "#cbd5e1",
  }));

  const totalCount = safeItems.reduce((sum, item) => sum + item.count, 0);
  if (totalEl) totalEl.textContent = formatNumber(totalCount);
  if (tiersEl) tiersEl.textContent = formatNumber(safeItems.length);

  const sortedByCount = safeItems.slice().sort((a, b) => b.count - a.count);
  const topThreeTotal = sortedByCount.slice(0, 3).reduce((sum, item) => sum + item.count, 0);
  const topShare = totalCount > 0 ? Math.round((topThreeTotal / totalCount) * 100) : 0;
  if (top3El) top3El.textContent = `${topShare}%`;
  if (mostCommonEl) {
    mostCommonEl.textContent = sortedByCount[0]
      ? `${sortedByCount[0].label} (${formatNumber(sortedByCount[0].count)})`
      : "--";
  }

  if (empty) {
    empty.classList.add("hidden");
  }

  const radius = 50;
  const strokeWidth = 12;
  const circumference = 2 * Math.PI * radius;
  let currentOffset = 0;

  safeItems.forEach((item, index) => {
    const pct = totalCount > 0 ? item.count / totalCount : 0;
    const segmentLength = circumference * pct;
    const offset = currentOffset;

    const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    circle.setAttribute("cx", "60");
    circle.setAttribute("cy", "60");
    circle.setAttribute("r", radius);
    circle.setAttribute("fill", "none");
    circle.setAttribute("stroke", item.color);
    circle.setAttribute("stroke-width", strokeWidth);
    circle.setAttribute("stroke-dasharray", `${segmentLength} ${circumference}`);
    circle.setAttribute("stroke-dashoffset", `-${offset + circumference}`);
    circle.setAttribute("stroke-linecap", "round");
    circle.classList.add("donut-segment");
    circle.style.transition = `stroke-dashoffset 1s ease-out ${index * 0.05}s`;
    circle.style.cursor = "pointer";

    svg.appendChild(circle);

    // trigger animation
    setTimeout(() => {
      circle.style.strokeDashoffset = `-${offset}`;
    }, 20);

    if (tooltip) {
      const pctDisplay = totalCount > 0 ? ((item.count / totalCount) * 100).toFixed(1) : "0.0";
      const showTip = (event) => {
        const rect = svg.getBoundingClientRect();
        tooltip.classList.remove("hidden");
        tooltip.style.left = `${event.clientX - rect.left}px`;
        tooltip.style.top = `${event.clientY - rect.top}px`;
        tooltip.textContent = `${item.label}: ${formatNumber(item.count)} (${pctDisplay}%)`;
      };
      circle.addEventListener("mousemove", showTip);
      circle.addEventListener("mouseenter", showTip);
      circle.addEventListener("mouseleave", () => {
        tooltip.classList.add("hidden");
      });
    }

    currentOffset += segmentLength;
  });
}

// simple slider for rank card
let rankSlideIndex = 0;
let rankSlideTimer = null;

function showRankSlide(index) {
  const slides = Array.from(document.querySelectorAll("#rank-slider .rank-slide"));
  const dots = Array.from(document.querySelectorAll(".rank-dot"));
  if (!slides.length) return;
  rankSlideIndex = ((index % slides.length) + slides.length) % slides.length;
  slides.forEach((slide, idx) => {
    slide.classList.toggle("hidden", idx !== rankSlideIndex);
    slide.classList.toggle("block", idx === rankSlideIndex);
  });
  dots.forEach((dot, idx) => {
    dot.classList.toggle("bg-slate-400", idx === rankSlideIndex);
    dot.classList.toggle("bg-slate-200", idx !== rankSlideIndex);
  });
}

function startRankSlider() {
  const dots = Array.from(document.querySelectorAll(".rank-dot"));
  dots.forEach((dot) => {
    dot.addEventListener("click", () => {
      const target = Number(dot.dataset.target) || 0;
      showRankSlide(target);
      resetRankTimer();
    });
  });
  resetRankTimer();
}

function resetRankTimer() {
  if (rankSlideTimer) clearInterval(rankSlideTimer);
  rankSlideTimer = setInterval(() => {
    showRankSlide(rankSlideIndex + 1);
  }, 5000);
}

document.addEventListener("DOMContentLoaded", () => {
  startRankSlider();
});

function renderTopPilots(items) {
  const list = document.getElementById("top-pilots");
  if (!list) return;
  list.innerHTML = "";

  if (!Array.isArray(items) || items.length === 0) {
    list.innerHTML = `<li class="text-sm text-slate-500">No pilot stats yet.</li>`;
    return;
  }

  items.forEach((pilot) => {
    const li = document.createElement("li");
    li.className = "flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 bg-white shadow-sm";
    const initial = (pilot.fullName || pilot.username || "?").charAt(0).toUpperCase();
    li.innerHTML = `
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-semibold">${escapeHtml(initial)}</div>
        <div>
          <p class="font-semibold text-slate-900">${escapeHtml(pilot.fullName || pilot.username)}</p>
        <p class="text-xs text-slate-500">@${escapeHtml(pilot.username || "user")} | ${escapeHtml(displayRankTitle(pilot.rank) || "Rookie")}</p>
      </div>
      </div>
      <div class="text-right">
        <p class="text-sm font-semibold text-slate-900">${formatNumber(pilot.score)}</p>
        <p class="text-xs text-slate-500">${formatNumber(pilot.lessons)} lessons completed</p>
      </div>
    `;
    list.appendChild(li);
  });
}

function renderRecentUsers(items) {
  const tbody = document.getElementById("recent-users");
  if (!tbody) return;
  if (Array.isArray(items)) {
    recentUsersData = items.slice();
  }
  tbody.innerHTML = "";

  const latestUsers = getLatestFiveUsers(recentUsersData);

  if (latestUsers.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-4 text-slate-500">No new signups yet.</td></tr>`;
    return;
  }

  latestUsers.forEach((user, index) => {
    const tr = document.createElement("tr");
    const avatarSrc = user.avatar || DEFAULT_AVATAR;
    const rowId = formatId(user.id || index + 1);
    const profileHref = buildProfileUrl(user.id, user.username);
    tr.className = "hover:bg-slate-50 cursor-pointer border-b border-slate-200 last:border-b-0";
    tr.addEventListener("click", () => {
      window.location.href = profileHref;
    });
    tr.innerHTML = `
      <td class="px-4 py-3 text-center">
        <img src="${escapeHtml(avatarSrc)}" alt="${escapeHtml(user.username || "User")} avatar" class="w-10 h-10 rounded-full object-cover border border-slate-200 mx-auto">
      </td>
      <td class="px-4 py-3 font-semibold text-slate-700 tabular-nums text-center">${rowId}</td>
      <td class="px-4 py-3 text-center">
        <p class="font-semibold text-slate-900">${escapeHtml(user.username || "Unknown")}</p>
      </td>
      <td class="px-4 py-3 text-slate-600 text-center">${escapeHtml(user.fullName || "-")}</td>
      <td class="px-4 py-3 text-slate-600 text-center">${formatAge(user.age)}</td>
      <td class="px-4 py-3 text-slate-600 capitalize text-center">${escapeHtml(formatGender(user.gender))}</td>
      <td class="px-4 py-3 text-slate-600 text-center">${escapeHtml(user.email || "N/A")}</td>
    `;
    tbody.appendChild(tr);
  });
}

function renderSignupGrowth(series, summary = {}) {
  setText("signup-total", formatNumber(summary.totalUsers));
  updateBadge("signup-week", Number(summary.weekCurrent), formatChange(summary.weekCurrent, summary.weekPrevious ?? null));
  updateBadge("signup-month", Number(summary.monthCurrent), formatChange(summary.monthCurrent, summary.monthPrevious ?? null));

  const empty = document.getElementById("signup-growth-empty");
  const canvas = document.getElementById("signup-growth-chart");
  if (!canvas || typeof Chart === "undefined") {
    return;
  }

  const sortedSeries = Array.isArray(series)
    ? series
        .filter((item) => item && item.date)
        .slice()
        .sort((a, b) => {
          const aDate = new Date(a.date);
          const bDate = new Date(b.date);
          return aDate - bDate;
        })
    : [];

  const labels = sortedSeries.map((item) => formatShortDate(item.date));
  const counts = sortedSeries.map((item) => Number(item.count) || 0);

  if (empty) {
    empty.classList.toggle("hidden", labels.length > 0);
  }

  if (signupGrowthChart) {
    signupGrowthChart.destroy();
    signupGrowthChart = null;
  }

  if (labels.length === 0) {
    return;
  }

  const ctx = canvas.getContext("2d");
  const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
  gradient.addColorStop(0, "rgba(14, 165, 233, 0.35)");
  gradient.addColorStop(1, "rgba(99, 102, 241, 0.05)");

  signupGrowthChart = new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Signups",
          data: counts,
          borderColor: "#0ea5e9",
          backgroundColor: gradient,
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointRadius: 0,
          pointHoverRadius: 4,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `Signups: ${formatNumber(context.parsed.y)}`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: "#94a3b8" },
        },
        y: {
          beginAtZero: true,
          grid: { color: "rgba(148, 163, 184, 0.25)", drawBorder: false },
          ticks: { color: "#94a3b8" },
        },
      },
      interaction: {
        mode: "index",
        intersect: false,
      },
    },
  });
}

function wirePasswordForm() {
  const form = document.getElementById("password-form");
  if (!form) return;

  const currentInput = form.querySelector("#password-current");
  const newInput = form.querySelector("#password-new");
  const confirmInput = form.querySelector("#password-confirm");
  const submitBtn = document.getElementById("password-submit") || form.querySelector("button[type='submit']");

  const setBusy = (state) => {
    if (!submitBtn) return;
    submitBtn.disabled = state;
    submitBtn.textContent = state ? "Updating..." : "Update password";
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const current = currentInput?.value || "";
    const next = newInput?.value || "";
    const confirm = confirmInput?.value || "";

    const validationError = validatePasswordPayload(current, next, confirm);
    if (validationError) {
      notifyPassword(validationError, "error");
      return;
    }

    setBusy(true);
    try {
      const formData = new FormData();
      formData.append("currentPassword", current);
      formData.append("newPassword", next);
      formData.append("confirmPassword", confirm);

      const response = await fetch(PASSWORD_CHANGE_ENDPOINT, {
        method: "POST",
        credentials: "same-origin",
        body: formData,
      });
      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.status !== "success") {
        const message = (payload?.errors && payload.errors[0]) || payload?.message || "Unable to update password.";
        notifyPassword(message, "error");
        return;
      }

      notifyPassword(payload.message || "Password updated successfully.", "success");
      form.reset();
    } catch (error) {
      console.error("Password update failed", error);
      notifyPassword("Unable to update password right now.", "error");
    } finally {
      setBusy(false);
    }
  });
}

function validatePasswordPayload(current, next, confirm) {
  if (!current || !next || !confirm) {
    return "Please fill in all password fields.";
  }
  if (next.length < 8) {
    return "New password must be at least 8 characters.";
  }
  if (next !== confirm) {
    return "New password and confirmation do not match.";
  }
  if (next === current) {
    return "New password must be different from your current password.";
  }
  return null;
}

function notifyPassword(message, type = "info") {
  if (typeof showToast === "function") {
    const icon = type === "success" ? "checkmark-circle" : "alert-circle";
    showToast(`<ion-icon name="${icon}"></ion-icon>${message}`, type);
  } else {
    alert(message);
  }
}

function getLatestFiveUsers(users = []) {
  return users
    .slice()
    .sort((a, b) => {
      const aTime = Date.parse(a?.joined) || 0;
      const bTime = Date.parse(b?.joined) || 0;
      return bTime - aTime;
    })
    .slice(0, 5);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function formatAge(value) {
  if (value === null || value === undefined || value === "") return "-";
  const num = Number(value);
  return Number.isFinite(num) ? `${num}` : "-";
}

function formatId(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) return "-";
  return String(num).padStart(3, "0");
}

function formatGender(value) {
  const normalized = (value || "").toLowerCase();
  if (!normalized) return "-";
  if (normalized === "prefer-not") return "Prefer not";
  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function buildProfileUrl(id = "", username = "") {
  if (id) {
    return `../HTML/adminViewProfile.html?id=${encodeURIComponent(id)}`;
  }
  return `../HTML/adminViewProfile.html?user=${encodeURIComponent(username || "")}`;
}
