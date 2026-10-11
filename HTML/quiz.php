<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
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
      padding: 32px 32px 36px;
      box-shadow: 0 22px 48px rgba(79, 70, 229, 0.25);
      display: flex;
      flex-direction: column;
      gap: 18px;
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
    .quiz-card {
      background: #fff;
      border-radius: 1.5rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
      padding: 1.5rem;
    }
    .quiz-progress-row {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 1rem;
      align-items: center;
      padding: 1rem 1.25rem;
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .quiz-progress-shell {
      width: 100%;
      height: 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.28);
      overflow: hidden;
    }
    .quiz-progress-fill {
      height: 100%;
      width: 20%;
      background: linear-gradient(90deg, #7c3aed, #6366f1);
      border-radius: inherit;
      transition: width 0.25s ease;
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
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
    }
    .question-remove {
      color: #ef4444;
      font-weight: 700;
      border: 1px solid rgba(239, 68, 68, 0.2);
      background: rgba(239, 68, 68, 0.08);
      padding: 0.45rem 0.9rem;
      border-radius: 999px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .question-remove:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 18px rgba(239, 68, 68, 0.18);
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
    .hero-progress-wrap {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
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
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      z-index: 150;
    }
    .modal-overlay.show {
      display: flex;
    }
    .modal-card {
      background: #fff;
      border-radius: 16px;
      padding: 1.5rem;
      width: min(420px, 100%);
      box-shadow: 0 24px 60px rgba(15,23,42,0.25);
      border: 1px solid #e2e8f0;
    }
    .modal-card h3 {
      margin: 0 0 0.5rem;
      font-size: 1.1rem;
      color: #0f172a;
    }
    .modal-card p {
      margin: 0 0 1rem;
      color: #475569;
      font-size: 0.95rem;
    }
    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }
    .modal-btn {
      padding: 0.55rem 1.1rem;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      background: #f8fafc; 
      font-weight: 700;
      color: #0f172a;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }
    .modal-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 22px rgba(15,23,42,0.12);
    }
    .modal-btn.confirm {
      background: #fef2f2;
      border-color: #fecdd3;
      color: #b91c1c;
    }
    .add-quiz-card {
      background: linear-gradient(180deg, #f8fafc, #ffffff);
      border: 1px dashed #e2e8f0;
      border-radius: 1.25rem;
      padding: 2.5rem 1.5rem;
      text-align: center;
      color: #0f172a;
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
      align-items: center;
      justify-content: center;
    }
    .add-quiz-circle {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #eef2ff;
      border: 1px solid #e0e7ff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #4f46e5;
      font-size: 1.5rem;
      box-shadow: 0 10px 22px rgba(79,70,229,0.16);
    }
    .add-quiz-card h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin: 0;
    }
    .add-quiz-card p {
      margin: 0;
      color: #475569;
      font-size: 0.95rem;
    }
    .add-quiz-card a {
      font-weight: 700;
      color: #4f46e5;
      text-decoration: none;
    }
  </style>
</head>

<body class="bg-slate-50 min-h-screen overflow-x-hidden">
  <div id="toastBox"></div>
  <div id="quizRemoveModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="removeQuizTitle" aria-describedby="removeQuizDesc">
    <div class="modal-card">
      <h3 id="removeQuizTitle">Remove this question?</h3>
      <p id="removeQuizDesc">This will remove the question from this quiz view. It won’t be saved until you publish changes.</p>
      <div class="modal-actions">
        <button type="button" class="modal-btn" id="cancelRemoveQuestion">Cancel</button>
        <button type="button" class="modal-btn confirm" id="confirmRemoveQuestion">Remove</button>
      </div>
    </div>
  </div>

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 flex gap-6">
      <div class="flex-1 flex flex-col gap-6">
        <section class="max-w-6xl mx-auto w-full space-y-8">
          <!-- Hero -->
          <div class="hero-banner">
            <div class="flex items-start gap-4 flex-col">
              <div class="space-y-3 w-full">
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
                </div>
                <div class="hero-progress-wrap">
                  <div class="quiz-progress-row mt-1 flex-1">
                    <div>
                    <div class="flex items-center justify-between mb-2 text-white">
                      <span id="quizProgressText" class="text-sm font-semibold">0% completed</span>
                      <span id="quizStepText" class="text-xs opacity-90">Question 0 of 0</span>
                    </div>
                    <div class="quiz-progress-shell" aria-label="Quiz progress">
                      <div id="quizProgressFill" class="quiz-progress-fill" style="width: 0%;"></div>
                    </div>
                  </div>
                  <div class="quiz-meta-small">
                    <i class='bx bx-time-five'></i>
                    <span id="quizTimerText">00:00 min left</span>
                  </div>
                </div>
                <div class="flex gap-3">
                  <div class="hero-stat">
                    <span class="label">Answered</span>
                      <span class="value" id="quizAnsweredCount">0</span>
                    </div>
                    <div class="hero-stat">
                      <span class="label">Not answered</span>
                      <span class="value" id="quizUnansweredCount">0</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Question container -->
          <div class="quiz-card space-y-4">
            <div id="questionShell" class="question-shell"></div>
            <div class="add-quiz-card" id="addQuizCard" role="button" tabindex="0" aria-label="Add new quiz question">
              <span class="add-quiz-circle"><i class='bx bx-plus'></i></span>
              <h3>Add new question</h3>
              <p>Create another quiz question for this lesson.</p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </section>

  <script src="../JS/home.js"></script>
  <script src="../JS/popup.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", async () => {
      const params = new URLSearchParams(window.location.search);
      const moduleId = params.get("module_id") || params.get("id") || "";
      const lessonId = params.get("lesson_id") || params.get("lesson") || "";
      const backLink = document.getElementById("quizBackLink");
      const removeModal = document.getElementById("quizRemoveModal");
      const confirmRemoveBtn = document.getElementById("confirmRemoveQuestion");
      const cancelRemoveBtn = document.getElementById("cancelRemoveQuestion");
      if (backLink) {
        const targetHref = moduleId
          ? `ViewLesson.php?id=${encodeURIComponent(moduleId)}`
          : "ViewLesson.php";
        backLink.setAttribute("href", targetHref);
      }
      const lessonLabel = document.getElementById("quizMissionLabel");
      const answeredEl = document.getElementById("quizAnsweredCount");
      const unansweredEl = document.getElementById("quizUnansweredCount");
      const progressFill = document.getElementById("quizProgressFill");
      const progressText = document.getElementById("quizProgressText");
      const stepText = document.getElementById("quizStepText");
      const questionShell = document.getElementById("questionShell");
      const addQuizCard = document.getElementById("addQuizCard");

      let questions = [];
      let answers = {};
      let currentIndex = 0;
      let pendingRemoveIndex = null;

      const updateCounters = () => {
        const answered = Object.keys(answers).length;
        const total = questions.length;
        const unanswered = Math.max(total - answered, 0);
        if (answeredEl) answeredEl.textContent = answered;
        if (unansweredEl) unansweredEl.textContent = unanswered;
        const pct = total ? Math.round(((currentIndex + 1) / total) * 100) : 0;
        if (progressFill) {
          progressFill.style.width = `${Math.min(Math.max(pct, 0), 100)}%`;
        }
        if (progressText) progressText.textContent = `${pct}% completed`;
        if (stepText) {
          stepText.textContent = `Question ${Math.min(currentIndex + 1, total)} of ${total}`;
        }
      };

      const renderQuestion = (index) => {
        if (!questionShell) return;
        questionShell.innerHTML = "";
        const total = questions.length;
        if (!total) {
          questionShell.innerHTML = `<p class="text-sm text-slate-500">No quiz available for this lesson.</p>`;
          if (progressFill) progressFill.style.width = "0%";
          if (progressText) progressText.textContent = "0% completed";
          if (stepText) stepText.textContent = "Question 0 of 0";
          if (answeredEl) answeredEl.textContent = "0";
          if (unansweredEl) unansweredEl.textContent = "0";
          return;
        }
        const safeIndex = Math.min(Math.max(index, 0), total - 1);
        currentIndex = safeIndex;
        const q = questions[safeIndex];
        const selected = answers[safeIndex];
        const optionsHtml =
          (q.options || [])
            .map((opt, optIdx) => {
              const isSelected = selected === optIdx;
              return `
            <div class="option ${isSelected ? "selected" : ""}" data-opt="${optIdx}">
              <span>${opt}</span>
              <span class="radio"></span>
            </div>
          `;
            })
            .join("") || `<p class="text-sm text-slate-500">No options provided.</p>`;

        questionShell.innerHTML = `
          <article class="question-card" data-question="${safeIndex}">
            <header>
              <h3>Question ${safeIndex + 1}</h3>
              <button class="question-remove" type="button" data-remove-question="${safeIndex}">Remove</button>
            </header>
            <p>${q.text || q.question || "Question"}</p>
            <div class="options">
              ${optionsHtml}
            </div>
          </article>
        `;
        updateCounters();
      };

      const normalizeQuestions = (raw) => {
        if (!raw) return [];
        let arr = raw;
        if (typeof raw === "string") {
          try {
            arr = JSON.parse(raw);
          } catch (_) {
            arr = [];
          }
        }
        if (!Array.isArray(arr)) return [];
        return arr.map((item) => {
          if (typeof item === "string") {
            return { text: item, options: [] };
          }
          const optsRaw = item.options || item.choices || item.options_json || [];
          let opts = optsRaw;
          if (typeof optsRaw === "string") {
            try {
              opts = JSON.parse(optsRaw);
            } catch (_) {
              opts = [];
            }
          }
          if (!Array.isArray(opts)) {
            opts = Object.values(opts || {});
          }
          return {
            text: item.text || item.question || "Question",
            options: opts.filter(Boolean),
          };
        });
      };

      const loadQuiz = async () => {
        if (!moduleId) {
          if (questionShell) {
            questionShell.innerHTML = `<p class="text-sm text-slate-500">Module id is missing.</p>`;
          }
          return;
        }
        try {
          const resp = await fetch(
            `../PHP/getModuleWithLessons.php?id=${encodeURIComponent(moduleId)}`
          );
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
          if (lessonLabel && lesson?.title) {
            lessonLabel.textContent = (lesson.title || "Lesson").toUpperCase();
          }
          const quizRaw = lesson?.quiz || lesson?.quiz_questions;
          questions = normalizeQuestions(quizRaw);
          answers = {};
          renderQuestion(0);
        } catch (err) {
          console.error(err);
          if (questionShell) {
            questionShell.innerHTML = `<p class="text-sm text-rose-500">Failed to load quiz.</p>`;
          }
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
        renderQuestion(qIdx);
      });

      document.addEventListener("click", (event) => {
        const btn = event.target.closest("[data-remove-question]");
        if (!btn) return;
        const qIdx = Number(btn.getAttribute("data-remove-question"));
        pendingRemoveIndex = Number.isFinite(qIdx) ? qIdx : null;
        if (removeModal) removeModal.classList.add("show");
      });

      const hideRemoveModal = () => {
        pendingRemoveIndex = null;
        if (removeModal) removeModal.classList.remove("show");
      };

      confirmRemoveBtn?.addEventListener("click", () => {
        if (pendingRemoveIndex === null) {
          hideRemoveModal();
          return;
        }
        const qIdx = pendingRemoveIndex;
        questions = questions.filter((_, idx) => idx !== qIdx);
        const newAnswers = {};
        questions.forEach((_, idx) => {
          if (answers[idx] !== undefined) newAnswers[idx] = answers[idx];
        });
        answers = newAnswers;
        hideRemoveModal();
        renderQuestion(Math.min(qIdx, questions.length - 1));
      });

      cancelRemoveBtn?.addEventListener("click", hideRemoveModal);
      removeModal?.addEventListener("click", (event) => {
        if (event.target === removeModal) {
          hideRemoveModal();
        }
      });

      const goToEditor = () => {
        if (!moduleId) return;
        const href = `editModule.php?id=${encodeURIComponent(moduleId)}${
          lessonId ? `&lesson_id=${encodeURIComponent(lessonId)}` : ""
        }#quiz`;
        window.location.href = href;
      };

      addQuizCard?.addEventListener("click", goToEditor);
      addQuizCard?.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          goToEditor();
        }
      });

      await loadQuiz();
    });
  </script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
