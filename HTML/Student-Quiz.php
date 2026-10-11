<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>CodeFly | Quiz</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css" />
  <link rel="stylesheet" href="../CSS/home.css" />
  <link rel="stylesheet" href="../CSS/popup.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    .hero-banner {
      background: linear-gradient(120deg, #1fa2ff, #6a68f2, #9b5cf6);
      color: #fff;
      border-radius: 28px;
      padding: 32px 32px 34px;
      box-shadow: 0 22px 48px rgba(79, 70, 229, 0.25);
      display: flex;
      flex-direction: column;
      gap: 14px;
      position: sticky;
      top: 12px;
      z-index: 5;
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
    .quiz-flow {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .quiz-card {
      background: #fff;
      border-radius: 1.5rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
      padding: 1.5rem;
    }
    .quiz-meta-small {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 700;
      color: #f8fafc;
    }
    .quiz-meta-small i {
      color: #f8fafc;
    }
    .question-shell {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }
    .question-card {
      background: #fff;
      border-radius: 1.25rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 12px 30px rgba(15,23,42,0.06);
      padding: 1.5rem;
    }
    .question-card h3 {
      font-size: 0.95rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #94a3b8;
      margin-bottom: 0.4rem;
    }
    .question-card p {
      color: #0f172a;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .question-card header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .options {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .option {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      padding: 0.9rem 1rem;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      background: #f8fafc;
      font-weight: 600;
      color: #1f2937;
      transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer;
    }
    .option:hover {
      border-color: #c7d2fe;
      box-shadow: 0 12px 24px rgba(99,102,241,0.08);
    }
    .option.selected {
      border-color: #8b5cf6;
      background: #f5f3ff;
      box-shadow: 0 16px 32px rgba(139,92,246,0.16);
    }
    .option .radio {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      border: 2px solid #cbd5e1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .option.selected .radio {
      border-color: #8b5cf6;
      box-shadow: 0 0 0 4px rgba(139,92,246,0.15);
    }
    .option.selected .radio::after {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #8b5cf6;
    }
    .hero-stat {
      display: inline-flex;
      flex-direction: column;
      justify-content: center;
      background: linear-gradient(135deg, rgba(255,255,255,0.22), rgba(255,255,255,0.14));
      color: #fff;
      padding: 0.75rem 1.25rem;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
      min-width: 140px;
      text-align: center;
      gap: 0.25rem;
    }
    .hero-stat .label {
      font-size: 0.75rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      opacity: 0.9;
      font-weight: 700;
    }
    .hero-stat .value {
      font-size: 1.4rem;
      font-weight: 800;
      line-height: 1.1;
    }
    .hero-stats {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      justify-content: flex-start;
      align-items: stretch;
    }
    .hero-actions-inline {
      display: flex;
      justify-content: flex-start;
      width: auto;
    }
    .hero-actions-inline .hero-btn {
      width: 60vw;
      min-width: 300px;
      max-width: 700px;
      justify-content: center;
    }
    /* Result modal styling */
    #quizModal {
      backdrop-filter: blur(6px);
    }
    #quizModal[hidden] {
      display: none;
    }
    .quiz-modal-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 24px 48px rgba(15, 23, 42, 0.16);
      border: 1px solid #e2e8f0;
    }
    .quiz-modal-icon {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }
    @media (min-width: 768px) {
      .hero-stats {
        justify-content: flex-end;
      }
      .hero-actions-inline {
        justify-content: flex-start;
      }
      .hero-actions-inline .hero-btn {
        width: 100%;
      }
      .hero-banner {
        top: 18px;
      }
    }
    .quiz-list li {
      display: flex;
      gap: 0.75rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid #e2e8f0;
      color: #0f172a;
      font-weight: 600;
    }
    .quiz-list li:last-child {
      border-bottom: none;
    }
    .quiz-list i {
      color: #2563eb;
      margin-top: 3px;
    }
    .quiz-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .quiz-card-floating {
      margin-top: 12px;
    }
    @media (min-width: 768px) {
      .quiz-card-floating {
        margin-top: 16px;
      }
    }
  </style>
</head>

<body class="bg-slate-50 min-h-screen overflow-x-hidden">
  <div id="toastBox"></div>
    <!-- SIDEBAR -->
  <?php include __DIR__ . '/partials/student_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 flex gap-6">
      <div class="flex-1 flex flex-col gap-6">
        <section class="max-w-6xl mx-auto w-full">
          <div class="quiz-flow">
            <!-- Hero -->
            <div class="hero-banner">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
                <div class="space-y-3 w-full md:flex-1 md:min-w-0">
                  <nav class="flex items-center gap-2 text-sm text-white/80 uppercase tracking-wide" aria-label="Breadcrumb">
                    <a id="quizBackLink" href="ViewLesson.php" class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-white/40 hover:bg-white/15 transition" aria-label="Back to modules">
                      <i class='bx bx-arrow-back text-base text-white'></i>
                    </a>
                    <span class="text-white/60">/</span>
                    <span class="font-semibold text-white">Modules</span>
                    <span class="text-white/60">/</span>
                    <span id="quizMissionLabel" class="font-semibold text-white">Lesson</span>
                  </nav>
                  <div class="space-y-2">
                    <h1 class="text-3xl font-bold leading-tight text-white">Module Quiz Center</h1>
                    <p id="quizDescription" class="text-white/90 text-sm lg:text-base max-w-4xl">The platform provides multiple choice questions to measure learners’ understanding of learning materials.</p>
                  </div>
                </div>
                <div class="flex flex-col gap-2 items-start md:items-end md:self-start w-full md:w-auto">
                  <div class="hero-stats">
                    <div class="hero-stat">
                      <span class="label">Answered</span>
                        <span class="value" id="quizAnsweredCount">0</span>
                      </div>
                      <div class="hero-stat">
                        <span class="label">Not answered</span>
                        <span class="value" id="quizUnansweredCount">0</span>
                      </div>
                      <div class="hero-stat">
                        <span class="label">Time left</span>
                        <span class="value" id="quizTimerText">00:00</span>
                      </div>
                  </div>
                  <div class="hero-actions-inline">
                    <button
                      id="finishQuizBtn"
                      class="hero-btn px-6"
                      style="background: #fff; color: #1e293b; border-color: rgba(255,255,255,0.85); box-shadow: 0 18px 36px rgba(79, 70, 229, 0.18);"
                    >
                      Finish
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

            <!-- Question container -->
            <div class="quiz-card-floating space-y-4">
              <div id="questionShell" class="question-shell"></div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </section>

  <!-- Result Modal -->
  <div id="quizModal" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="quiz-modal-card max-w-md w-full p-6 space-y-5">
      <div class="flex items-start gap-3">
        <div id="quizModalIcon" class="quiz-modal-icon bg-gradient-to-br from-blue-500 to-indigo-500">
          <i class='bx bx-check text-2xl'></i>
        </div>
        <div class="space-y-1">
          <p id="quizModalTitle" class="text-lg font-semibold text-slate-900">Result</p>
          <p id="quizModalMsg" class="text-sm text-slate-600">You earned XP.</p>
        </div>
      </div>
      <div class="flex justify-end">
        <button id="quizModalBtn" class="hero-btn px-5 py-2" style="background: linear-gradient(120deg, #1fa2ff, #6a68f2); color: #fff; border-color: rgba(255,255,255,0.85); box-shadow: 0 14px 24px rgba(79, 70, 229, 0.18);">
          Continue
        </button>
      </div>
    </div>
  </div>

  <script src="../JS/home.js"></script>
  <script src="../JS/popup.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", async () => {
      const params = new URLSearchParams(window.location.search);
      const moduleId = params.get("module_id") || params.get("id") || "";
      const lessonId = params.get("lesson_id") || params.get("lesson") || "";
      const backLink = document.getElementById("quizBackLink");
      if (backLink) {
        const targetHref = moduleId
          ? `Student-ViewLesson.php?id=${encodeURIComponent(moduleId)}`
          : "Student-ViewLesson.php";
        backLink.setAttribute("href", targetHref);
      }
      const lessonLabel = document.getElementById("quizMissionLabel");
      const answeredEl = document.getElementById("quizAnsweredCount");
      const unansweredEl = document.getElementById("quizUnansweredCount");
      const timerText = document.getElementById("quizTimerText");
      const questionShell = document.getElementById("questionShell");
      const finishBtn = document.getElementById("finishQuizBtn");
      let finishing = false;
      let rawQuestions = [];
      const modal = document.getElementById("quizModal");
      const modalTitle = document.getElementById("quizModalTitle");
      const modalMsg = document.getElementById("quizModalMsg");
      const modalBtn = document.getElementById("quizModalBtn");
      const modalIcon = document.getElementById("quizModalIcon");

      let questions = [];
      let answers = {};
      let loadedLessonId = "";
      let totalSeconds = 0;
      let timeLeft = 0;
      let timerInterval = null;

      const formatTime = (seconds) => {
        const clamped = Math.max(0, Math.floor(seconds));
        const mins = Math.floor(clamped / 60).toString().padStart(2, "0");
        const secs = (clamped % 60).toString().padStart(2, "0");
        return `${mins}:${secs}`;
      };

      const updateCounters = () => {
        const answered = Object.keys(answers).length;
        const total = questions.length;
        const unanswered = Math.max(total - answered, 0);
        if (answeredEl) answeredEl.textContent = answered;
        if (unansweredEl) unansweredEl.textContent = unanswered;
        if (timerText) timerText.textContent = formatTime(timeLeft);
      };

      const clearTimer = () => {
        if (timerInterval) {
          clearInterval(timerInterval);
          timerInterval = null;
        }
      };

      const startTimer = () => {
        clearTimer();
        timeLeft = totalSeconds;
        updateCounters();
        timerInterval = setInterval(() => {
          timeLeft -= 1;
          updateCounters();
          if (timeLeft <= 0) {
            clearTimer();
            handleFinish("timeout");
          }
        }, 1000);
      };

      const renderQuestions = (resetTimer = false) => {
        if (!questionShell) return;
        questionShell.innerHTML = "";
        const total = questions.length;
        if (!total) {
          questionShell.innerHTML = `<p class="text-sm text-slate-500">No quiz available for this lesson.</p>`;
          if (answeredEl) answeredEl.textContent = "0";
          if (unansweredEl) unansweredEl.textContent = "0";
          clearTimer();
          if (timerText) timerText.textContent = "00:00";
          return;
        }
        questionShell.innerHTML = questions.map((q, idx) => {
          const selected = answers[idx];
          const optionsHtml = (q.options || []).map((opt, optIdx) => {
            const isSelected = selected === optIdx;
            return `
              <div class="option ${isSelected ? "selected" : ""}" data-opt="${optIdx}">
                <span>${opt}</span>
                <span class="radio"></span>
              </div>
            `;
          }).join("") || `<p class="text-sm text-slate-500">No options provided.</p>`;
          return `
            <article class="question-card" data-question="${idx}">
              <header>
                <h3>Question ${idx + 1}</h3>
              </header>
              <p>${q.text || q.question || "Question"}</p>
              <div class="options">
                ${optionsHtml}
              </div>
            </article>
          `;
        }).join("");
        if (resetTimer) {
          startTimer();
        } else {
          updateCounters();
        }
      };

      const normalizeQuestions = (raw) => {
        if (!raw) return [];
        let arr = raw;
        if (typeof raw === "string") {
          try { arr = JSON.parse(raw); } catch (_) { arr = []; }
        }
        if (!Array.isArray(arr)) return [];
        return arr.map((item) => {
          if (typeof item === "string") {
            return { text: item, options: [] };
          }
          const optsRaw = item.options || item.choices || item.options_json || [];
          let opts = optsRaw;
          if (typeof optsRaw === "string") {
            try { opts = JSON.parse(optsRaw); } catch (_) { opts = []; }
          }
          if (!Array.isArray(opts)) {
            opts = Object.values(opts || {});
          }
          const rawCorrect = item.correct_index ?? item.correctIndex;
          const correctIndex = Number.isFinite(Number(rawCorrect)) ? Number(rawCorrect) : null;
          return {
            text: item.text || item.question || "Question",
            options: opts.filter(Boolean),
            correctIndex,
          };
        });
      };

      const loadQuiz = async () => {
        if (!moduleId) {
          questionShell.innerHTML = `<p class="text-sm text-slate-500">Module id is missing.</p>`;
          return;
        }
        try {
          const resp = await fetch(`../PHP/getModuleWithLessons.php?id=${encodeURIComponent(moduleId)}`);
          const data = await resp.json();
          if (!resp.ok || data?.status !== "success") {
            throw new Error(data?.message || "Unable to load module/quiz.");
          }
          const lessons = data.data?.lessons || [];
          let lesson = null;
          if (lessonId) {
            lesson = lessons.find((l) => String(l.id) === String(lessonId));
          }
          if (!lesson) {
            lesson = lessons[0];
          }
          loadedLessonId = lesson?.id ?? "";
          if (lessonLabel && lesson?.title) {
            lessonLabel.textContent = (lesson.title || "Lesson").toUpperCase();
          }
          const quizRaw = lesson?.quiz || lesson?.quiz_questions;
          rawQuestions = Array.isArray(quizRaw) ? quizRaw : normalizeQuestions(quizRaw);
          questions = normalizeQuestions(quizRaw);
          answers = {};
          totalSeconds = questions.length * 30;
          renderQuestions(true);
        } catch (err) {
          console.error(err);
          questionShell.innerHTML = `<p class="text-sm text-rose-500">Failed to load quiz.</p>`;
        }
      };

      document.addEventListener("click", (event) => {
        const opt = event.target.closest(".option");
        if (!opt) return;
        const questionEl = opt.closest("[data-question]");
        if (!questionEl) return;
        const qIdx = Number(questionEl.getAttribute("data-question")) || 0;
        const optIdx = Number(opt.getAttribute("data-opt"));
        answers[qIdx] = optIdx;
        questionEl.querySelectorAll(".option").forEach((el) => {
          el.classList.toggle("selected", el === opt);
        });
        updateCounters();
      });

      const redirectToMission = () => {
        if (!moduleId) {
          window.location.href = "Student-ViewLesson.php";
          return;
        }
        if (lessonId) {
          window.location.href = `Student-ViewLesson-Detail.php?module_id=${encodeURIComponent(moduleId)}&lesson_id=${encodeURIComponent(lessonId)}`;
          return;
        }
        window.location.href = `Student-ViewLesson.php?id=${encodeURIComponent(moduleId)}`;
      };

      await loadQuiz();
      if (!questions.length && timerText) {
        timerText.textContent = "00:00";
      }
      if (finishBtn) {
        finishBtn.addEventListener("click", () => handleFinish("completed"));
      }
      if (modalBtn) {
        modalBtn.addEventListener("click", () => {
          modal?.setAttribute("hidden", "hidden");
          redirectToMission();
        });
      }

      async function handleFinish(reason = "completed") {
        if (finishing) return;
        finishing = true;
        clearTimer();
        // Answers are graded on the server; the client no longer has the answer key.
        const payload = {
          module_id: moduleId || "",
          lesson_id: loadedLessonId || lessonId || "",
          answers,
          reason,
        };

        try {
          const resp = await fetch("../PHP/awardXp.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify(payload),
          });
          const data = await resp.json();
          if (!resp.ok || data?.status !== "success") {
            throw new Error(data?.message || "Unable to award XP.");
          }
          const result = data.data || {};
          const xpEarned = Number(result.xp_awarded) || 0;
          const percent = Number(result.percentage) || 0;
          const passed = percent >= 80;
          const lessonLabel = document.getElementById("quizMissionLabel")?.textContent || "this lesson";
          const repeatNote =
            xpEarned === 0 && Number(result.attempt_xp) > 0
              ? " Your best score for this lesson was already counted, so no new XP was added."
              : "";
          const msg =
            (reason === "timeout"
              ? `Time out. You earned ${xpEarned} XP from ${lessonLabel}.`
              : passed
              ? `Congratulations! You earned ${xpEarned} XP from ${lessonLabel}.`
              : `You earned ${xpEarned} XP from ${lessonLabel}. Keep going!`) + repeatNote;
          openModal(
            reason === "timeout" ? "Time out" : passed ? "Great job" : "Quiz submitted",
            percent ? `Score: ${percent}%. ${msg}` : msg,
            reason === "timeout" ? "warning" : "success"
          );
          // mark quiz taken locally with pass flag
          if (lessonId) {
            const quizTakenKey = `quiz_taken_${moduleId || "m"}_${lessonId}`;
            localStorage.setItem(quizTakenKey, "1");
            const quizPassKey = `quiz_pass_${moduleId || "m"}_${lessonId}`;
            localStorage.setItem(quizPassKey, passed ? "1" : "0");
            const quizScoreKey = `quiz_score_${moduleId || "m"}_${lessonId}`;
            localStorage.setItem(quizScoreKey, String(percent));
          }
        } catch (err) {
          console.error(err);
          openModal("XP not saved", "Failed to award XP. Please try again.", "error");
        } finally {
          // wait for modal interaction
        }
      }

      function openModal(title, message, variant = "success") {
        if (modalTitle) modalTitle.textContent = title;
        if (modalMsg) modalMsg.textContent = message;
        if (modalIcon) {
          modalIcon.className = "quiz-modal-icon";
          let bg = "bg-gradient-to-br from-blue-500 to-indigo-500";
          let icon = "bx bx-check";
          if (variant === "warning") {
            bg = "bg-gradient-to-br from-amber-500 to-orange-500";
            icon = "bx bx-time-five";
          } else if (variant === "error") {
            bg = "bg-gradient-to-br from-rose-500 to-red-500";
            icon = "bx bx-error";
          }
          modalIcon.classList.add(...bg.split(" "));
          modalIcon.innerHTML = `<i class='${icon} text-2xl'></i>`;
        }
        if (modal) {
          modal.removeAttribute("hidden");
        }
      }
    });
  </script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>

