<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>CodeFly | Lesson Detail</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css" />
  <link rel="stylesheet" href="../CSS/home.css" />
  <link rel="stylesheet" href="../CSS/popup.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    .hero-banner {
      background: linear-gradient(120deg, #1fa2ff, #6a68f2, #9b5cf6);
      color: #fff;
      border-radius: 28px;
      padding: 32px 32px 36px;
      box-shadow: 0 22px 48px rgba(79, 70, 229, 0.25);
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .hero-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-left: auto;
    }
    .hero-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 999px;
      font-weight: 700;
      border: 2px solid rgba(255, 255, 255, 0.8);
      color: #1e293b;
      background: #fff;
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.25);
      transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
    }
    .hero-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.28);
    }
    .hero-btn.secondary {
      background: transparent;
      color: #fff;
      box-shadow: none;
      border-color: rgba(255, 255, 255, 0.6);
    }
    .hero-meta {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.92);
    }
    .hero-meta .breadcrumb-module {
      font-weight: 600;
    }
    .hero-back-btn {
      width: 40px;
      height: 40px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1.5px solid rgba(255, 255, 255, 0.9);
      color: #fff;
      background: transparent;
      transition: background 0.2s ease, color 0.2s ease, transform 0.15s ease;
    }
    .hero-back-btn:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-1px);
    }
    .hero-meta .divider {
      opacity: 0.7;
    }
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.4rem 0.9rem;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.75rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      background: #e0e7ff;
      color: #312e81;
    }
    .card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 1.5rem;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    }
    .video-layout {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
      align-items: start;
    }
    @media (min-width: 960px) {
      .video-layout {
        grid-template-columns: 1.5fr 1fr;
      }
    }
    .outcome-list li {
      display: flex;
      gap: 0.65rem;
      align-items: flex-start;
      padding: 0.5rem 0;
      border-bottom: 1px solid #e2e8f0;
      font-weight: 600;
      color: #0f172a;
    }
    .outcome-list li:last-child { border-bottom: none; }
    .outcome-list i { color: #2563eb; font-size: 1.1rem; margin-top: 0.1rem; }
    .muted-note { color: #64748b; font-style: italic; }
  </style>
</head>

<body class="bg-slate-50 min-h-screen overflow-x-hidden">
  <div id="toastBox"></div>

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 flex gap-6">
      <div class="flex-1 flex flex-col gap-6">
        <section class="max-w-6xl mx-auto w-full space-y-8">
          <!-- Hero -->
          <div class="hero-banner">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
              <div class="space-y-4 max-w-2xl">
                <nav class="flex items-center gap-2 text-sm text-white/80 uppercase tracking-wide" aria-label="Breadcrumb">
                  <a href="ViewLesson.php" id="breadcrumbModuleLink" class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-white/40 hover:bg-white/15 transition" aria-label="Back to module overview">
                    <i class='bx bx-arrow-back text-base text-white'></i>
                  </a>
                  <a href="ViewLesson.php" id="breadcrumbModuleLabel" class="hover:text-white">Module</a>
                  <span class="text-white/60">/</span>
                  <span id="breadcrumbMissionLabel" class="font-semibold text-white">Lesson</span>
                </nav>
                <h1 id="heroTitle" class="text-3xl font-bold leading-tight text-white">Lesson title</h1>
                <p id="heroDescription" class="text-white/80 text-sm lg:text-base">
                  Lesson description will appear here.
                </p>
              </div>
              <div class="flex gap-3">
                <button id="watchMissionBtn" class="px-5 py-3 rounded-full bg-white text-slate-900 font-semibold shadow-lg shadow-slate-900/25 hover:-translate-y-0.5 transition">
                  Watch lesson
                </button>
              </div>
            </div>
          </div>

          <!-- Video + Outcomes -->
          <div class="video-layout">
            <div id="lessonVideo" class="card p-4 lg:p-6 space-y-4">
              <div class="rounded-2xl overflow-hidden shadow-lg border border-slate-100">
                <iframe
                  id="lessonVideoFrame"
                  class="w-full h-[360px] lg:h-[420px]"
                  src=""
                  title="CodeFly Lesson Video"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen></iframe>
              </div>
            </div>

            <div id="learnCol" class="flex flex-col gap-4 h-full">
              <div id="learnCard" class="card p-6 space-y-4 h-full flex flex-col">
                <div class="space-y-2 flex-1">
                  <h2 class="text-2xl font-semibold text-slate-900">What you'll learn</h2>
                  <p id="learnSummary" class="text-slate-600 leading-relaxed">Learn the key ideas for this lesson.</p>
                </div>
                <div class="space-y-3">
                  <p class="text-xs uppercase tracking-wide text-slate-400">Course outcomes</p>
                  <ul id="courseOutcomesList" class="outcome-list text-sm"></ul>
                </div>
                
              </div>
              <button id="startQuizBtn" class="hero-btn text-[1.05rem] px-6" style="background: linear-gradient(120deg, #1fa2ff, #6a68f2); color: #fff; border-color: rgba(255,255,255,0.85); box-shadow: 0 18px 36px rgba(79, 70, 229, 0.25);">
                Start quiz
              </button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </section>

  <script>
    document.addEventListener("DOMContentLoaded", async () => {
      const params = new URLSearchParams(window.location.search);
      const moduleId = parseInt(params.get("module_id") || params.get("id") || "0", 10);
      const lessonIdParam = params.get("lesson_id") || params.get("lesson");
      const breadcrumb = document.getElementById("breadcrumbMissionLabel");
      const breadcrumbModuleLink = document.getElementById("breadcrumbModuleLink");
      const breadcrumbModuleLabel = document.getElementById("breadcrumbModuleLabel");
      const lessonBadge = document.getElementById("lessonBadge");
      const heroTitle = document.getElementById("heroTitle");
      const heroDescription = document.getElementById("heroDescription");
      const learnSummary = document.getElementById("learnSummary");
      const outcomesList = document.getElementById("courseOutcomesList");
      const videoFrame = document.getElementById("lessonVideoFrame");
      const note = document.getElementById("lessonNote");
      const watchBtn = document.getElementById("watchMissionBtn");
      const learnCol = document.getElementById("learnCol");
      const learnCard = document.getElementById("learnCard");
      const videoCard = document.getElementById("lessonVideo");
      const startQuizBtn = document.getElementById("startQuizBtn");
      const showError = (message) => {
        if (heroTitle) heroTitle.textContent = "Lesson not found";
        if (heroDescription) heroDescription.textContent = message;
      };

      const normalizeVideoUrl = (rawUrl) => {
        if (!rawUrl) return "";
        const trimmed = String(rawUrl).trim();
        if (!trimmed) return "";
        try {
          const url = new URL(trimmed, window.location.origin);
          const host = url.hostname.toLowerCase();
          let videoId = "";
          if (host.includes("youtu.be")) {
            videoId = url.pathname.replace("/", "").split("/")[0];
          } else if (host.includes("youtube.com")) {
            if (url.searchParams.get("v")) {
              videoId = url.searchParams.get("v");
            } else if (url.pathname.startsWith("/embed/")) {
              videoId = url.pathname.split("/")[2] || "";
            } else if (url.pathname.startsWith("/shorts/")) {
              videoId = url.pathname.split("/")[2] || "";
            }
          }
          if (videoId) {
            return `https://www.youtube.com/embed/${videoId}`;
          }
        } catch (_) {
          // Fall back to raw URL
        }
        return trimmed;
      };

      const parseOutcomes = (raw) => {
        if (!raw) return [];
        try {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) return parsed.map((item) => String(item)).filter(Boolean);
        } catch (_) {
          // Ignore JSON parse errors
        }
        return String(raw)
          .split(/[\n\r;]+/)
          .map((item) => item.trim())
          .filter(Boolean);
      };

      const applyMission = (lesson, moduleData, lessons = []) => {
        if (!lesson) {
          showError("Lesson is missing. Please return to the module.");
          return;
        }
        document.title = lesson.title ? `CodeFly | ${lesson.title}` : "CodeFly Lesson";
        if (lessonBadge) {
          lessonBadge.textContent = moduleData?.title ? moduleData.title : "Lesson";
        }
        if (breadcrumb) {
          const lessonIndex = Array.isArray(lessons)
            ? lessons.findIndex((l) => String(l.id) === String(lesson.id))
            : -1;
          const labelNumber =
            lessonIndex >= 0 ? `Lesson ${String(lessonIndex + 1).padStart(2, "0")}` : "Lesson";
          breadcrumb.textContent = labelNumber.toUpperCase();
        }
        if (breadcrumbModuleLink) {
          const targetHref = moduleId
            ? `ViewLesson.php?id=${encodeURIComponent(moduleId)}`
            : "ViewLesson.php";
          breadcrumbModuleLink.setAttribute("href", targetHref);
          breadcrumbModuleLabel?.setAttribute("href", targetHref);
        }
        if (breadcrumbModuleLabel) {
          const moduleName = moduleData?.title || "Module";
          breadcrumbModuleLabel.textContent = moduleName;
        }
        if (heroTitle) {
          heroTitle.textContent = lesson.title || "Lesson";
        }
        if (heroDescription) {
          heroDescription.textContent =
            lesson.description ||
            moduleData?.description ||
            "This lesson is still being prepared.";
        }
        if (learnSummary) {
          learnSummary.textContent =
            lesson.what_you_learn ||
            lesson.description ||
            moduleData?.description ||
            "Learn the key ideas for this lesson.";
        }
        if (videoFrame) {
          videoFrame.src = normalizeVideoUrl(lesson.video_url || lesson.video || "");
        }
        if (outcomesList) {
          const outcomes = parseOutcomes(lesson.learning_outcomes);
          outcomesList.innerHTML = outcomes.length
            ? outcomes.map((item) => `<li><i class='bx bx-check-circle'></i><span>${item}</span></li>`).join("")
            : "<li><i class='bx bx-time-five'></i><span>Outcomes will be added soon.</span></li>";
        }
        if (note) {
          note.textContent = lesson.note || "Perfect for beginners with no prior coding experience.";
        }
      };

      const adjustLearnHeight = () => {
        if (!videoCard || !learnCol || !learnCard) return;
        if (window.innerWidth < 960) {
          learnCol.style.height = "auto";
          learnCard.style.height = "auto";
          learnCard.style.flex = "0 0 auto";
          return;
        }
        const videoHeight = videoCard.getBoundingClientRect().height;
        if (videoHeight > 0) {
          learnCol.style.height = `${videoHeight}px`;
          learnCard.style.flex = "1 1 auto";
          learnCard.style.height = "auto";
        }
      };

      const debounce = (fn, delay = 120) => {
        let t;
        return (...args) => {
          clearTimeout(t);
          t = setTimeout(() => fn(...args), delay);
        };
      };

      const debouncedResize = debounce(adjustLearnHeight, 100);

      if (!moduleId) {
        showError("Module id is missing.");
        return;
      }

      const requestFullscreen = (el) => {
        if (!el) return;
        const fn = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
        if (fn) fn.call(el);
      };

      const startPlayback = () => {
        if (!videoFrame) return;
        const currentSrc = videoFrame.getAttribute("src") || "";
        if (currentSrc && !/autoplay=1/.test(currentSrc)) {
          const hasQuery = currentSrc.includes("?");
          const sep = hasQuery ? "&" : "?";
          videoFrame.src = `${currentSrc}${sep}autoplay=1`;
        }
        requestFullscreen(videoFrame);
        document.getElementById("lessonVideo")?.scrollIntoView({ behavior: "smooth" });
      };

      try {
        const resp = await fetch(`../PHP/getModuleWithLessons.php?id=${moduleId}`);
        const data = await resp.json();
        if (!resp.ok || data?.status !== "success") {
          throw new Error(data?.message || "Unable to load module.");
        }
        const moduleData = data.data?.module || {};
        const lessons = data.data?.lessons || [];
        let lesson = null;
        if (lessonIdParam) {
          const idNum = Number(lessonIdParam);
          lesson =
            lessons.find((l) => String(l.id) === String(lessonIdParam)) ||
            lessons.find((l) => l.focus === lessonIdParam) ||
            (Number.isFinite(idNum) ? lessons.find((l) => Number(l.id) === idNum) : null);
        }
        if (!lesson) {
          lesson = lessons[0];
        }
        applyMission(lesson, moduleData, lessons);
        if (watchBtn) {
          watchBtn.addEventListener("click", startPlayback);
        }
        if (startQuizBtn) {
          const query = new URLSearchParams({
            module_id: moduleId || "",
            lesson_id: lesson?.id || "",
          }).toString();
          startQuizBtn.onclick = () => {
            window.location.href = `../HTML/quiz.php?${query}`;
          };
        }
        adjustLearnHeight();
        window.addEventListener("resize", debouncedResize);
      } catch (err) {
        console.error(err);
        showError(err.message || "Unable to load this lesson.");
      }
    });
  </script>
  <script src="../JS/home.js"></script>
  <script src="../JS/popup.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
