// ---------------- SIDEBAR + MODE TOGGLE ----------------
const body = document.querySelector("body"),
  sidebar = document.querySelector(".sidebar"),
  toggle = document.querySelector(".toggle"),
  modeSwitch = document.querySelector(".toggle-switch"),
  modeText = document.querySelector(".mode-text");
const SIDEBAR_STATE_KEY = "codeflySidebarState";

function applySavedSidebarState() {
  if (!sidebar) return;
  const saved = localStorage.getItem(SIDEBAR_STATE_KEY);
  if (saved === "open") {
    sidebar.classList.remove("close");
  } else if (saved === "close") {
    sidebar.classList.add("close");
  }
}

function toggleSidebar() {
  if (!sidebar) return;
  sidebar.classList.toggle("close");
  const isOpen = !sidebar.classList.contains("close");
  localStorage.setItem(SIDEBAR_STATE_KEY, isOpen ? "open" : "close");
}

function applySidebarTooltips() {
  const links = document.querySelectorAll(".sidebar .menu-links .nav-link a");
  links.forEach((link) => {
    const label = link.querySelector(".text.nav-text")?.textContent?.trim() || link.textContent.trim();
    if (label) {
      link.setAttribute("data-tooltip", label);
      link.removeAttribute("title");
    }
  });
}

const RANK_HIERARCHY = ["bronze", "silver", "gold", "ace", "captain", "commander"];
const WORKSPACE_MIN_RANK = "ace";
let profileBundleCache = null;
let profileBundlePromise = null;
let workspaceModalEl = null;
let workspaceModalBodyEl = null;
let pendingWorkspaceHref = "../HTML/workspace.php";
let workspaceLinkListenerAttached = false;

applySavedSidebarState();

if (toggle && sidebar) {
  toggle.addEventListener("click", toggleSidebar);
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


// ✅ Wait until DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  // Sidebar expansion via hover is disabled; only explicit clicks toggle.
  const sidebarLogo = document.querySelector(".sidebar .image-text");
  const sidebarIcons = document.querySelectorAll(".sidebar .menu-links .icon");
  if (sidebarLogo) {
    sidebarLogo.addEventListener("click", () => {
      toggleSidebar();
    });
  }
  applySidebarTooltips();
  // Icons no longer force sidebar open; retain listeners only if future behavior added

  // Highlight active nav link based on current page
  const navLinks = document.querySelectorAll(".sidebar .menu-links .nav-link a");
  // Sub-pages highlight the menu item of the section they belong to.
  const NAV_PARENT = {
    "NewModule.php": "AddModule.php",
    "editModule.php": "AddModule.php",
    "ViewLesson.php": "AddModule.php",
    "ViewLesson-Detail.php": "AddModule.php",
    "quiz.php": "AddModule.php",
    "addUser.php": "manageUser.php",
    "editUser.php": "manageUser.php",
    "adminViewProfile.php": "manageUser.php",
    "Student-ViewLesson.php": "module.php",
    "Student-ViewLesson-Detail.php": "module.php",
    "Student-Quiz.php": "module.php",
    "view-profile.php": "leaderboard.php",
    "profile.php": "account-setting.php",
    "edit-profile.php": "account-setting.php",
  };
  const pageName = window.location.pathname.split("/").pop();
  const currentPage = NAV_PARENT[pageName] || pageName;
  navLinks.forEach((link) => {
    const linkPage = link.getAttribute("href")?.split("/").pop();
    if (linkPage === currentPage) {
      link.parentElement.classList.add("active");
    }
  });

  initNotificationBell();

  // ---------------- PROFILE MENU ----------------
  const profileMenuBtn = document.getElementById("profile-menu-btn");
  const profileMenu = document.getElementById("profile-menu");
  if (profileMenuBtn && profileMenu) {
    profileMenuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle("hidden");
    });
    document.addEventListener("click", (e) => {
      if (
        !profileMenu.contains(e.target) &&
        !profileMenuBtn.contains(e.target)
      ) {
        profileMenu.classList.add("hidden");
      }
    });
  }

  // ---------------- PROFILE PICTURE UPLOAD ----------------
  const profilePicInput = document.getElementById("profilePicInput");
  const profileImage = document.getElementById("profileImage");
  if (profilePicInput && profileImage) {
    profilePicInput.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          profileImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // ---------------- WEATHER WIDGET ----------------
  const weatherCard = document.getElementById("weather-card");
  if (weatherCard) {
    const WEATHER_COORDS = {
      lat: 3.4486,
      lon: 102.4163,
      label: "Temerloh",
    };

    let weatherRequest = 0;
    function fetchWeather(lat, lon, label) {
      // Only the newest request may update the card (a slow earlier one must not overwrite it).
      const requestId = ++weatherRequest;
      // PHP/weather.php calls OpenWeatherMap with the key kept on the server.
      fetch(`../PHP/weather.php?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`, {
        credentials: "same-origin",
      })
        .then((res) => {
          if (!res.ok) throw new Error(`Weather request failed (${res.status})`);
          return res.json();
        })
        .then((data) => {
          if (requestId !== weatherRequest) return;
          const weatherTempEl = document.getElementById("weather-temp");
          const weatherDescEl = document.getElementById("weather-desc");
          const weatherLocationEl = document.getElementById("weather-location");
          const feelsEl = document.getElementById("weather-feels");
          const humidityEl = document.getElementById("weather-humidity");
          const dateEl = document.getElementById("weather-date");
          const windSpeedEl = document.getElementById("wind-speed");
          const windArrowEl = document.getElementById("wind-arrow");

          if (weatherTempEl) {
            weatherTempEl.textContent = Math.round(data.main.temp) + "\u00B0C";
          }
          if (weatherDescEl) {
            weatherDescEl.textContent = data.weather[0].description;
          }
          if (weatherLocationEl) {
            weatherLocationEl.textContent = label || data.name;
          }

          const feelsLike = data.main?.feels_like;
          const humidity = data.main?.humidity;

          if (feelsEl && typeof feelsLike === "number") {
            feelsEl.textContent = `Feels like ${Math.round(feelsLike)}\u00B0C`;
          }
          if (humidityEl && typeof humidity === "number") {
            humidityEl.textContent = `${humidity}%`;
          }
          if (dateEl) {
            const now = new Date();
            dateEl.textContent = now.toLocaleDateString("en-US", {
              weekday: "short",
              month: "short",
              day: "numeric",
            });
          }
          const windSpeed = data.wind?.speed;
          const windDeg = data.wind?.deg;
          if (windSpeedEl && typeof windSpeed === "number") {
            const windKmh = (windSpeed * 3.6).toFixed(1);
            windSpeedEl.textContent = `${windKmh} km/h`;
          }
          if (windArrowEl && typeof windDeg === "number") {
            windArrowEl.style.transform = `rotate(${windDeg}deg)`;
          }
        })
        .catch(() => {
          if (requestId !== weatherRequest) return;
          const weatherDescEl = document.getElementById("weather-desc");
          if (weatherDescEl) {
            weatherDescEl.textContent = "Failed to load";
          }
        });
    }

    // Weather where the user is: the browser asks for permission the first time and remembers
    // the answer. Until then, or if location is blocked or unavailable, the card shows the
    // default town. Coordinates are rounded to ~1 km before they leave the browser (the
    // weather data covers kilometres anyway, so the result is the same).
    function showDeviceWeather() {
      navigator.geolocation.getCurrentPosition(
        (pos) => fetchWeather(pos.coords.latitude.toFixed(2), pos.coords.longitude.toFixed(2), null),
        () => {}, // keep the default town
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 10 * 60 * 1000 },
      );
    }

    if (!navigator.geolocation) {
      fetchWeather(WEATHER_COORDS.lat, WEATHER_COORDS.lon, WEATHER_COORDS.label);
    } else if (navigator.permissions?.query) {
      navigator.permissions.query({ name: "geolocation" })
        .then((status) => {
          // Already allowed: go straight to the local weather, no default town first.
          if (status.state !== "granted") fetchWeather(WEATHER_COORDS.lat, WEATHER_COORDS.lon, WEATHER_COORDS.label);
          if (status.state !== "denied") showDeviceWeather();
        })
        .catch(() => {
          fetchWeather(WEATHER_COORDS.lat, WEATHER_COORDS.lon, WEATHER_COORDS.label);
          showDeviceWeather();
        });
    } else {
      fetchWeather(WEATHER_COORDS.lat, WEATHER_COORDS.lon, WEATHER_COORDS.label);
      showDeviceWeather();
    }
  }

  // ---------------- LEARNING PROGRESS CARDS ----------------
  const progressRings = document.querySelectorAll("[data-progress-ring]");
  progressRings.forEach((ring, index) => {
    const target = Math.min(Number(ring.dataset.progress) || 0, 100);
    const radius = Number(ring.getAttribute("r")) || 0;
    const circumference =
      Number(ring.getAttribute("stroke-dasharray")) ||
      2 * Math.PI * radius ||
      0;
    ring.style.strokeDasharray = circumference;
    ring.style.strokeDashoffset = circumference;

    const card = ring.closest("[data-progress-card]");
    const label = card?.querySelector("[data-progress-label]");
    const duration = 1100 + index * 120;
    const start = performance.now();

    function animate(now) {
      const progress = Math.min((now - start) / duration, 1);
      const value = Math.round(progress * target);
      if (label) {
        label.textContent = `${value}%`;
      }
      const offset = circumference - (circumference * value) / 100;
      ring.style.strokeDashoffset = offset;
      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    }

    requestAnimationFrame(animate);
  });

  const progressCarousel = document.querySelector("[data-progress-carousel]");
  if (progressCarousel) {
    const cards = Array.from(
      progressCarousel.querySelectorAll("[data-progress-card]")
    );
    if (cards.length > 1) {
      let current = 0;
      const dotsContainer = progressCarousel.querySelector(
        "[data-progress-dots]"
      );
      const dots = cards.map((_, idx) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className =
          "w-2.5 h-2.5 rounded-full bg-gray-300 transition hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300";
        dot.setAttribute("aria-label", `Go to module ${idx + 1}`);
        dot.addEventListener("click", () => {
          current = idx;
          updateCarousel();
        });
        dotsContainer?.appendChild(dot);
        return dot;
      });

      function updateCarousel() {
        cards.forEach((card, idx) => {
          const isActive = idx === current;
          card.classList.toggle("hidden", !isActive);
          card.setAttribute("aria-hidden", isActive ? "false" : "true");
        });
        dots.forEach((dot, idx) => {
          dot.classList.toggle("bg-gray-900", idx === current);
          dot.classList.toggle("bg-gray-300", idx !== current);
        });
      }

      updateCarousel();
    }
  }

  // ---------------- ADVANCED MODULE BARS ----------------
  const advancedBarGroups = document.querySelectorAll("[data-advanced-bars]");
  advancedBarGroups.forEach((group) => {
    const bars = group.querySelectorAll("[data-bar-value]");
    bars.forEach((bar, index) => {
      const value = Math.min(Number(bar.dataset.barValue) || 0, 100);
      setTimeout(() => {
        bar.style.height = value + "%";
      }, index * 120);
    });
  });

  // ---------------- HOME MODULE GRID (dynamic) ----------------
  hydrateHomeModules();

  // ---------------- LEADERBOARD SORTING + FILTERING ----------------
const leaderboardTable = document.getElementById("leaderboard-table");
const searchInput = document.getElementById("searchInput");
const rankFilters = document.getElementById("rankFilters");

if (leaderboardTable && searchInput && rankFilters) {
  const tbody = leaderboardTable.querySelector("tbody");

  // Sort rows by score (descending)
  function sortRows(rows) {
    return rows.sort((a, b) => {
      const scoreA = parseInt(a.cells[3].textContent.trim()) || 0;
      const scoreB = parseInt(b.cells[3].textContent.trim()) || 0;
      return scoreB - scoreA;
    });
  }

  function refreshLeaderboard() {
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const sorted = sortRows(rows);
    tbody.innerHTML = "";
    sorted.forEach((row, i) => {
      row.cells[0].textContent = i + 1;
      tbody.appendChild(row);
    });
  }
  refreshLeaderboard();

  // Active filter
  let activeRank = "all";

  function applyFilters() {
    const searchValue = searchInput.value.toLowerCase();
    const rows = Array.from(tbody.querySelectorAll("tr"));
    rows.forEach((row) => {
      const username = row.cells[2].textContent.toLowerCase();
      const rankText = row.cells[4].textContent.toLowerCase();
      const matchSearch = username.includes(searchValue);
      const matchRank = activeRank === "all" || rankText.includes(activeRank);
      row.style.display = matchSearch && matchRank ? "" : "none";
    });
  }

  // Filter button click
  rankFilters.addEventListener("click", (e) => {
    if (e.target.classList.contains("filter-btn")) {
      document.querySelectorAll(".filter-btn").forEach((btn) => {
        btn.classList.remove("bg-blue-500", "text-white");
        btn.classList.add("bg-gray-200", "hover:bg-gray-300");
      });
      e.target.classList.add("bg-blue-500", "text-white");
      e.target.classList.remove("bg-gray-200", "hover:bg-gray-300");
      activeRank = e.target.dataset.rank;
      applyFilters();
    }
  });

  // Search typing
  searchInput.addEventListener("keyup", applyFilters);
}

  // ---------------- MODULE COVER IMAGE PREVIEW ----------------
  const uploadCoverBtn = document.getElementById("uploadCoverBtn");
  const coverInput = document.getElementById("coverInput");
  const coverImage = document.getElementById("coverImage");

  if (uploadCoverBtn && coverInput && coverImage) {
    uploadCoverBtn.addEventListener("click", () => coverInput.click());

    coverInput.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          coverImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

});

// ---------------- HOME PAGE SEARCH NAVIGATION ----------------
document.addEventListener("DOMContentLoaded", () => {
  initHomeSearch();

  initWorkspaceAccessGate();
  handleWorkspaceRedirectNotice();
  hydrateProfileCard();
});

function hydrateProfileCard() {
  const nameEl = document.getElementById("profile-name");
  const heroWelcomeEl = document.getElementById("hero-welcome");
  if (!nameEl) return;

  fetchProfileBundle()
    .then((data) => {
      if (!data) return;
      const handleEl = document.getElementById("profile-handle");
      const avatarEl = document.getElementById("profile-avatar");
      const rankTitleEl = document.getElementById("profile-rank-title");
      const rankNoteEl = document.getElementById("profile-rank-note");
      const rankBadgeEl = document.getElementById("profile-rank-badge");
      const lessonsEl = document.querySelector('[data-profile-stat="lessons"]');
      const streakEl = document.querySelector('[data-profile-stat="streak"]');
      const xpEl = document.querySelector('[data-profile-stat="xp"]');

      const displayName = data.fullName || data.username || "Pilot";
      nameEl.textContent = displayName;
      if (heroWelcomeEl) {
        heroWelcomeEl.textContent = `Welcome back, ${greetingName(displayName)}!`;
      }
      renderXpProgress(data);
      renderWorkspaceButton(data);
      if (handleEl && data.username) {
        handleEl.textContent = `@${data.username}`;
      }
      if (avatarEl && data.avatar) {
        avatarEl.src = data.avatar;
      }

      const rank = data.rank || {};
      if (rankTitleEl && rank.title) {
        rankTitleEl.textContent = displayRankTitle(rank.title);
      }
      if (rankNoteEl && rank.note) {
        rankNoteEl.textContent = rank.note;
      }
      if (rankBadgeEl && rank.badge) {
        rankBadgeEl.src = rank.badge;
      }
      if (lessonsEl && typeof data.lessons === "number") {
        lessonsEl.textContent = data.lessons.toLocaleString();
      }
      if (streakEl && typeof data.streak === "number") {
        streakEl.textContent = `${data.streak}d`;
      }
      if (xpEl && typeof data.xp === "number") {
        xpEl.textContent = data.xp.toLocaleString();
      }
    })
    .catch((err) => {
      console.error("Unable to load profile info", err);
    });
}

/** "MOHAMAD SHAH FITRI" -> "Shah": first given name, skipping Muhammad/Mohd-style prefixes. */
function greetingName(fullName) {
  const prefixes = ["muhammad", "muhamad", "mohamad", "mohammad", "mohamed", "mohammed", "mohd", "muhd", "md"];
  const words = String(fullName || "").trim().split(/\s+/).filter(Boolean);
  const first = words.find((w) => !prefixes.includes(w.toLowerCase().replace(/\.$/, ""))) || words[0] || "Pilot";
  return first.charAt(0).toUpperCase() + first.slice(1).toLowerCase();
}

/** Profile card bar: XP earned towards the next rank (from profileCard.php nextRank). */
function renderXpProgress(data) {
  const wrap = document.getElementById("profile-xp");
  const next = data?.nextRank;
  if (!wrap || typeof data?.xp !== "number") return;
  const label = document.getElementById("profile-xp-label");
  const count = document.getElementById("profile-xp-count");
  const fill = document.getElementById("profile-xp-fill");
  const bar = document.getElementById("profile-xp-bar");

  let percent = 100;
  if (next) {
    const span = Math.max(1, next.minXp - next.fromXp);
    percent = Math.max(0, Math.min(100, Math.round(((data.xp - next.fromXp) / span) * 100)));
    label.textContent = `Next: ${displayRankTitle(next.key)}`;
    count.textContent = `${data.xp.toLocaleString()} / ${next.minXp.toLocaleString()} XP`;
  } else {
    label.textContent = "Top rank reached";
    count.textContent = `${data.xp.toLocaleString()} XP`;
  }
  fill.style.width = `${percent}%`;
  bar.setAttribute("aria-valuenow", String(percent));
  wrap.classList.remove("hidden");
}

/** Show the workspace button as locked (with the XP still needed) until the gate allows entry. */
function renderWorkspaceButton(data) {
  const btn = document.getElementById("home-workspace-btn");
  const access = data?.workspace;
  if (!btn || !access || access.allowed) return;
  btn.classList.remove("bg-purple-100", "text-purple-700");
  btn.classList.add("bg-slate-100", "text-slate-500");
  document.getElementById("home-workspace-icon").className = "bx bx-lock-alt";
  document.getElementById("home-workspace-label").textContent =
    `Workspace · ${access.xpRemaining.toLocaleString()} XP to unlock`;
  btn.title = `Unlocks at ${displayRankTitle(access.requiredRank)} (${access.requiredXp.toLocaleString()} XP)`;
}

function fetchProfileBundle() {
  if (profileBundleCache) {
    return Promise.resolve(profileBundleCache);
  }
  if (profileBundlePromise) {
    return profileBundlePromise;
  }

  profileBundlePromise = fetch("../PHP/profileCard.php", {
    credentials: "same-origin",
    cache: "no-store",
  })
    .then((res) => {
      if (res && res.status === 401) {
        window.location.href = "../index.html";
        throw new Error("Unauthorized");
      }
      return res.json();
    })
    .then((payload) => {
      if (!payload || payload.status !== "success") {
        throw new Error(payload?.message || "Unable to load profile info.");
      }
      profileBundleCache = payload.data || {};
      return profileBundleCache;
    })
    .finally(() => {
      profileBundlePromise = null;
    });

  return profileBundlePromise;
}

function initWorkspaceAccessGate() {
  if (workspaceLinkListenerAttached) {
    return;
  }

  workspaceLinkListenerAttached = true;
  document.addEventListener(
    "click",
    (event) => {
      const anchor = event.target.closest
        ? event.target.closest("a[href*='workspace.php']")
        : null;
      if (!anchor) {
        return;
      }

      const href = anchor.getAttribute("href") || "../HTML/workspace.php";
      const isModifiedClick =
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey;

      event.preventDefault();
      if (!isModifiedClick) {
        attemptWorkspaceEntry(href);
      } else {
        // Even modified clicks should be gated; keep the modal consistent
        attemptWorkspaceEntry(href);
      }
    },
    true
  );
}

function attemptWorkspaceEntry(targetHref = "../HTML/workspace.php") {
  pendingWorkspaceHref = targetHref;
  showWorkspaceModal("loading");

  fetchProfileBundle()
    .then((data) => {
      const isAdmin = (data?.level || data?.role || "").toString().toLowerCase() === "admin";
      const rankTitle = data?.rank?.title || "Rookie";
      // profileCard.php applies the same rule as the workspace.php gate.
      const allowed = data?.workspace ? data.workspace.allowed : isAdmin || isRankEligible(rankTitle);
      if (allowed) {
        showWorkspaceModal("allowed", { rankTitle });
      } else {
        showWorkspaceModal("blocked", { rankTitle, xp: data?.xp, workspace: data?.workspace });
      }
    })
    .catch(() => {
      showWorkspaceModal("error");
    });
}

function showWorkspaceModal(state, context = {}) {
  const overlay = ensureWorkspaceModal();
  overlay.classList.remove("hidden");
  overlay.classList.add("flex");
  renderWorkspaceModalState(state, context);
}

function hideWorkspaceModal() {
  if (!workspaceModalEl) return;
  workspaceModalEl.classList.add("hidden");
  workspaceModalEl.classList.remove("flex");
}

function ensureWorkspaceModal() {
  if (workspaceModalEl) {
    return workspaceModalEl;
  }

  workspaceModalEl = document.createElement("div");
  workspaceModalEl.id = "workspaceGateModal";
  workspaceModalEl.className =
    "fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/70 px-6";
  workspaceModalEl.innerHTML = `
      <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 text-center">
        <button type="button" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600" data-modal-close aria-label="Close workspace notice">
          <i class='bx bx-x text-2xl'></i>
        </button>
        <div id="workspace-modal-body" class="space-y-4"></div>
      </div>
    `;

  document.body.appendChild(workspaceModalEl);
  workspaceModalBodyEl = workspaceModalEl.querySelector("#workspace-modal-body");

  workspaceModalEl.addEventListener("click", (event) => {
    if (event.target === workspaceModalEl) {
      hideWorkspaceModal();
    }
  });

  const closeBtn = workspaceModalEl.querySelector("[data-modal-close]");
  if (closeBtn) {
    closeBtn.addEventListener("click", hideWorkspaceModal);
  }

  return workspaceModalEl;
}

function renderWorkspaceModalState(state, context = {}) {
  if (!workspaceModalBodyEl) {
    ensureWorkspaceModal();
  }
  if (!workspaceModalBodyEl) {
    return;
  }

  if (state === "loading") {
    workspaceModalBodyEl.innerHTML = `
      <div class="flex flex-col items-center gap-4 text-slate-600">
        <div class="w-16 h-16 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
        <p class="font-semibold">Checking your flight clearance...</p>
      </div>
    `;
    return;
  }

  if (state === "allowed") {
    const rankKey = context.rankTitle || "Ace";
    const rankDisplay = displayRankTitle(rankKey) || "Rookie";
    const rankBadgeMap = {
      bronze: "RankBronze.png",
      silver: "RankSilver.png",
      gold: "RankGold.png",
      ace: "RankAce.png",
      captain: "RankCaptain.png",
      commander: "RankCommander.png",
    };
    const badgeSrc = rankBadgeMap[rankKey.toLowerCase()] || "RankBronze.png";
    workspaceModalBodyEl.innerHTML = `
      <div class="flex flex-col gap-6 text-slate-700">
        <div class="flex flex-col items-center gap-3 text-center">
          <img src="../Assets/${badgeSrc}" alt="${rankDisplay} badge" class="w-16 h-16 object-contain">
          <div>
            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Access granted</p>
            <h3 class="text-2xl font-semibold text-slate-900">Choose your lesson drone</h3>
            <p class="text-sm mt-2">Your ${rankDisplay} rank clears you for Code Block. Select your platform to continue.</p>
          </div>
        </div>
        <div class="flex flex-col gap-3">
          <button type="button" class="flex items-center justify-between w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-3 text-left opacity-50 cursor-not-allowed">
            <div>
              <p class="font-semibold text-slate-900">DJI Drone</p>
              <p class="text-xs text-slate-500">Multi-model support (coming soon)</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">Not released</span>
          </button>
          <button type="button" data-drone="tello" class="flex items-center justify-between w-full rounded-2xl border-2 border-blue-500/70 bg-blue-50 px-4 py-3 text-left hover:bg-blue-100 transition focus-visible:ring-2 focus-visible:ring-blue-300">
            <div>
              <p class="font-semibold text-slate-900">DJI Tello</p>
              <p class="text-xs text-slate-500">CodeFly classroom fleet</p>
            </div>
            <span class="text-xs font-semibold text-blue-600">Available</span>
          </button>
          <button type="button" class="flex items-center justify-between w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-3 text-left opacity-50 cursor-not-allowed">
            <div>
              <p class="font-semibold text-slate-900">Custom Drones</p>
              <p class="text-xs text-slate-500">Bring your own hardware (coming soon)</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">Not released</span>
          </button>
        </div>
        <p class="text-xs text-slate-500 text-center">Only Elite Pilot rank and above can access the workspace.</p>
      </div>
    `;
    const telloBtn = workspaceModalBodyEl.querySelector('[data-drone="tello"]');
    if (telloBtn) {
      telloBtn.addEventListener("click", () => {
        hideWorkspaceModal();
        window.location.href = pendingWorkspaceHref;
      });
    }
    return;
  }

  if (state === "blocked") {
      const rankKey = context.rankTitle || "Bronze";
      const rankLabel = displayRankTitle(rankKey) || "Rookie";
    const rankBadgeMap = {
      bronze: "RankBronze.png",
      silver: "RankSilver.png",
      gold: "RankGold.png",
      ace: "RankAce.png",
      captain: "RankCaptain.png",
      commander: "RankCommander.png",
    };
    const badgeSrc = rankBadgeMap[rankKey.toLowerCase()] || "RankBronze.png";
    workspaceModalBodyEl.innerHTML = `
      <div class="flex flex-col items-center gap-4 text-slate-700">
        <img src="../Assets/${badgeSrc}" alt="${rankLabel} badge" class="w-16 h-16 object-contain">
        <div class="text-center">
          <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Access restricted</p>
          <h3 class="text-2xl font-semibold text-rose-600 mt-1">Flight clearance needed</h3>
          <p class="text-sm mt-2">Code Block and drone flights unlock at <strong>Elite Pilot</strong> or higher. Your current rank: <strong>${displayRankTitle(context.rankTitle) || "Rookie"}</strong>.</p>
          ${context.workspace && typeof context.xp === "number" ? `
          <div class="mt-4 text-left">
            <div class="flex justify-between text-xs font-semibold text-slate-500">
              <span>${context.xp.toLocaleString()} XP</span><span>${context.workspace.requiredXp.toLocaleString()} XP</span>
            </div>
            <div class="mt-1 h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full rounded-full bg-blue-500" style="width:${Math.min(100, Math.round((context.xp / Math.max(1, context.workspace.requiredXp)) * 100))}%"></div>
            </div>
            <p class="text-xs text-slate-500 mt-2">${context.workspace.xpRemaining.toLocaleString()} XP to go. Finish lessons and quizzes to earn XP.</p>
          </div>` : ""}
        </div>
        <div class="w-full text-left bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Minimum XP tiers</p>
          <ul class="mt-2 space-y-1 text-sm text-slate-700">
            <li><strong>Elite Pilot (Ace)</strong> — 1200 XP</li>
            <li><strong>Captain</strong> — 3000 XP</li>
            <li><strong>Commander</strong> — 5000 XP</li>
          </ul>
        </div>
        <button
          type="button"
          data-close-workspace
          class="px-6 py-3 rounded-full bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition"
          style="width: 100%; max-width: 220px;"
        >
          Keep training
        </button>
      </div>
    `;
    const closeBtn = workspaceModalBodyEl.querySelector("[data-close-workspace]");
    if (closeBtn) {
      closeBtn.addEventListener("click", hideWorkspaceModal);
    }
    return;
  }

  workspaceModalBodyEl.innerHTML = `
    <div class="flex flex-col items-center gap-4 text-slate-700">
      <div class="w-16 h-16 rounded-full bg-rose-100 flex items-center justify-center text-rose-500 text-3xl">
        <i class='bx bx-error-circle'></i>
      </div>
      <div>
        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Request failed</p>
        <h3 class="text-2xl font-semibold text-slate-900">Unable to verify rank</h3>
        <p class="text-sm mt-2">We couldn't confirm your clearance. Please try again.</p>
      </div>
      <div class="flex gap-3">
        <button type="button" data-retry-workspace class="px-5 py-3 rounded-full bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
          Try Again
        </button>
        <button type="button" class="px-5 py-3 rounded-full border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition" data-close-workspace>
          Cancel
        </button>
      </div>
    </div>
  `;
  const retryBtn = workspaceModalBodyEl.querySelector("[data-retry-workspace]");
  if (retryBtn) {
    retryBtn.addEventListener("click", () => attemptWorkspaceEntry(pendingWorkspaceHref));
  }
  const cancelBtn = workspaceModalBodyEl.querySelectorAll("[data-close-workspace]");
  cancelBtn.forEach((btn) => btn.addEventListener("click", hideWorkspaceModal));
}

function isRankEligible(rankTitle) {
  if (!rankTitle) {
    return false;
  }
  const normalized = rankTitle.toLowerCase();
  const rankIndex = RANK_HIERARCHY.indexOf(normalized);
  const thresholdIndex = RANK_HIERARCHY.indexOf(WORKSPACE_MIN_RANK);
  if (rankIndex === -1 || thresholdIndex === -1) {
    return false;
  }
  return rankIndex >= thresholdIndex;
}

function handleWorkspaceRedirectNotice() {
  const url = new URL(window.location.href);
  const workspaceFlag = url.searchParams.get("workspace");
  if (!workspaceFlag) {
    return;
  }

  url.searchParams.delete("workspace");
  window.history.replaceState({}, document.title, url.toString());

  if (workspaceFlag === "denied") {
    fetchProfileBundle()
      .then((data) => {
        showWorkspaceModal("blocked", { rankTitle: data?.rank?.title || "Rookie", xp: data?.xp, workspace: data?.workspace });
      })
      .catch(() => {
        showWorkspaceModal("blocked");
      });
  } else if (workspaceFlag === "error") {
    showWorkspaceModal("error");
  }
}

// ---------------- NOTIFICATION BELL (sidebar) ----------------
const NOTIF_POLL_MS = 30000;
const NOTIF_STYLES = {
  module: { icon: "bx-book-open", classes: "bg-sky-50 text-sky-600" },
  quiz: { icon: "bx-check-circle", classes: "bg-emerald-50 text-emerald-600" },
  rank: { icon: "bx-medal", classes: "bg-violet-50 text-violet-600" },
  streak: { icon: "bx-calendar-check", classes: "bg-amber-50 text-amber-600" },
  user: { icon: "bx-user-plus", classes: "bg-sky-50 text-sky-600" },
};
let notificationItems = [];

function initNotificationBell() {
  const btn = document.getElementById("notif-btn");
  const panel = document.getElementById("notif-panel");
  if (!btn || !panel) return;

  const isOpen = () => !panel.classList.contains("hidden");
  const close = () => {
    panel.classList.add("hidden");
    btn.setAttribute("aria-expanded", "false");
  };
  const open = () => {
    renderNotifications();
    panel.classList.remove("hidden");
    btn.setAttribute("aria-expanded", "true");
    positionNotificationPanel(btn, panel);
    // Opening counts as reading; the "New" highlight stays until the next refresh.
    if (notificationItems.some((item) => !item.isRead)) markNotificationsRead();
  };

  btn.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    isOpen() ? close() : open();
  });
  document.getElementById("notif-close")?.addEventListener("click", close);
  document.addEventListener("click", (e) => {
    if (isOpen() && !panel.contains(e.target) && !btn.contains(e.target)) close();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && isOpen()) {
      close();
      btn.focus();
    }
  });
  window.addEventListener("resize", () => isOpen() && positionNotificationPanel(btn, panel));

  loadNotifications();
  setInterval(() => {
    if (!document.hidden && !isOpen()) loadNotifications();
  }, NOTIF_POLL_MS);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden && !isOpen()) loadNotifications();
  });
}

/** Next to the sidebar, level with the bell; full width on phones where that would be too narrow. */
function positionNotificationPanel(btn, panel) {
  const gap = 12;
  const sidebarRight = document.querySelector(".sidebar")?.getBoundingClientRect().right || 0;
  let left = sidebarRight + gap;
  if (window.innerWidth - left - gap < 280) left = gap;
  panel.style.left = `${left}px`;
  panel.style.width = `${Math.min(384, window.innerWidth - left - gap)}px`;
  const top = btn.getBoundingClientRect().top;
  const maxTop = window.innerHeight - panel.offsetHeight - gap;
  panel.style.top = `${Math.max(gap, Math.min(top, maxTop))}px`;
}

function loadNotifications() {
  return fetch("../PHP/notifications.php", { credentials: "same-origin", cache: "no-store" })
    .then((res) => res.json())
    .then((payload) => {
      if (!payload || payload.status !== "success") return;
      notificationItems = Array.isArray(payload.data) ? payload.data : [];
      updateNotificationBadge();
      const panel = document.getElementById("notif-panel");
      if (panel && !panel.classList.contains("hidden")) renderNotifications();
    })
    .catch((err) => console.warn("Unable to load notifications", err));
}

function updateNotificationBadge() {
  const badge = document.getElementById("notif-badge");
  const btn = document.getElementById("notif-btn");
  const unread = notificationItems.filter((item) => !item.isRead).length;
  if (badge) {
    badge.textContent = unread > 9 ? "9+" : String(unread);
    badge.classList.toggle("hidden", unread === 0);
  }
  btn?.setAttribute("aria-label", unread ? `Notifications, ${unread} unread` : "Notifications");
}

function renderNotifications() {
  const list = document.getElementById("notif-list");
  const sub = document.getElementById("notif-count-sub");
  if (!list) return;

  const unread = notificationItems.filter((item) => !item.isRead).length;
  if (sub) sub.textContent = unread ? `${unread} new` : "You're all caught up";

  list.innerHTML = "";
  if (!notificationItems.length) {
    list.innerHTML = `<li class="text-gray-400 text-xs text-center py-4">No notifications yet.</li>`;
    return;
  }

  notificationItems.forEach((item) => {
    const li = document.createElement("li");
    const url = item.meta?.url;
    // Only our own pages: the server sets these, but never follow anything else.
    const entry = document.createElement(typeof url === "string" && url.startsWith("../HTML/") ? "a" : "div");
    if (entry.tagName === "A") entry.href = url;
    entry.className = `notif-item${item.isRead ? "" : " notif-item--unread"}`;

    const style = NOTIF_STYLES[item.type] || { icon: "bx-bell", classes: "bg-slate-100 text-slate-500" };
    const icon = document.createElement("span");
    icon.className = `notif-item__icon ${style.classes}`;
    icon.innerHTML = `<i class="bx ${style.icon}" aria-hidden="true"></i>`;

    const body = document.createElement("div");
    body.className = "flex-1 min-w-0";
    const message = document.createElement("p");
    message.className = `leading-snug text-slate-800 ${item.isRead ? "" : "font-semibold"}`;
    message.textContent = item.message || "Notification";
    const time = document.createElement("p");
    time.className = "text-xs text-slate-500 mt-0.5";
    time.textContent = formatNotificationTime(item.timestamp, { includeRelative: true });
    body.append(message, time);

    entry.append(icon, body);
    if (!item.isRead) {
      const pill = document.createElement("span");
      pill.className = "text-[10px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full shrink-0";
      pill.textContent = "New";
      entry.appendChild(pill);
    }
    li.appendChild(entry);
    list.appendChild(li);
  });
}

function markNotificationsRead() {
  fetch("../PHP/notifications.php", { method: "POST", credentials: "same-origin", cache: "no-store" })
    .then((res) => {
      if (!res.ok) return;
      // Clear the badge now but keep the rendered "New" highlights for this viewing.
      const badge = document.getElementById("notif-badge");
      badge?.classList.add("hidden");
      document.getElementById("notif-btn")?.setAttribute("aria-label", "Notifications");
      notificationItems = notificationItems.map((item) => ({ ...item, isRead: true }));
    })
    .catch(() => {});
}

function formatNotificationTime(timestamp, options = {}) {
  const { includeRelative = false } = options;
  // MySQL sends "YYYY-MM-DD HH:MM:SS"; the "T" form parses in every browser (Safari rejects the space).
  const date = new Date(String(timestamp || "").replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return "";
  if (includeRelative) {
    const diffMs = Date.now() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    if (diffMinutes < 1) return "Just now";
    if (diffMinutes < 60) return `${diffMinutes} min ago`;
    if (diffHours < 24) return `${diffHours} hr${diffHours > 1 ? "s" : ""} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? "s" : ""} ago`;
  }
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

// ---------------- HOME MODULES FROM DB ----------------
function hydrateHomeModules() {
  const grid = document.getElementById("home-module-grid");
  if (!grid) return;

  fetch("../PHP/listModules.php", {
    credentials: "same-origin",
    cache: "no-store",
  })
    .then((res) => res.json())
    .then((payload) => {
      if (!payload || payload.status !== "success") {
        throw new Error(payload?.message || "Unable to load modules.");
      }
      const modules = Array.isArray(payload.data) ? payload.data : [];
      homeSearchIndex = buildHomeSearchIndex(modules);
      renderContinueCard(modules);

      if (!modules.length) {
        grid.innerHTML = `
          <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100 text-center text-slate-500">
            No modules available yet.
          </div>
        `;
        return;
      }

      // "Next up" goes to the first unlocked module that is not finished yet.
      const nextUpIndex = modules.findIndex((mod) => !isModuleLocked(mod) && moduleProgress(mod).percent < 100);

      grid.innerHTML = modules
        .map((mod, idx) => {
          const { total, done, flags, percent } = moduleProgress(mod);
          const locked = isModuleLocked(mod);
          const status = locked
            ? "locked"
            : percent === 100
              ? "completed"
              : idx === nextUpIndex
                ? "next"
                : done > 0
                  ? "progress"
                  : "new";
          const badge = HOME_MODULE_BADGES[status];
          const lessonTitles = Array.isArray(mod.lessons) ? mod.lessons.map((l, i) => l?.title || `Lesson ${i + 1}`) : [];
          const href = `../HTML/Student-ViewLesson.php?id=${encodeURIComponent(mod.id)}`;
          const tag = locked ? "div" : "a";
          const linkAttrs = locked ? 'aria-disabled="true"' : `href="${href}"`;
          return `
            <${tag} ${linkAttrs} class="group rounded-2xl bg-white shadow-lg p-6 flex flex-col gap-6 border border-gray-100 min-h-[280px] ${locked ? "opacity-70" : "hover:border-sky-300 hover:shadow-xl transition"}" data-home-module-card>
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-gray-500">Course Progress</p>
                <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap text-xs font-semibold px-3 py-1 rounded-full ${badge.classes}"><i class="bx ${badge.icon} text-base"></i>${badge.label}</span>
              </div>
              <div class="flex flex-col items-center text-center gap-4 bg-white rounded-2xl p-5 border border-gray-100 shadow-sm min-h-[200px] justify-center">
                <div class="donut-wrapper relative" style="width:120px;height:120px;">
                  ${buildSegmentedDonutSVG(Math.max(total, 1), done, idx, lessonTitles, flags)}
                  <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-xl font-bold text-slate-900 tabular-nums">${percent}%</span>
                    <span class="text-[10px] text-slate-500">${done}/${total} lessons</span>
                  </div>
                </div>
                <h4 class="text-lg font-semibold text-slate-900 group-hover:text-sky-600">${escapeHomeHtml(mod.title || "Module")}</h4>
              </div>
            </${tag}>
          `;
        })
        .join("");
    })
    .catch((err) => {
      console.error(err);
      grid.innerHTML = `
        <div class="rounded-2xl bg-white shadow-lg p-6 border border-gray-100 text-center text-rose-500">
          Unable to load modules.
        </div>
      `;
    });
}

const HOME_MODULE_BADGES = {
  completed: { label: "Completed", icon: "bx-check-circle", classes: "text-emerald-700 bg-emerald-50" },
  next: { label: "Next up", icon: "bx-right-arrow-alt", classes: "text-sky-700 bg-sky-50" },
  progress: { label: "In progress", icon: "bx-time-five", classes: "text-amber-700 bg-amber-50" },
  new: { label: "Not started", icon: "bx-circle", classes: "text-slate-600 bg-slate-100" },
  locked: { label: "Locked", icon: "bx-lock", classes: "text-slate-600 bg-slate-100" },
};

function isModuleLocked(mod) {
  return String(mod?.locked ?? "0") === "1";
}

/** Lesson completion for one module, from listModules.php (lesson.completed = quiz score recorded). */
function moduleProgress(mod) {
  const lessons = Array.isArray(mod?.lessons) ? mod.lessons : [];
  const flags = lessons.map((l) => Boolean(l?.completed));
  const total = lessons.length || Number(mod?.lesson_count) || 0;
  const done = flags.filter(Boolean).length || Number(mod?.completed_lessons) || 0;
  const percent = total ? Math.round((Math.min(done, total) / total) * 100) : 0;
  return { total, done: Math.min(done, total), flags, percent };
}

function escapeHomeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (ch) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[ch]);
}

// ---------------- CONTINUE LEARNING ----------------
function renderContinueCard(modules) {
  const card = document.getElementById("continue-card");
  if (!card) return;
  const titleEl = document.getElementById("continue-title");
  const subEl = document.getElementById("continue-sub");
  const eyebrowEl = document.getElementById("continue-eyebrow");
  const ctaEl = document.getElementById("continue-cta");

  let target = null;
  for (const mod of modules) {
    if (isModuleLocked(mod)) continue;
    const lessons = Array.isArray(mod.lessons) ? mod.lessons : [];
    const index = lessons.findIndex((l) => !l?.completed);
    if (index !== -1) {
      target = { mod, lesson: lessons[index], index, total: lessons.length, started: lessons.some((l) => l?.completed) };
      break;
    }
  }

  if (target) {
    card.href = `../HTML/Student-ViewLesson.php?id=${encodeURIComponent(target.mod.id)}`;
    eyebrowEl.textContent = target.started ? "Continue learning" : "Start learning";
    titleEl.textContent = target.lesson.title || `Lesson ${target.index + 1}`;
    subEl.textContent = `${target.mod.title || "Module"} · Lesson ${target.index + 1} of ${target.total}`;
    if (ctaEl) ctaEl.innerHTML = `${target.started ? "Resume" : "Start"} <i class="bx bx-right-arrow-alt"></i>`;
  } else if (modules.length) {
    card.href = "../HTML/module.php";
    eyebrowEl.textContent = "All missions complete";
    titleEl.textContent = "Great flying, pilot!";
    subEl.textContent = "Review any module or check your ranking.";
    if (ctaEl) ctaEl.innerHTML = `Modules <i class="bx bx-right-arrow-alt"></i>`;
  } else {
    return;
  }
  card.classList.remove("hidden");
  card.classList.add("flex");
}

// ---------------- LESSON SEARCH ----------------
let homeSearchIndex = [];

function buildHomeSearchIndex(modules) {
  const items = [];
  modules.forEach((mod) => {
    const href = `../HTML/Student-ViewLesson.php?id=${encodeURIComponent(mod.id)}`;
    const locked = isModuleLocked(mod);
    items.push({ type: "module", title: mod.title || "Module", sub: mod.subtitle || `${(mod.lessons || []).length} lessons`, text: `${mod.title} ${mod.subtitle} ${mod.description}`, href, locked });
    (mod.lessons || []).forEach((lesson, i) => {
      items.push({
        type: "lesson",
        title: lesson.title || `Lesson ${i + 1}`,
        sub: `${mod.title || "Module"} · Lesson ${i + 1}${lesson.completed ? " · Completed" : ""}`,
        text: `${lesson.title} ${lesson.focus} ${lesson.description}`,
        href,
        locked,
      });
    });
  });
  return items;
}

/** Pages reachable by keyword, kept from the old search ("leaderboard", "settings", ...). */
const HOME_SEARCH_PAGES = [
  { words: ["leaderboard", "rank", "ranking"], title: "Leaderboard", href: "../HTML/leaderboard.php", icon: "bx-trophy" },
  { words: ["workspace", "code", "block", "drone"], title: "Workspace (Code Block)", href: "../HTML/workspace.php", icon: "bx-code-block" },
  { words: ["setting", "settings", "account"], title: "Account settings", href: "../HTML/account-setting.php", icon: "bx-cog" },
  { words: ["profile"], title: "My profile", href: "../HTML/view-profile.php", icon: "bx-user-circle" },
  { words: ["module", "modules"], title: "All modules", href: "../HTML/module.php", icon: "bx-layer" },
];

function searchHome(query) {
  const q = query.toLowerCase().trim();
  if (!q) return [];
  const pages = HOME_SEARCH_PAGES.filter((p) => p.words.some((w) => w.startsWith(q) || q.startsWith(w)))
    .map((p) => ({ type: "page", title: p.title, sub: "Page", href: p.href, icon: p.icon }));
  const content = homeSearchIndex
    .filter((item) => item.text.toLowerCase().includes(q))
    // titles that match come first
    .sort((a, b) => Number(!a.title.toLowerCase().includes(q)) - Number(!b.title.toLowerCase().includes(q)))
    .slice(0, 8);
  return [...content, ...pages];
}

function initHomeSearch() {
  const input = document.getElementById("homeSearchInput");
  const list = document.getElementById("homeSearchResults");
  const box = document.getElementById("homeSearchBox");
  if (!input || !list) return;
  let activeIndex = -1;

  const close = () => {
    list.classList.add("hidden");
    input.setAttribute("aria-expanded", "false");
    activeIndex = -1;
  };

  const render = () => {
    const results = searchHome(input.value);
    activeIndex = -1;
    if (!input.value.trim()) return close();
    list.innerHTML = results.length
      ? results
          .map((r, i) => {
            const icon = r.icon || (r.type === "module" ? "bx-layer" : "bx-book-open");
            const lock = r.locked ? '<i class="bx bx-lock text-slate-400"></i>' : "";
            return `<li role="option" id="home-search-opt-${i}">
              <a href="${r.href}" data-search-result class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-sky-50">
                <i class="bx ${icon} text-xl text-sky-500"></i>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-semibold text-slate-900">${escapeHomeHtml(r.title)}</span>
                  <span class="block truncate text-xs text-slate-500">${escapeHomeHtml(r.sub)}</span>
                </span>${lock}
              </a></li>`;
          })
          .join("")
      : `<li class="px-3 py-3 text-sm text-slate-500">No lessons or modules match "${escapeHomeHtml(input.value.trim())}".</li>`;
    list.classList.remove("hidden");
    input.setAttribute("aria-expanded", "true");
  };

  const links = () => [...list.querySelectorAll("[data-search-result]")];
  const highlight = () => links().forEach((a, i) => a.classList.toggle("bg-sky-50", i === activeIndex));

  input.addEventListener("input", render);
  input.addEventListener("focus", () => input.value.trim() && render());
  input.addEventListener("keydown", (event) => {
    const items = links();
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
      if (!items.length) return;
      event.preventDefault();
      activeIndex = (activeIndex + (event.key === "ArrowDown" ? 1 : -1) + items.length) % items.length;
      highlight();
    } else if (event.key === "Enter") {
      event.preventDefault();
      const chosen = items[activeIndex >= 0 ? activeIndex : 0];
      if (chosen) chosen.click();
    } else if (event.key === "Escape") {
      close();
    }
  });
  document.addEventListener("click", (event) => {
    if (box && !box.contains(event.target)) close();
  });
}

function clampPercent(value) {
  if (!Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(100, Math.round(value)));
}

// Solid pastel of a hex colour (mixed with white), so pending segments never look darker
// where they meet, unlike a translucent colour.
function tintHex(hex, amount = 0.78) {
  const n = parseInt(hex.slice(1), 16);
  const mix = (c) => Math.round(c + (255 - c) * amount);
  const r = mix((n >> 16) & 255);
  const g = mix((n >> 8) & 255);
  const b = mix(n & 255);
  return `#${((1 << 24) | (r << 16) | (g << 8) | b).toString(16).slice(1)}`;
}

// One ring segment per lesson: done lessons in the module colour, the rest in its pastel.
// Flat segment ends with a fixed gap (round ends overlapped the gaps and made dark blobs).
// .donut-chart in home.css rotates it so the first lesson starts at 12 o'clock.
function buildSegmentedDonutSVG(totalMissions = 1, completedMissions = 0, idx = 0, lessonTitles = [], doneFlags = null) {
  const total = Math.max(1, Math.floor(totalMissions));
  const completed = Math.max(0, Math.min(total, Math.floor(completedMissions)));
  const r = 15.915; // circumference = 100, so dash lengths are percentages
  const palette = ["#0ea5e9", "#22c55e", "#f97316", "#ec4899"];
  const doneColor = palette[idx % palette.length];
  const pendingColor = tintHex(doneColor);
  const gap = total > 1 ? Math.min(2.5, (100 / total) * 0.3) : 0;
  const segment = 100 / total - gap;
  const circles = [];

  for (let i = 0; i < total; i += 1) {
    const isDone = Array.isArray(doneFlags) && doneFlags.length === total ? doneFlags[i] : i < completed;
    const lessonName = lessonTitles[i] || `Lesson ${i + 1}`;
    const safeName = lessonName.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
    circles.push(`
      <circle
        class="donut-segment"
        cx="18"
        cy="18"
        r="${r}"
        fill="none"
        stroke="${isDone ? doneColor : pendingColor}"
        stroke-width="4"
        stroke-dasharray="${segment} ${100 - segment}"
        stroke-dashoffset="${-(i * (100 / total) + gap / 2)}"
      >
        <title>${safeName}${isDone ? " (done)" : ""}</title>
      </circle>
    `);
  }

  return `
    <svg class="donut-chart" viewBox="0 0 36 36" role="img" aria-hidden="true" width="120" height="120" preserveAspectRatio="xMidYMid meet">
      ${circles.join("")}
    </svg>
  `;
}


// ---------------- MODULE PAGE SEARCH & FILTER ----------------
document.addEventListener("DOMContentLoaded", () => {
  // Select elements on the module page
  const searchInput = document.getElementById("moduleSearchInput");
  const filterMenu = document.getElementById("level-menu");
  const moduleGrid = document.getElementById("moduleGrid");
  const levelSelectedText = document.getElementById("level-selected");

  // This 'if' check is important!
  // It stops this code from running on other pages (like home.php)
  if (searchInput && filterMenu && moduleGrid) {
    
    // Store the current filter states
    let currentQuery = "";
    let currentFilter = "All Modules"; // Default filter
    let lastNoResultSignature = null;
    
    // 1. Add listener for the search bar (triggers on every key press)
    searchInput.addEventListener("keyup", () => {
      currentQuery = searchInput.value.toLowerCase();
      applyFilters();
    });

    // 2. Add listener for the filter dropdown
    filterMenu.addEventListener("click", (e) => {
      // Check if we clicked an LI tag
      if (e.target.tagName === "LI") {
        const selectedText = e.target.textContent;
        currentFilter = selectedText;
        
        // Update the filter button text
        levelSelectedText.textContent = selectedText;
        
        applyFilters();
      }
    });

    /**
     * This function runs both filters and updates the UI.
     */
    function applyFilters() {
      // Get all the module cards
      const modules = moduleGrid.querySelectorAll(".module-card");
      let visibleCount = 0;

      modules.forEach(module => {
        // Get the title from the card (e.g., "Basics Module")
        const title = module.querySelector("h3").textContent.toLowerCase();

        // Check if it matches the search query
        const matchesSearch = title.includes(currentQuery);

        // Check if it matches the filter
        // We use .includes() so "Basic" matches "Basics Module"
        const matchesFilter = (currentFilter === "All Modules") || 
                              title.includes(currentFilter.toLowerCase().replace(" module", ""));

        // Show or hide the card based on *both* matches
        if (matchesSearch && matchesFilter) {
          module.style.display = ""; // Show
          visibleCount++;
        } else {
          module.style.display = "none"; // Hide
        }
      });

      const signature = `${currentQuery}|${currentFilter}`;
      if (visibleCount === 0) {
        if (typeof showToast === "function" && lastNoResultSignature !== signature) {
          const queryText = currentQuery ? ` for "${searchInput.value.trim()}"` : "";
          const filterText = currentFilter !== "All Modules" ? ` (${currentFilter})` : "";
          const toastMsg = `<ion-icon name="alert-circle"></ion-icon>No modules found${queryText}${filterText}.`;
          showToast(toastMsg, "error");
          lastNoResultSignature = signature;
        }
      } else {
        lastNoResultSignature = null;
      }
    }
  }
});
