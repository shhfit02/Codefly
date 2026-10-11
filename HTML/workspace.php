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
    header('Location: ../HTML/home.html?workspace=denied');
    exit;
}
$homeUrl = $isAdmin ? '../HTML/adminDashboard.php' : '../HTML/home.html';

$displayRankMap = [
    'bronze' => 'Rookie',
    'silver' => 'Operator',
    'gold' => 'Specialist',
    'ace' => 'Elite Pilot',
    'captain' => 'Commander',
    'commander' => 'Grand Commander',
];

$rawRank = strtolower($rank['title'] ?? 'ace');
$rankTitle = $displayRankMap[$rawRank] ?? ucfirst($rank['title'] ?? 'Ace');
// Chip text: "Admin" for admins, otherwise "<rank> Pilot" without doubling "Elite Pilot Pilot".
$chipLabel = $isAdmin ? 'Admin' : (preg_match('/pilot$/i', $rankTitle) ? $rankTitle : $rankTitle . ' Pilot');
$isLite = isset($_GET['lite']) && $_GET['lite'] === '1';
// Same switch as IS_LESSON_SIM in JS/workspace.js: lesson mode runs a simulator instead of the drone.
$isSim = (($_GET['sim'] ?? '') === '1') || (($_GET['mode'] ?? '') === 'lesson');
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
<body class="scratch-shell <?php echo $isLite ? 'scratch-shell-lite' : ''; ?>" data-home="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <?php if (!$isLite): ?>
    <header class="scratch-header">
      <div class="header-brand">
        <button class="ghost-home-btn" onclick="goHome()" aria-label="Back">
          <i class='bx bx-chevron-left'></i>
        </button>
        <div class="header-copy">
          <h1>CodeFly Workspace</h1>
        </div>
      </div>
      <div class="header-telemetry" aria-label="Drone telemetry">
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
        <div class="header-controls">
          <button type="button" class="stage-btn flag header-flag-btn" id="header-flag-btn">
            <span class="launch-timer" id="launch-timer">00:00</span>
            <span id="header-flag-label">Launch</span>
          </button>
        </div>
        <div class="pilot-chip" id="pilot-chip">
          <button type="button" class="chip-trigger" id="pilot-chip-trigger" aria-haspopup="true" aria-expanded="false">
            <span class="chip-label"><?= htmlspecialchars($chipLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <img src="../Assets/logo3.png" alt="CodeFly logo" class="chip-logo">
            <i class='bx bx-chevron-down'></i>
          </button>
          <div class="chip-menu" id="pilot-chip-menu" role="menu">
            <button type="button" class="chip-option" data-action="undo">
              <span class="chip-icon"><i class='bx bx-undo'></i></span>
              <span class="chip-text">Undo</span>
            </button>
            <button type="button" class="chip-option" data-action="redo">
              <span class="chip-icon"><i class='bx bx-redo'></i></span>
              <span class="chip-text">Redo</span>
            </button>
            <button type="button" class="chip-option" data-action="reset">
              <span class="chip-icon"><i class='bx bx-revision'></i></span>
              <span class="chip-text">Reset</span>
            </button>
            <button type="button" class="chip-option" data-action="save">
              <span class="chip-icon"><i class='bx bx-save'></i></span>
              <span class="chip-text">Save</span>
            </button>
            <button type="button" class="chip-option" data-action="load">
              <span class="chip-icon"><i class='bx bx-import'></i></span>
              <span class="chip-text">Load</span>
            </button>
          </div>
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

  <div class="scratch-body">
    <aside class="scratch-sidebar">
      <?php if (!$isLite): ?>
        <div class="sidebar-head">
          <button type="button" id="connect-btn" class="brand-logo drone-avatar" aria-pressed="false">
            <i class='bx bx-rocket'></i>
          </button>
          <div class="sidebar-copy">
            <p id="sidebar-connection-label">Not Connected</p>
            <span id="sidebar-battery-label">Battery: Unknown</span>
          </div>
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
      <div class="panel-tabs" role="tablist">
        <button class="tab-btn active" data-view="blocks">Blocks</button>
        <button class="tab-btn" data-view="python">Python</button>
      </div>

      <div class="workspace-view active" data-view="blocks" id="blocks-view">
        <div id="blocklyDiv" class="blockly-stage"></div>
      </div>

      <div class="workspace-view" data-view="python" id="python-view">
        <div class="python-status" id="python-status">Ready to transpile blocks.</div>
        <pre><code id="pythonPreview" class="language-python"># Drag blocks to see Python...</code></pre>
      </div>

      <!-- Drone output: workspace.js writes run logs, errors and bridge results here. -->
      <div class="console-footer collapsed" id="consoleFooter">
        <div class="console-resizer" id="consoleResizer" aria-hidden="true"></div>
        <div class="console-toolbar">
          <span class="console-title">Drone Console</span>
          <button type="button" class="console-btn" id="consoleClearBtn">Clear</button>
          <button type="button" class="console-btn" id="consoleToggleBtn" aria-expanded="false" aria-controls="consoleBody" aria-label="Show or hide console">
            <i class='bx bx-chevron-up' id="consoleToggleSymbol"></i>
          </button>
        </div>
        <div class="console-body" id="consoleBody">
          <?php if ($isSim): ?>
            <div class="sim-panel">
              <canvas id="droneSimCanvas" width="360" height="200" aria-label="Drone simulator"></canvas>
              <p id="droneSimStatus" class="sim-status">Ready</p>
            </div>
          <?php endif; ?>
          <div class="console-panels">
            <div class="console-panel active">
              <pre id="consoleOutput" data-placeholder="Press Launch to see drone output here.">Press Launch to see drone output here.</pre>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>

  <xml id="toolbox" style="display: none"></xml>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
  <script src="../JS/workspace-blocks.js"></script>
  <script src="../JS/workspace.js"></script>

  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
