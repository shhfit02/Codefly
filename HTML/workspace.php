<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

require_once '../PHP/db.php';
require_once '../PHP/profile_helpers.php';
require_once '../PHP/auth_helpers.php';

$userId = (int)$_SESSION['user_id'];
// Admins can always open the workspace; the rank gate only applies to students.
$isAdmin = isAdminSession($conn);

try {
    $rank = fetchUserRank($conn, $userId);
} catch (Throwable $e) {
    $rank = PROFILE_DEFAULT_RANK;
}

$conn->close();

if (!workspaceAccess($rank, $isAdmin, 0)['allowed']) {
    header('Location: ../HTML/home.php?workspace=denied');
    exit;
}
$homeUrl = $isAdmin ? '../HTML/adminDashboard.php' : '../HTML/home.php';

$isLite = isset($_GET['lite']) && $_GET['lite'] === '1';
// Same switch as IS_LESSON_SIM in JS/workspace.js: lesson mode runs a simulator instead of the drone.
$isSim = (($_GET['sim'] ?? '') === '1') || (($_GET['mode'] ?? '') === 'lesson');
// Lessons always simulate; the normal workspace lets the user switch between simulator and drone.
$simLocked = ($_GET['mode'] ?? '') === 'lesson' || $isLite;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title><?php echo $isLite ? 'CodeFly Blocks Lite' : 'CodeFly Scratch Lab'; ?></title>
  <link rel="stylesheet" href="../CSS/workspace.css">
  <!-- Offline Blockly bundle (order matters) -->
  <script src="../Assets/blockly/blockly.min.js"></script>
  <script src="../Assets/blockly/python_compressed.js"></script>
  <script src="../Assets/blockly/msg_en.js"></script>
  
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
</head>
<body class="scratch-shell <?php echo $isLite ? 'scratch-shell-lite' : ''; ?>" data-home="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>" data-user="<?= $userId ?>" data-sim-locked="<?= $simLocked ? '1' : '0' ?>">
  <?php if (!$isLite): ?>
    <header class="scratch-header">
      <div class="header-brand">
        <button class="ghost-home-btn" onclick="goHome()" aria-label="Back">
          <i class='bx bx-chevron-left'></i>
        </button>
        <!-- Same logos as the login page, on a white pill so the dark lettering stays readable. -->
        <div class="brand-pill">
          <img src="../Assets/codefly_header_logo.png" alt="CodeFly" class="brand-pill__codefly">
          <span class="brand-pill__powered">
            <span class="brand-pill__label">Powered by</span>
            <img src="../Assets/powered_header.png" alt="Universiti Pendidikan Sultan Idris, Fakulti Komputeran dan Meta-Teknologi">
          </span>
        </div>
        <div class="header-copy">
          <h1>Workspace</h1>
        </div>
      </div>
      <div class="header-telemetry is-offline" id="header-telemetry" aria-label="Drone telemetry">
        <span class="telemetry-offline" id="telemetry-offline">No drone data</span>
        <div class="telemetry-chip">
          <p>Pitch</p>
          <span id="telemetry-pitch">--</span>
        </div>
        <div class="telemetry-chip">
          <p>Roll</p>
          <span id="telemetry-roll">--</span>
        </div>
        <div class="telemetry-chip">
          <p>Yaw</p>
          <span id="telemetry-yaw">--</span>
        </div>
      </div>
      <div class="header-actions">
        <?php if (!$simLocked): ?>
          <div class="mode-switch" role="radiogroup" aria-label="Run on">
            <button type="button" class="mode-option" data-mode="sim" role="radio" aria-checked="false" title="Run on the on-screen simulator (no drone needed)">
              <i class='bx bx-desktop'></i><span>Simulator</span>
            </button>
            <button type="button" class="mode-option" data-mode="drone" role="radio" aria-checked="false" title="Run on the DJI Tello">
              <i class='bx bx-paper-plane'></i><span>Drone</span>
            </button>
          </div>
        <?php endif; ?>
        <div class="header-controls">
          <button type="button" class="stage-btn flag header-flag-btn" id="header-flag-btn" title="Run the program (Esc stops and lands)">
            <span class="launch-timer" id="launch-timer">00:00</span>
            <span id="header-flag-label">Launch</span>
          </button>
        </div>
      </div>
    </header>
  <?php else: ?>
    <header class="scratch-header lite-header" style="padding: 10px 14px; background: #eef2ff;">
      <div class="header-actions" style="margin-left:auto; gap: 10px; display: flex;">
        <div class="header-controls">
          <button type="button" class="stage-btn flag header-flag-btn" id="header-flag-btn">
            <span class="launch-timer" id="launch-timer">00:00</span>
            <span id="header-flag-label">Launch</span>
          </button>
        </div>
      </div>
    </header>
  <?php endif; ?>

  <!-- Shown only on narrow screens (see workspace.css): block dragging needs room. -->
  <div class="small-screen-notice" id="small-screen-notice" role="note">
    <i class='bx bx-laptop' aria-hidden="true"></i>
    <span>The block workspace works best on a laptop or tablet. On a phone, the block categories are below the canvas.</span>
    <button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button>
  </div>

  <div class="scratch-body">
    <aside class="scratch-sidebar">
      <?php if (!$isLite): ?>
        <div class="drone-card" id="drone-card" data-state="offline">
          <div class="drone-card__status">
            <span class="drone-card__dot" aria-hidden="true"></span>
            <div class="sidebar-copy">
              <p id="sidebar-connection-label">Not Connected</p>
              <span id="sidebar-battery-label">Battery: Unknown</span>
            </div>
          </div>
          <div class="drone-card__battery" aria-hidden="true"><span id="battery-bar"></span></div>
          <button type="button" id="connect-btn" class="drone-connect-btn" aria-pressed="false">
            <i class='bx bx-wifi'></i><span id="connect-btn-label">Connect drone</span>
          </button>
          <p class="drone-card__sim-note">Simulator mode: no drone needed. Switch to <strong>Drone</strong> above to fly the Tello.</p>
        </div>
      <?php endif; ?>

      <div class="categories" id="categories">
        <button class="category cat-takeoff active" data-category="Takeoff">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Takeoff</p>
            <p class="cat-sub">Lift and launch</p>
          </div>
        </button>
        <button class="category cat-navigation" data-category="Navigation">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Navigation</p>
            <p class="cat-sub">Move and turn</p>
          </div>
        </button>
        <button class="category cat-camera" data-category="Camera">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Camera</p>
            <p class="cat-sub">Shots &amp; angles</p>
          </div>
        </button>
        <button class="category cat-flip" data-category="Flip">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Flip</p>
            <p class="cat-sub">Aerial tricks</p>
          </div>
        </button>
        <button class="category cat-loops" data-category="Loops">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Loops</p>
            <p class="cat-sub">Repeat actions</p>
          </div>
        </button>
        <button class="category cat-logic" data-category="Logic">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Logic</p>
            <p class="cat-sub">Conditions</p>
          </div>
        </button>
        <button class="category cat-math" data-category="Math">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Math</p>
            <p class="cat-sub">Numbers &amp; ops</p>
          </div>
        </button>
        <button class="category cat-variable" data-category="Variables">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Variables</p>
            <p class="cat-sub">Store values</p>
          </div>
        </button>
        <button class="category cat-functions" data-category="Functions">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Functions</p>
            <p class="cat-sub">Reusable steps</p>
          </div>
        </button>
        <button class="category cat-land" data-category="Land">
          <span class="category-dot"></span>
          <div>
            <p class="cat-title">Land</p>
            <p class="cat-sub">Return safely</p>
          </div>
        </button>
      </div>

    </aside>

    <section class="workspace-panel">
      <div class="panel-head">
        <div class="panel-tabs" role="tablist">
          <button class="tab-btn active" data-view="blocks">Blocks</button>
          <button class="tab-btn sim-only" data-view="python">Python</button>
          <!-- Drone mode: the run log replaces the Python tab (the simulator panel is hidden). -->
          <button class="tab-btn drone-only" data-view="log">Flight log</button>
        </div>
        <div class="canvas-toolbar" role="toolbar" aria-label="Program tools">
          <span class="program-name" id="program-name" title="Current program">Untitled</span>
          <button type="button" class="tool-btn" data-action="undo" title="Undo (Ctrl+Z)" aria-label="Undo"><i class='bx bx-undo'></i></button>
          <button type="button" class="tool-btn" data-action="redo" title="Redo (Ctrl+Y)" aria-label="Redo"><i class='bx bx-redo'></i></button>
          <button type="button" class="tool-btn" data-action="reset" title="Clear all blocks" aria-label="Clear all blocks"><i class='bx bx-trash'></i></button>
          <?php if (!$isLite): ?>
          <span class="tool-sep" aria-hidden="true"></span>
          <button type="button" class="tool-btn tool-btn--text" data-action="save" title="Save to your account"><i class='bx bx-save'></i><span>Save</span></button>
          <button type="button" class="tool-btn tool-btn--text" data-action="load" title="Open one of your saved programs"><i class='bx bx-folder-open'></i><span>My programs</span></button>
          <?php endif; ?>
        </div>
      </div>

      <div class="workspace-view active" data-view="blocks" id="blocks-view">
        <div id="blocklyDiv" class="blockly-stage"></div>
        <!-- Floating simulator (simulator mode) with the run log under it. In drone mode the log
             moves to the Flight log tab. Drag by the title bar; minimise with the button. -->
        <section class="sim-float" id="simFloat" aria-label="Drone simulator">
          <header class="sim-float__bar" id="simFloatBar">
            <i class='bx bx-move' aria-hidden="true"></i>
            <span class="sim-float__title">Simulator</span>
            <span id="droneSimStatus" class="sim-status">Ready</span>
            <button type="button" class="sim-float__toggle" id="simFloatToggle" aria-expanded="true" aria-label="Minimise simulator"><i class='bx bx-minus'></i></button>
          </header>
          <div class="sim-float__body">
            <canvas id="droneSimCanvas" width="480" height="270" aria-label="Drone simulator, seen from above"></canvas>
            <dl class="sim-readout">
              <dt>Height</dt><dd id="simAltitude">0 cm</dd>
              <dt>Heading</dt><dd id="simHeading">0&deg;</dd>
              <dt class="sim-hint">Forward is up &middot; 1 square = 50 cm</dt>
            </dl>
            <!-- workspace.js writes each step, errors and bridge results here; it is moved into the
                 Flight log tab in drone mode (see applyRunMode). -->
            <pre id="consoleOutput" class="flight-log" aria-live="polite">Press Launch to run your program.</pre>
          </div>
        </section>
      </div>

      <div class="workspace-view" data-view="python" id="python-view">
        <pre><code id="pythonPreview" class="language-python"># Drag blocks to see Python...</code></pre>
      </div>

      <div class="workspace-view" data-view="log" id="log-view">
        <div class="flight-log-slot" id="flightLogSlot"></div>
      </div>

    </section>

  </div>

  <xml id="toolbox" style="display: none"></xml>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
  <script src="../JS/workspace-blocks.js"></script>
  <script src="../JS/workspace.js"></script>
</body>
</html>
