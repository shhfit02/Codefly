const quizModal = document.getElementById("quizModal");
const quizBox = document.getElementById("quizBox");
const startQuiz = document.getElementById("startQuiz");
const closeQuiz = document.getElementById("closeQuiz");
const questionText = document.getElementById("questionText");
const optionsDiv = document.getElementById("options");
const nextBtn = document.getElementById("nextBtn");
const submitBtn = document.getElementById("submitBtn");
const quizDesc = document.getElementById("quizDesc");
const progressBar = document.getElementById("progressBar");
const progressLabel = document.getElementById("progressLabel");
const questionCounter = document.getElementById("questionCounter");
const scorePreview = document.getElementById("scorePreview");
const xpPreview = document.getElementById("xpPreview");
const questionHint = document.getElementById("questionHint");
const quizStatus = document.getElementById("quizStatus");
const quizHelper = document.getElementById("quizHelper");
const quizTimer = document.getElementById("quizTimer");
const timerPill = quizTimer?.closest(".quiz-pill");
const activeLessonId = document.body?.dataset?.lesson || "lesson-01";

const LESSON_QUIZZES = {
  default: [
    {
      question: "1. What is the meaning of sequencing in programming?",
      options: [
        "Doing steps in any order",
        "Doing steps from top to bottom",
        "Doing steps randomly",
        "Doing steps only once"
      ],
      answer: 1,
      hint: "Sequencing is about the exact order instructions run."
    },
    {
      question: "2. Which of the following is an example of a variable?",
      options: [
        "A fixed sentence",
        "A box that stores information",
        "A loop that repeats",
        "A picture"
      ],
      answer: 1,
      hint: "Variables are containers that hold data you can change."
    },
    {
      question: "3. If/Else is used for what purpose?",
      options: [
        "To repeat instructions",
        "To make decisions",
        "To store data",
        "To draw shapes"
      ],
      answer: 1,
      hint: "Think of branching paths based on a condition."
    },
    {
      question: "4. Which situation best describes a loop?",
      options: [
        "Turning on a light",
        "Tying shoes once",
        "Clapping hands 10 times",
        "Clicking a button once"
      ],
      answer: 2,
      hint: "Loops repeat an action until a goal is met."
    },
    {
      question: "5. What is a function in programming?",
      options: [
        "A repeated mistake",
        "A reusable set of instructions",
        "A place to store numbers",
        "A type of error"
      ],
      answer: 1,
      hint: "Functions package steps you can call by name."
    },
    {
      question: "6. Which concept helps us avoid writing the same code many times?",
      options: [
        "Selection",
        "Variables",
        "Loops",
        "Comments"
      ],
      answer: 2,
      hint: "This concept repeats actions so you don't duplicate lines."
    },
    {
      question: "7. What happens if a sequence is in the wrong order?",
      options: [
        "The program runs faster",
        "The program may not work",
        "Nothing happens",
        "It becomes a loop"
      ],
      answer: 1,
      hint: "Incorrect order often breaks the logic."
    },
    {
      question: "8. Which of the following uses a conditional?",
      options: [
        "\"Repeat 5 times.\"",
        "\"Move 10 steps.\"",
        "\"If hungry, eat food.\"",
        "\"Store number 10.\""
      ],
      answer: 2,
      hint: "Conditionals check something before acting."
    },
    {
      question: "9. What is the main purpose of variables in programming?",
      options: [
        "To decorate the program",
        "To store and change data",
        "To make drawings",
        "To slow down the computer"
      ],
      answer: 1,
      hint: "Variables hold values you can update."
    },
    {
      question: "10. A function named jump() most likely contains...?",
      options: [
        "A repeated loop",
        "A set of steps for jumping",
        "A stored value",
        "A decision statement"
      ],
      answer: 1,
      hint: "Function names describe the action their steps perform."
    }
  ],
  "lesson-02": [
    {
      question: "1. What does IPO stand for?",
      options: [
        "Input–Power–Output",
        "Input–Process–Output",
        "Inside–Program–Outside",
        "Information–Print–Operation"
      ],
      answer: 1,
      hint: "Think of what happens before, during, and after the computer works."
    },
    {
      question: "2. Which is an input device?",
      options: ["Monitor", "Keyboard", "Speaker", "Printer"],
      answer: 1,
      hint: "You use it to send keystrokes into the computer."
    },
    {
      question: "3. Which is an example of output?",
      options: ["Clicking the mouse", "Display on screen", "Touching a button", "Typing a letter"],
      answer: 1,
      hint: "Outputs are what the computer shows or plays back."
    },
    {
      question: "4. Computers think like humans.",
      options: ["True", "False"],
      answer: 1,
      hint: "They follow instructions literally."
    },
    {
      question: "5. What happens during the Process stage?",
      options: [
        "Computer receives data",
        "Computer makes decisions & calculations",
        "Computer shows results",
        "Computer turns off"
      ],
      answer: 1,
      hint: "This is the \"work\" part of IPO."
    },
    {
      question: "6. Computers follow instructions based on _______.",
      options: ["Emotion", "Logic", "Guessing", "Mood"],
      answer: 1,
      hint: "Robots are not swayed by feelings."
    },
    {
      question: "7. Why do computers need exact steps?",
      options: ["They can’t guess", "They are slow", "They forget", "They get bored"],
      answer: 0,
      hint: "Precision matters because there is no intuition."
    },
    {
      question: "8. Which part of IPO is a screen display?",
      options: ["Input", "Process", "Output", "Storage"],
      answer: 2,
      hint: "It's what the user sees after processing."
    },
    {
      question: "9. Computers understand emotions.",
      options: ["True", "False"],
      answer: 1,
      hint: "Feelings are not data."
    },
    {
      question: "10. Which activity best shows IPO?",
      options: ["Eating food", "Using ATM", "Running", "Sleeping"],
      answer: 1,
      hint: "Insert card, process request, get cash."
    }
  ],
  "lesson-03": [
    {
      question: "1. What is an algorithm?",
      options: ["A type of game", "Step-by-step instructions", "A random idea", "A song"],
      answer: 1,
      hint: "Think ordered steps you can follow."
    },
    {
      question: "2. Which is a step-by-step task?",
      options: ["Random jumping", "Brushing teeth in order", "Sleeping", "Talking"],
      answer: 1,
      hint: "Starts with wet toothbrush, add paste, brush..."
    },
    {
      question: "3. Why must algorithms be in correct sequence?",
      options: ["For fun", "So tasks work correctly", "Computers prefer it", "To make it longer"],
      answer: 1,
      hint: "Wrong order often breaks the result."
    },
    {
      question: "4. Algorithms only exist in computers.",
      options: ["True", "False"],
      answer: 1,
      hint: "Recipes and routines are algorithms too."
    },
    {
      question: "5. What happens if a step is missing?",
      options: ["Nothing", "The task may fail", "It becomes faster", "It improves"],
      answer: 1,
      hint: "Missing steps usually break the flow."
    },
    {
      question: "6. Which activity represents an algorithm?",
      options: ["Eating randomly", "Following a recipe", "Jumping", "Sitting"],
      answer: 1,
      hint: "Recipes are detailed sequences."
    },
    {
      question: "7. Algorithms must be ______.",
      options: ["Confusing", "Clear and complete", "Random", "Boring"],
      answer: 1,
      hint: "Clarity makes them repeatable."
    },
    {
      question: "8. Order does not matter in algorithms.",
      options: ["True", "False"],
      answer: 1,
      hint: "Order is the whole point."
    },
    {
      question: "9. Which example is shown in the video?",
      options: ["Doing homework", "Making a sandwich", "Riding bicycle", "Sleeping"],
      answer: 1,
      hint: "Classic lunch example."
    },
    {
      question: "10. Why are algorithms important?",
      options: [
        "They decorate programs",
        "They help instructions run correctly",
        "They confuse computers",
        "They make tasks slower"
      ],
      answer: 1,
      hint: "Correct results depend on good algorithms."
    }
  ],
  "lesson-04": [
    {
      question: "1. Which symbol shows Start/End?",
      options: ["Rectangle", "Diamond", "Oval", "Arrow"],
      answer: 2,
      hint: "It looks like a pill shape."
    },
    {
      question: "2. What symbol is used for decision?",
      options: ["Circle", "Diamond", "Square", "Oval"],
      answer: 1,
      hint: "Think of branches coming out of it."
    },
    {
      question: "3. Process steps use a rectangle.",
      options: ["True", "False"],
      answer: 0,
      hint: "Most actions sit inside this shape."
    },
    {
      question: "4. Why are flowcharts useful?",
      options: ["Make instructions harder", "Visualize steps clearly", "Add decoration", "Replace computers"],
      answer: 1,
      hint: "Seeing the flow helps everyone follow it."
    },
    {
      question: "5. Arrows show the ______ of steps.",
      options: ["Colour", "Direction", "Shape", "Speed"],
      answer: 1,
      hint: "They point the way."
    },
    {
      question: "6. Which step uses a diamond?",
      options: ["Start", "End", "“Is it raining?”", "Process"],
      answer: 2,
      hint: "Yes/No questions belong here."
    },
    {
      question: "7. Flowcharts must begin with an oval.",
      options: ["True", "False"],
      answer: 0,
      hint: "Start/End live in ovals."
    },
    {
      question: "8. Which is an example of a process?",
      options: ["Start", "End", "Mix ingredients", "Yes/No question"],
      answer: 2,
      hint: "This is an action step."
    },
    {
      question: "9. Flowcharts help make instructions more ______.",
      options: ["Confusing", "Interesting", "Clear", "Longer"],
      answer: 2,
      hint: "Clarity is the goal."
    },
    {
      question: "10. What if arrows are missing?",
      options: ["No direction", "Faster flowchart", "Better design", "More colour"],
      answer: 0,
      hint: "Without arrows you can't follow the path."
    }
  ],
  "lesson-05": [
    {
      question: "1. What is a variable?",
      options: ["A fixed number", "A container that stores data", "A type of computer", "A hardware device"],
      answer: 1,
      hint: "It's like a labeled box for values."
    },
    {
      question: "2. A variable can change its value.",
      options: ["True", "False"],
      answer: 0,
      hint: "Values can be updated."
    },
    {
      question: "3. Which of the following is NOT a variable?",
      options: ["Age", "Score", "Favourite colour", "A stone on the ground"],
      answer: 3,
      hint: "Think about what changes over time."
    },
    {
      question: "4. Variables store ______.",
      options: ["Decorations", "Data", "Music", "Pictures only"],
      answer: 1,
      hint: "They hold information."
    },
    {
      question: "5. What type of data is TRUE/FALSE?",
      options: ["Text", "Boolean", "Number", "Undefined"],
      answer: 1,
      hint: "Yes/No values."
    },
    {
      question: "6. Which is a numerical variable?",
      options: ["“Blue”", "15", "True", "Pizza"],
      answer: 1,
      hint: "Numbers count."
    },
    {
      question: "7. Variables can only store text.",
      options: ["True", "False"],
      answer: 1,
      hint: "They can store many types."
    },
    {
      question: "8. Why do programmers use variables?",
      options: ["To store changing information", "To make code longer", "To add colours", "To make computers slow"],
      answer: 0,
      hint: "They keep track of changing values."
    },
    {
      question: "9. Which is an example of a variable in a game?",
      options: ["Screen colour", "Player score", "Background music", "Character design"],
      answer: 1,
      hint: "Scores update often."
    },
    {
      question: "10. Apps use variables to store ______.",
      options: ["User data", "Food recipes", "Phone battery", "Wallpaper"],
      answer: 0,
      hint: "Think about logins and settings."
    }
  ],
  "lesson-06": [
    {
      question: "1. What does an IF statement check?",
      options: ["A colour", "A condition", "A shape", "A number only"],
      answer: 1,
      hint: "It tests a true/false question."
    },
    {
      question: "2. IF/ELSE helps computers make decisions.",
      options: ["True", "False"],
      answer: 0,
      hint: "They choose between branches."
    },
    {
      question: "3. Which is an example of IF/ELSE?",
      options: ["Repeat 10 times", "If hungry → eat, else → drink", "Move forward", "Play sound"],
      answer: 1,
      hint: "Look for the condition plus two actions."
    },
    {
      question: "4. When the IF condition is false, the program runs the ______ part.",
      options: ["IF", "ELSE", "Start", "Loop"],
      answer: 1,
      hint: "It's the alternative path."
    },
    {
      question: "5. Computers use IF/ELSE to make ______.",
      options: ["Decorations", "Mistakes", "Decisions", "Music"],
      answer: 2,
      hint: "They choose actions based on checks."
    },
    {
      question: "6. IF statements are used in games.",
      options: ["True", "False"],
      answer: 0,
      hint: "Think of scoring, health, and win/lose states."
    },
    {
      question: "7. Which example appears in the video?",
      options: ["If raining → bring umbrella", "If hungry → sleep", "If battery low → charge", "If coding → jump"],
      answer: 0,
      hint: "Weather choice."
    },
    {
      question: "8. A condition checks if something is ______.",
      options: ["True or false", "Loud or quiet", "Big or small", "Fast or slow"],
      answer: 0,
      hint: "Binary check."
    },
    {
      question: "9. A program can run without decisions.",
      options: ["True", "False"],
      answer: 1,
      hint: "Most real apps branch."
    },
    {
      question: "10. Why do we use IF/ELSE?",
      options: [
        "To make instructions pretty",
        "To help programs choose actions",
        "To make code longer",
        "To confuse computers"
      ],
      answer: 1,
      hint: "Decisions are the goal."
    }
  ],
  "lesson-07": [
    {
      question: "1. What is the purpose of a loop?",
      options: ["To repeat steps", "To stop programs", "To add music", "To decorate code"],
      answer: 0,
      hint: "Loops run things again and again."
    },
    {
      question: "2. Loops repeat actions automatically.",
      options: ["True", "False"],
      answer: 0,
      hint: "That's their job."
    },
    {
      question: "3. What does a REPEAT loop do?",
      options: ["Runs forever", "Repeats a fixed number of times", "Repeats randomly", "Repeats only once"],
      answer: 1,
      hint: "Think counted repetitions."
    },
    {
      question: "4. A WHILE loop repeats as long as the ______ is true.",
      options: ["Colour", "Movement", "Condition", "Sound"],
      answer: 2,
      hint: "It checks a boolean each time."
    },
    {
      question: "5. Which is an example of a loop?",
      options: ["Take one photo", "Brush hair 20 times", "Turn off light", "Smile once"],
      answer: 1,
      hint: "Repeating action."
    },
    {
      question: "6. Loops make tasks faster.",
      options: ["True", "False"],
      answer: 0,
      hint: "Automation saves time."
    },
    {
      question: "7. Why do programmers use loops?",
      options: [
        "To save time and avoid repeating code",
        "To confuse the computer",
        "To slow down programs",
        "To change colours"
      ],
      answer: 0,
      hint: "Efficiency."
    },
    {
      question: "8. Which example appears in the video?",
      options: ["Repeat clap 10 times", "Drive a car", "Make a sandwich", "Turn left once"],
      answer: 0,
      hint: "Think rhythm."
    },
    {
      question: "9. Loops help avoid writing the same steps ______.",
      options: ["Once", "Repeatedly", "Randomly", "Never"],
      answer: 1,
      hint: "They prevent manual duplication."
    },
    {
      question: "10. What happens if a WHILE condition never becomes false?",
      options: ["Loop stops immediately", "Loop runs forever", "Loop plays music", "Loop changes to IF/ELSE"],
      answer: 1,
      hint: "That's an infinite loop."
    }
  ],
  "lesson-08": [
    {
      question: "1. Why is drone safety important?",
      options: ["To make the drone look cool", "To avoid accidents and injuries", "To fly faster", "To take better photos"],
      answer: 1,
      hint: "Safety protects people and gear."
    },
    {
      question: "2. Which area is SAFE for drone takeoff?",
      options: ["Crowded school corridor", "Near cars in the parking lot", "Open field with no people", "Inside the classroom"],
      answer: 2,
      hint: "Space and no bystanders."
    },
    {
      question: "3. What should you check before flying?",
      options: ["Drone battery level", "Colour of the drone", "Phone wallpaper", "School timetable"],
      answer: 0,
      hint: "Power matters."
    },
    {
      question: "4. What weather is SAFE for flying a drone?",
      options: ["Strong wind", "Heavy rain", "Storm", "Clear and calm weather"],
      answer: 3,
      hint: "Calm skies are best."
    },
    {
      question: "5. Why must you keep the drone in your sight?",
      options: ["To take selfies", "To avoid losing control", "To show your friends", "To save battery"],
      answer: 1,
      hint: "Visual line-of-sight prevents mishaps."
    },
    {
      question: "6. What should you do if the drone does not respond?",
      options: ["Fly higher", "Press emergency stop / land immediately", "Throw the remote", "Keep flying"],
      answer: 1,
      hint: "Stop safely."
    },
    {
      question: "7. Which of the following is NOT safe?",
      options: ["Flying near electrical wires", "Flying far from people", "Flying in an empty field", "Doing a pre-flight check"],
      answer: 0,
      hint: "Avoid hazards overhead."
    },
    {
      question: "8. When the drone battery is low, you should…",
      options: ["Fly faster", "Ignore it", "Land safely", "Restart the drone"],
      answer: 2,
      hint: "Land before power cuts."
    },
    {
      question: "9. What should you do after landing?",
      options: ["Leave the drone on", "Check for damage and power off", "Throw it in your bag", "Fly again immediately"],
      answer: 1,
      hint: "Inspect and shut down."
    },
    {
      question: "10. Drone safety rules help you to…",
      options: ["Fly indoors", "Protect people, property, and the drone", "Fly without permission", "Break speed limits"],
      answer: 1,
      hint: "Safety protects everyone."
    }
  ]
};

const quiz = LESSON_QUIZZES[activeLessonId] || LESSON_QUIZZES.default;

let current = 0;
let score = 0;
const TIME_PER_QUESTION_SECONDS = 30; // 30 seconds per question
const BASE_XP_PER_CORRECT = 10;
const PERFECT_BONUS_XP = 100;
const QUIZ_DURATION_SECONDS = quiz.length * TIME_PER_QUESTION_SECONDS;
const XP_TOTAL_KEY = "user-total-xp";
let remainingSeconds = QUIZ_DURATION_SECONDS;
let timerInterval = null;

function persistQuizResult(lessonId, finalScore, totalQuestions, earnedXp) {
  const percentage = Math.round((finalScore / totalQuestions) * 100);
  const scoreKey = `${lessonId}-quiz-score`;
  const lessonXpKey = `${lessonId}-quiz-xp`;
  const endpoint = "../PHP/saveQuizResult.php";

  const previousBestXp = Number(localStorage.getItem(lessonXpKey)) || 0;
  const bestXp = Math.max(previousBestXp, earnedXp);
  const currentTotal = Number(localStorage.getItem(XP_TOTAL_KEY)) || 0;
  const adjustedTotal = Math.max(0, currentTotal - previousBestXp + bestXp);

  localStorage.setItem(scoreKey, String(percentage));
  localStorage.setItem(lessonXpKey, String(bestXp));
  localStorage.setItem(XP_TOTAL_KEY, String(adjustedTotal));

  fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      lessonId,
      score: finalScore,
      totalQuestions,
      percentage,
      xp: earnedXp,
      bestXp
    }),
    credentials: "include"
  }).catch(() => {
    // Silently ignore network/HTTP errors; localStorage still holds progress.
  });
}

startQuiz?.addEventListener("click", () => {
  quizModal?.classList.remove("hidden");
  document.body.classList.add("modal-open");
  quizBox?.classList.add("animate-slideUp");
  quizBox?.classList.remove("opacity-0", "translate-y-full");
  startTimer();
  loadQuestion();
});

closeQuiz?.addEventListener("click", closeModal);
quizModal?.addEventListener("click", (event) => {
  if (event.target === quizModal) {
    closeModal();
  }
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && quizModal && !quizModal.classList.contains("hidden")) {
    closeModal();
  }
});

function loadQuestion() {
  if (!questionText || !optionsDiv) return;
  const q = quiz[current];
  questionText.textContent = q.question;
  if (questionHint) {
    questionHint.textContent = q.hint || "Select the best answer to move forward.";
  }
  if (questionCounter) {
    const padded = String(current + 1).padStart(2, "0");
    const total = String(quiz.length).padStart(2, "0");
    questionCounter.textContent = `${padded} of ${total}`;
  }
  if (scorePreview) {
    scorePreview.textContent = `Score ${score}/${quiz.length}`;
  }
  if (xpPreview) {
    xpPreview.textContent = `XP ${score * BASE_XP_PER_CORRECT}`;
  }
  if (quizHelper) {
    quizHelper.textContent =
      current === quiz.length - 1
        ? "Review your final selection, then see how you scored."
        : "Lock in an answer to keep the streak going.";
  }
  resetStatus();

  optionsDiv.innerHTML = "";
  optionsDiv.classList.remove("fade-in");
  void optionsDiv.offsetWidth;
  optionsDiv.classList.add("fade-in");

  q.options.forEach((opt, i) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.dataset.optionIndex = i;
    btn.className = "option-card";
    btn.innerHTML = `
      <span class="option-content">
        <span class="option-index">${String.fromCharCode(65 + i)}</span>
        <span>${opt}</span>
      </span>
      <i class='bx bxs-check-circle'></i>
    `;
    btn.addEventListener("click", () => {
      document
        .querySelectorAll("#options .option-card")
        .forEach((card) => card.classList.remove("is-selected"));
      btn.classList.add("is-selected");
      resetStatus();
    });
    optionsDiv.appendChild(btn);
  });

  updateProgress();
  const isLast = current === quiz.length - 1;
  nextBtn?.classList.toggle("hidden", isLast);
  submitBtn?.classList.toggle("hidden", !isLast);
}

function updateProgress() {
  if (!progressBar) return;
  const denominator = quiz.length > 1 ? quiz.length - 1 : 1;
  const percentage = quiz.length === 1 ? 100 : Math.round((current / denominator) * 100);
  const safePercent = Math.min(Math.max(percentage, 0), 100);
  progressBar.style.width = `${safePercent}%`;
  if (progressLabel) {
    progressLabel.textContent = `${safePercent}%`;
  }
}

function getSelectedIndex() {
  if (!optionsDiv) return null;
  const selected = optionsDiv.querySelector(".option-card.is-selected");
  if (!selected) {
    showStatus("Pick an answer to continue.", "error");
    return null;
  }
  return Number(selected.dataset.optionIndex);
}

nextBtn?.addEventListener("click", () => {
  const selectedIndex = getSelectedIndex();
  if (selectedIndex === null) return;
  recordAnswer(selectedIndex);
  current++;
  loadQuestion();
});

submitBtn?.addEventListener("click", () => {
  const completed = completeQuiz(true);
  if (completed) {
    stopTimer();
  }
});

function recordAnswer(selectedIndex) {
  if (selectedIndex === quiz[current].answer) {
    score++;
  }
}

function calculateXp(finalScore, totalQuestions) {
  const isPerfect = finalScore === totalQuestions;
  let xp = finalScore * BASE_XP_PER_CORRECT;
  if (isPerfect) {
    xp += PERFECT_BONUS_XP;
  }
  return { xp, isPerfect };
}

function completeQuiz(evaluateSelection, options = {}) {
  const { reason } = options;
  let selectedIndex = null;
  if (evaluateSelection) {
    selectedIndex = getSelectedIndex();
    if (selectedIndex === null) return false;
    recordAnswer(selectedIndex);
  }
  const finalScore = score;
  const percentage = Math.round((finalScore / quiz.length) * 100);
  const { xp, isPerfect } = calculateXp(finalScore, quiz.length);
  persistQuizResult(activeLessonId, finalScore, quiz.length, xp);
  let descriptor = "Solid effort. Give it another pass to lock the concepts in.";
  if (reason === "timeout") {
    descriptor = "Time's up. Review the lesson and retry when you're ready.";
  }
  if (percentage === 100) {
    descriptor = "Perfect run. You are ready for the next module!";
  } else if (percentage >= 67) {
    descriptor = "Great work. Review the summary once more for a perfect score.";
  }
  if (quizDesc) {
    const xpMessage = isPerfect
      ? `You earned ${xp} XP (including a ${PERFECT_BONUS_XP} XP perfect bonus).`
      : `You earned ${xp} XP.`;
    quizDesc.textContent = `You scored ${finalScore} out of ${quiz.length} (${percentage}%). ${descriptor} ${xpMessage}`;
  }
  if (xpPreview) {
    xpPreview.textContent = `XP ${xp}`;
  }
  if (startQuiz) {
    startQuiz.textContent = "Retake quiz";
    startQuiz.classList.add("ring-2", "ring-white/60");
  }
  closeModal();
  return true;
}

function showStatus(message, variant = "info") {
  if (!quizStatus) return;
  quizStatus.textContent = message;
  quizStatus.dataset.variant = variant;
  quizStatus.classList.remove("hidden");
}

function resetStatus() {
  if (!quizStatus) return;
  quizStatus.textContent = "";
  quizStatus.classList.add("hidden");
}

function closeModal() {
  quizModal?.classList.add("hidden");
  quizBox?.classList.remove("animate-slideUp");
  quizBox?.classList.add("opacity-0", "translate-y-full");
  current = 0;
  score = 0;
  stopTimer();
  remainingSeconds = QUIZ_DURATION_SECONDS;
  if (progressBar) {
    progressBar.style.width = "0%";
  }
  if (progressLabel) {
    progressLabel.textContent = "0%";
  }
  if (questionCounter) {
    const total = String(quiz.length).padStart(2, "0");
    questionCounter.textContent = `01 of ${total}`;
  }
  if (scorePreview) {
    scorePreview.textContent = `Score 0/${quiz.length}`;
  }
  if (xpPreview) {
    xpPreview.textContent = "XP 0";
  }
  if (quizHelper) {
    quizHelper.textContent = "Select an answer and choose Next.";
  }
  if (quizTimer) {
    quizTimer.textContent = formatTime(remainingSeconds);
  }
  if (timerPill) {
    timerPill.classList.remove("timer-warning");
  }
  resetStatus();
  document.body.classList.remove("modal-open");
}

function startTimer() {
  stopTimer();
  remainingSeconds = QUIZ_DURATION_SECONDS;
  updateTimerDisplay();
  timerInterval = setInterval(() => {
    remainingSeconds -= 1;
    if (remainingSeconds <= 0) {
      remainingSeconds = 0;
      updateTimerDisplay();
      stopTimer();
      completeQuiz(false, { reason: "timeout" });
      return;
    }
    updateTimerDisplay();
  }, 1000);
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
}

function updateTimerDisplay() {
  if (quizTimer) {
    quizTimer.textContent = formatTime(remainingSeconds);
  }
  if (timerPill) {
    timerPill.classList.toggle("timer-warning", remainingSeconds <= 10);
  }
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60)
    .toString()
    .padStart(2, "0");
  const secs = (seconds % 60).toString().padStart(2, "0");
  return `${mins}:${secs}`;
}

// Star Rating System
document.addEventListener("DOMContentLoaded", () => {
  const ratingContainer = document.getElementById("moduleRating");
  const ratingText = document.getElementById("ratingText");

  if (ratingContainer) {
    const stars = ratingContainer.querySelectorAll("i");

    const setVisualRating = (rating) => {
      stars.forEach((star) => {
        if (star.dataset.value <= rating) {
          star.classList.add("filled", "bxs-star");
          star.classList.remove("bx-star");
        } else {
          star.classList.remove("filled", "bxs-star");
          star.classList.add("bx-star");
        }
      });
    };

    stars.forEach((star) => {
      star.addEventListener("mouseover", () => {
        setVisualRating(star.dataset.value);
        ratingText.textContent = `Set as ${star.dataset.value} star(s)`;
      });

      star.addEventListener("click", () => {
        const newRating = star.dataset.value;
        ratingContainer.dataset.currentRating = newRating;
        setVisualRating(newRating);
        ratingText.textContent = `You rated ${newRating} star(s)!`;
        alert(`Thanks for your ${newRating}-star rating!`);
      });
    });

    ratingContainer.addEventListener("mouseout", () => {
      const currentRating = ratingContainer.dataset.currentRating;
      setVisualRating(currentRating);

      if (currentRating == 0) {
        ratingText.textContent = "Rate this module";
      } else {
        ratingText.textContent = `You rated ${currentRating} star(s)!`;
      }
    });
  }
});
