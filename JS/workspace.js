const CONSOLE_MIN_HEIGHT = 140;
const CONSOLE_MAX_HEIGHT = 520;
let lastConsoleHeight = 260;
const urlParams = new URLSearchParams(window.location.search);
const LESSON_ID = urlParams.get('lesson') || '';
const IS_LESSON_SIM = urlParams.get('sim') === '1' || urlParams.get('mode') === 'lesson';
let lessonToolboxContents = null;
let lessonDefaultCategory = null;
// simMode: run on the on-screen simulator instead of the Tello. Lessons always simulate;
// the normal workspace has a Simulator / Drone switch (see initRunMode).
let simMode = IS_LESSON_SIM;
let commandReady = false;
let stopRequested = false;

// Harden Blockly Python codegen once Blockly loads.
(function hardenBlocklyPython() {
  const wait = setInterval(() => {
    if (typeof Blockly === "undefined" || !Blockly.Python) return;

    clearInterval(wait);

    const original = Blockly.Python.blockToCode;

    Blockly.Python.blockToCode = function (block, opt_this) {
      if (!block) return '';

      // Blockly 10+ looks generators up in forBlock (not on the generator object itself).
      if (this.forBlock && !this.forBlock[block.type]) {
        console.warn("Auto-creating Python generator for:", block.type);

        // Create a safe stub instead of throwing
        this.forBlock[block.type] = function () {
          return `# [MISSING] ${block.type}\n`;
        };
      }

      return original.call(this, block, opt_this);
    };

    console.log("Blockly Python hardened against missing generators");
  }, 50);
})();

function setRunEnabled(enabled) {
  const runButton = document.getElementById('header-flag-btn');
  const headerFlagLabel = document.getElementById('header-flag-label');
  if (!runButton) return;
  // Keep visually marked but still clickable so we can surface messaging instead of blocking clicks
  runButton.setAttribute('aria-disabled', (!enabled).toString());
  runButton.classList.toggle('is-disabled', !enabled);
  if (!enabled) {
    runButton.classList.remove('is-running');
    runButton.setAttribute('aria-pressed', 'false');
    if (headerFlagLabel) headerFlagLabel.textContent = 'Launch';
  }
}

function setLaunchButtonIdle() {
  if (typeof window !== 'undefined' && typeof window.updateLaunchButton === 'function') {
    window.updateLaunchButton(false);
    return;
  }
  const runButton = document.getElementById('header-flag-btn');
  const headerFlagLabel = document.getElementById('header-flag-label');
  const launchTimerEl = document.getElementById('launch-timer');
  if (runButton) {
    runButton.classList.remove('is-running');
    runButton.setAttribute('aria-pressed', 'false');
  }
  if (headerFlagLabel) {
    headerFlagLabel.textContent = 'Launch';
  }
  if (launchTimerEl) {
    launchTimerEl.textContent = '00:00';
  }
}
const lessonToolboxConfigs = {
  'block-01': {
    categories: {
      Takeoff: ['tello_takeoff', 'tello_takeoff_delay', 'tello_emergency'],
      Navigation: ['tello_move_direction', 'tello_turn', 'tello_wait'],
      Land: ['tello_land']
    },
    defaultCategory: 'Takeoff'
  },
  'block-02': {
    categories: {
      Takeoff: ['tello_takeoff', 'tello_takeoff_delay'],
      Navigation: ['tello_move_direction', 'tello_turn', 'tello_wait'],
      Land: ['tello_land']
    },
    defaultCategory: 'Navigation'
  },
  'block-03': {
    categories: {
      Takeoff: ['tello_takeoff'],
      Navigation: ['tello_move_direction'],
      Loops: ['tello_wait'],
      Land: ['tello_land']
    },
    defaultCategory: 'Navigation'
  },
  'block-04': {
    categories: {
      Loops: ['controls_repeat_ext', 'controls_whileUntil'],
      Navigation: ['tello_move_direction', 'tello_turn'],
      Takeoff: ['tello_takeoff'],
      Land: ['tello_land']
    },
    defaultCategory: 'Loops'
  },
  'block-05': {
    categories: {
      Math: ['math_number', 'math_arithmetic'],
      Logic: ['logic_compare', 'logic_operation', 'logic_boolean'],
      Navigation: ['tello_move_direction', 'tello_turn']
    },
    defaultCategory: 'Math'
  },
  'block-06': {
    categories: {
      Logic: ['controls_if'],
      Navigation: ['tello_move_direction', 'tello_turn'],
      Takeoff: ['tello_takeoff'],
      Land: ['tello_land']
    },
    defaultCategory: 'Logic'
  },
  'block-07': {
    categories: {
      Logic: ['logic_compare', 'logic_operation', 'logic_boolean'],
      Takeoff: ['tello_takeoff_delay'],
      Navigation: ['tello_move_direction'],
      Land: ['tello_land']
    },
    defaultCategory: 'Logic'
  },
  'block-08': {
    categories: {
      Flip: ['tello_flip_forward', 'tello_flip_backward', 'tello_flip_left', 'tello_flip_right'],
      Takeoff: ['tello_takeoff'],
      Land: ['tello_land']
    },
    defaultCategory: 'Flip'
  },
  'block-09': {
    categories: {
      Variables: ['variables_get', 'variables_set'],
      Math: ['math_number'],
      Navigation: ['tello_move_direction', 'tello_turn']
    },
    defaultCategory: 'Variables'
  }
};


function toggleConsole() {
  const footer = document.getElementById("consoleFooter");
  if (!footer) return;
  const shouldCollapse = !footer.classList.contains("collapsed");
  setConsoleCollapsed(shouldCollapse);
}

function filterMissionBlocks(categoryName, allowedTypes) {
  const baseBlocks = toolboxContents[categoryName] || [];
  if (!allowedTypes || !allowedTypes.length) return baseBlocks;
  return baseBlocks.filter((item) => {
    const blockType = item.type || item.kind;
    return allowedTypes.includes(blockType);
  });
}

function applyMissionToolboxConfig() {
  if (!LESSON_ID || !lessonToolboxConfigs[LESSON_ID]) return;
  const config = lessonToolboxConfigs[LESSON_ID];
  lessonToolboxContents = {};
  lessonDefaultCategory = config.defaultCategory || null;

  Object.entries(config.categories || {}).forEach(([categoryName, types]) => {
    lessonToolboxContents[categoryName] = filterMissionBlocks(categoryName, types);
  });

  const categoriesContainer = document.getElementById('categories');
  const categories = categoriesContainer ? categoriesContainer.querySelectorAll('.category') : [];
  if (categories && config.categories) {
    categories.forEach((cat) => {
      const catName = cat.dataset.category;
      if (!config.categories[catName]) {
        cat.style.display = 'none';
      } else {
        cat.style.display = '';
      }
    });
  }
}

function clampConsoleHeight(value) {
  return Math.min(CONSOLE_MAX_HEIGHT, Math.max(CONSOLE_MIN_HEIGHT, value));
}

function updateConsoleIcon(isCollapsed) {
  const icon = document.getElementById("consoleToggleSymbol");
  if (icon) {
    icon.classList.toggle("bx-chevron-up", isCollapsed);
    icon.classList.toggle("bx-chevron-down", !isCollapsed);
  }
}

function setConsoleCollapsed(collapsed) {
  const footer = document.getElementById("consoleFooter");
  if (!footer) return;

  if (collapsed) {
    const currentHeight = footer.getBoundingClientRect().height;
    if (!isNaN(currentHeight) && currentHeight > 0) {
      lastConsoleHeight = clampConsoleHeight(currentHeight);
    }
    footer.classList.add("collapsed");
    footer.style.height = "";
  } else {
    footer.classList.remove("collapsed");
    const targetHeight = clampConsoleHeight(
      lastConsoleHeight || footer.getBoundingClientRect().height || 240
    );
    footer.style.height = `${targetHeight}px`;
  }

  const isCollapsed = footer.classList.contains("collapsed");
  updateConsoleIcon(isCollapsed);
  const toggleBtn = document.getElementById("consoleToggleBtn");
  if (toggleBtn) {
    toggleBtn.setAttribute("aria-expanded", (!isCollapsed).toString());
  }
  setTimeout(resizeBlockly, 500);
}

function openConsole() {
  setConsoleCollapsed(false);
}

function clearActiveConsolePanel() {
  const activePanel = document.querySelector(".console-panel.active pre");
  if (activePanel) {
    activePanel.textContent = activePanel.dataset.placeholder || "";
    activePanel.scrollTop = 0;
    activePanel.style.color = "";
  }
}

function initConsoleResizer() {
  const footer = document.getElementById("consoleFooter");
  const resizer = document.getElementById("consoleResizer");
  if (!footer || !resizer) return;

  let startY = 0;
  let startHeight = footer.getBoundingClientRect().height;

  const handleDrag = (clientY) => {
    const delta = clientY - startY;
    const newHeight = clampConsoleHeight(startHeight - delta);
    footer.style.height = `${newHeight}px`;
    lastConsoleHeight = newHeight;
    resizeBlockly();
  };

  const stopResize = () => {
    document.body.classList.remove("resizing-console");
    footer.classList.remove("resizing");
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    window.removeEventListener("touchmove", onTouchMove);
    window.removeEventListener("touchend", onTouchEnd);
    setTimeout(resizeBlockly, 200);
  };

  const onMouseMove = (e) => {
    e.preventDefault();
    handleDrag(e.clientY);
  };

  const onMouseUp = (e) => {
    e.preventDefault();
    stopResize();
  };

  const onTouchMove = (e) => {
    if (e.touches.length !== 1) return;
    handleDrag(e.touches[0].clientY);
  };

  const onTouchEnd = () => stopResize();

  const startResize = (clientY) => {
    startY = clientY;
    startHeight = footer.getBoundingClientRect().height || lastConsoleHeight;
    openConsole();
    document.body.classList.add("resizing-console");
    footer.classList.add("resizing");
  };

  resizer.addEventListener("mousedown", (e) => {
    if (e.button !== 0) return;
    e.preventDefault();
    startResize(e.clientY);
    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
  });

  resizer.addEventListener("touchstart", (e) => {
    if (e.touches.length !== 1) return;
    startResize(e.touches[0].clientY);
    window.addEventListener("touchmove", onTouchMove);
    window.addEventListener("touchend", onTouchEnd);
  });
}

// NOTE: This object is NOT used.
// The real definitions are in 'workspace-blocks.js'
const toolboxXmls = {};

// Resize Blockly to fill remaining space
function resizeBlockly() {
  // Notify Blockly that its container has resized
  Blockly.svgResize(workspace);
}

// Call on load and resize
window.addEventListener('load', resizeBlockly);
window.addEventListener('resize', resizeBlockly);
// The canvas shrinks and grows with the console (it fills the panel), so follow its size.
if (typeof ResizeObserver !== 'undefined') {
  window.addEventListener('load', () => {
    const stage = document.getElementById('blocklyDiv');
    if (stage) new ResizeObserver(() => resizeBlockly()).observe(stage);
  });
}

// Category click handlers
document.addEventListener('DOMContentLoaded', function() {
  initRunMode();
  const footer = document.getElementById("consoleFooter");
  if (footer) {
    updateConsoleIcon(footer.classList.contains("collapsed"));
  }
  const consoleToggleBtn = document.getElementById("consoleToggleBtn");
  if (consoleToggleBtn) {
    consoleToggleBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleConsole();
    });
  }
  const consoleClearBtn = document.getElementById("consoleClearBtn");
  if (consoleClearBtn) {
    consoleClearBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      clearActiveConsolePanel();
    });
  }
  initConsoleResizer();
  // --- Logic for Active Category ---
  const categoriesContainer = document.getElementById('categories');
  const categories = categoriesContainer ? categoriesContainer.querySelectorAll('.category') : [];

  // Function to handle setting the active category
  function setActiveCategory(clickedCategory) {
    if (!clickedCategory) return;
    categories.forEach(cat => cat.classList.remove('active'));
    clickedCategory.classList.add('active');

    const categoryName =
      clickedCategory.dataset.category ||
      clickedCategory.querySelector('.cat-title')?.textContent ||
      clickedCategory.textContent;
    if (categoryName) {
      updateToolbox(categoryName.trim());
    }
  }

  // Add a click listener to each category
  if (categoriesContainer) {
    categories.forEach(cat => {
      cat.addEventListener('click', function() {
        setActiveCategory(this);
      });
    });
  }
  
  // --- End of Active Category Logic ---

  applyMissionToolboxConfig();

  // Load default category on page load
  let defaultCategory = categoriesContainer
    ? (lessonDefaultCategory
      ? categoriesContainer.querySelector(`.category[data-category="${lessonDefaultCategory}"]`)
      : categoriesContainer.querySelector('.category'))
    : null;
  if (!defaultCategory && categoriesContainer) {
    defaultCategory = Array.from(categoriesContainer.querySelectorAll('.category')).find(cat => cat.style.display !== 'none') || null;
  }
  if (defaultCategory) {
    setActiveCategory(defaultCategory);
  }

  // --- MODIFIED: Toolbar button handlers ---
  const headerFlagBtn = document.getElementById('header-flag-btn');
  const headerFlagLabel = document.getElementById('header-flag-label');
  const launchTimerEl = document.getElementById('launch-timer');
  const runButton = headerFlagBtn;
  
  function attachBounceHandler(button, handler) {
    if (!button) return;
    button.addEventListener('click', (event) => {
      button.classList.remove('bouncing');
      // Force reflow to restart animation
      void button.offsetWidth;
      button.classList.add('bouncing');
      handler(event);
    });
    button.addEventListener('animationend', () => {
      button.classList.remove('bouncing');
    });
  }

  let isRunning = false;
  let launchTimerInterval = null;
  let launchStartTime = null;

  function updateLaunchTimerDisplay() {
    if (!launchStartTime || !launchTimerEl) return;
    const elapsedMs = Date.now() - launchStartTime;
    const totalSeconds = Math.max(0, Math.floor(elapsedMs / 1000));
    const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');
    launchTimerEl.textContent = `${minutes}:${seconds}`;
  }

  function startLaunchTimer() {
    launchStartTime = Date.now();
    updateLaunchTimerDisplay();
    if (launchTimerInterval) clearInterval(launchTimerInterval);
    launchTimerInterval = setInterval(updateLaunchTimerDisplay, 1000);
  }

  function stopLaunchTimer() {
    if (launchTimerInterval) {
      clearInterval(launchTimerInterval);
      launchTimerInterval = null;
    }
    launchStartTime = null;
    if (launchTimerEl) {
      launchTimerEl.textContent = '00:00';
    }
  }

  function updateLaunchButton(running) {
    if (!headerFlagBtn) return;
    isRunning = running;
    headerFlagBtn.classList.toggle('is-running', running);
    headerFlagBtn.setAttribute('aria-pressed', running ? 'true' : 'false');
    if (headerFlagLabel) {
      headerFlagLabel.textContent = running ? 'Stop' : 'Launch';
    }
    if (running) {
      startLaunchTimer();
    } else {
      stopLaunchTimer();
    }
  }

  window.updateLaunchButton = updateLaunchButton;
  window.isProgramRunning = () => isRunning;

  // Esc = stop and land. Capture phase, so Blockly field editors cannot swallow it.
  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isRunning) {
      event.preventDefault();
      stopCode();
      updateLaunchButton(false);
    }
  }, true);


  attachBounceHandler(headerFlagBtn, () => {
    if (isRunning) {
      stopCode();
      updateLaunchButton(false);
    } else {
      // Mark running first: runCode resets the button itself when it stops early
      // (not connected, no blocks), which used to be overwritten back to "Stop".
      updateLaunchButton(true);
      runCode();
    }
  });

  // Canvas toolbar (Undo, Redo, Clear, Save, My programs)
  const toolActions = {
    undo,
    redo,
    reset: resetWorkspace,
    save: saveProgram,
    load: openProgramsDialog,
  };
  document.querySelectorAll('.canvas-toolbar .tool-btn[data-action]').forEach((btn) => {
    btn.addEventListener('click', () => toolActions[btn.dataset.action]?.());
  });
  restoreDraft();

  // --- Workspace view tabs ---
  const viewButtons = document.querySelectorAll('.tab-btn[data-view]');
  const workspaceViews = document.querySelectorAll('.workspace-view');

  function setWorkspaceTab(target) {
    viewButtons.forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.view === target);
    });

    workspaceViews.forEach((view) => {
      const isMatch =
        view.dataset.view === target ||
        view.id === `${target}-view`;
      view.classList.toggle('active', isMatch);
      view.style.display = isMatch ? '' : 'none'; // .active lays it out (flex)
    });

    if (target === 'blocks') {
      resizeBlockly();
    } else if (target === 'python') {
      updatePythonPreview();
    }
  }

  viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.view || 'blocks';
      setWorkspaceTab(target);
    });
  });

  // Ensure both the UI and the preview start in sync.
  setWorkspaceTab('blocks');
  updatePythonPreview();
  setPythonStatus('Ready to transpile blocks.', 'idle');
  initDroneSim();
});


// This function correctly reads from 'toolboxContents'
// (from workspace-blocks.js) and loads the blocks.
function updateToolbox(categoryName) {
  
  // 1. Get the array of blocks from 'toolboxContents'
  //    (which is defined in workspace-blocks.js)
  const source = lessonToolboxContents || toolboxContents;
  const blocks = source[categoryName];

  if (blocks) {
    // 2. Pass the array of blocks directly to updateToolbox.
    //    Blockly will build the flyout from this.
    workspace.updateToolbox({
      'kind': 'flyoutToolbox',
      'contents': blocks
    });
    workspace.resize();
  } else {
    console.error("No toolbox contents found for category:", categoryName);
  }
}

// Safety net: make sure Python generators exist for all custom Tello blocks
function ensureTelloPythonGenerators() {
  const generator =
    typeof pythonGenerator !== "undefined"
      ? pythonGenerator
      : typeof Blockly !== "undefined"
      ? (Blockly.Python || Blockly.PythonGenerator || null)
      : null;
  if (!generator) return;
  if (typeof Blockly !== "undefined" && !Blockly.Python) {
    Blockly.Python = generator;
  }
  generator.forBlock = generator.forBlock || generator;

  // Force-set all custom generators so they always exist, even if some script load order changes.
  generator.forBlock['tello_takeoff'] = () => 'tello.takeoff()\n';
  generator.forBlock['tello_takeoff_delay'] = (block) => {
    const delay = Blockly.Python.valueToCode(block, 'DELAY', Blockly.Python.ORDER_ATOMIC) || 0;
    return `time.sleep(${delay})\ntello.takeoff()\n`;
  };
  generator.forBlock['tello_land_delay'] = (block) => {
    const delay = Blockly.Python.valueToCode(block, 'DELAY', Blockly.Python.ORDER_ATOMIC) || 0;
    return `tello.land()\ntime.sleep(${delay})\ntello.takeoff()\n`;
  };
  // Tello has no "bounce" SDK command; tello_bridge.py provides tello.bounce() (up/down pulses).
  generator.forBlock['tello_bounce'] = () => 'tello.bounce()\n';
  generator.forBlock['tello_circle'] = (block) => {
    const duration = Blockly.Python.valueToCode(block, 'DURATION', Blockly.Python.ORDER_ATOMIC) || 0;
    return `tello.send_rc_control(0, 40, 0, 40)
time.sleep(${duration})
tello.send_rc_control(0, 0, 0, 0)
`;
  };
  generator.forBlock['tello_spin_360'] = () => 'tello.rotate_clockwise(360)\n';
  generator.forBlock['tello_take_photo'] = () => 'tello.take_picture()\n';
  generator.forBlock['tello_take_photo_burst'] = (block) => {
    const count = Blockly.Python.valueToCode(block, 'COUNT', Blockly.Python.ORDER_ATOMIC) || 0;
    const interval = Blockly.Python.valueToCode(block, 'INTERVAL', Blockly.Python.ORDER_ATOMIC) || 0;
    return `
for _ in range(int(${count})):
    tello.take_picture()
    time.sleep(${interval})
`;
  };
  generator.forBlock['tello_land'] = () => 'tello.land()\n';
  // EMERGENCY STOP lands the drone and ends the program (tello_bridge.py emergency_land);
  // it no longer calls tello.emergency(), which cuts the motors in mid-air.
  generator.forBlock['tello_emergency'] = () => 'tello.emergency_land()\n';
  generator.forBlock['tello_move_direction'] = (block) => {
    const dir = block.getFieldValue('DIRECTION');
    const dist = Blockly.Python.valueToCode(block, 'DISTANCE', Blockly.Python.ORDER_ATOMIC) || 0;
    const unit = block.getFieldValue('UNIT') || 'cm';
    const factor = unit === 'in' ? 2.54 : unit === 'm' ? 100 : 1;
    let distExpr = dist;
    if (/^-?\d+(\.\d+)?$/.test(String(dist))) {
      // Literal distance: convert here so the drone (and the op parser) get whole centimetres.
      distExpr = String(Math.round(Number(dist) * factor));
    } else {
      // Math/variable blocks can produce floats (e.g. 50.0); the Tello SDK only accepts integers.
      distExpr = factor !== 1 ? `int(round((${dist})*${factor}))` : `int(round(${dist}))`;
    }
    const methodMap = {
      forward: 'move_forward',
      back: 'move_back',
      left: 'move_left',
      right: 'move_right',
      up: 'move_up',
      down: 'move_down',
    };
    const method = methodMap[dir] || 'move_forward';
    return `tello.${method}(${distExpr})\n`;
  };
  generator.forBlock['tello_turn'] = (block) => {
    // Field/input names must match the block definition in workspace-blocks.js (TURN, DEGREES).
    const dir = block.getFieldValue('TURN');   // cw / ccw
    const rawAngle = Blockly.Python.valueToCode(block, 'DEGREES', Blockly.Python.ORDER_ATOMIC) || 0;
    // Tello only accepts whole degrees; wrap non-literal values (math/variables) in int(round()).
    const angle = /^-?\d+$/.test(String(rawAngle)) ? rawAngle : `int(round(${rawAngle}))`;

    if (dir === 'ccw') {
      return `tello.rotate_counter_clockwise(${angle})\n`;
    }
    return `tello.rotate_clockwise(${angle})\n`;
  };
  generator.forBlock['tello_move_rc'] = (block) => {
    const direction = block.getFieldValue('DIRECTION');
    const seconds = Blockly.Python.valueToCode(block, 'SECONDS', Blockly.Python.ORDER_ATOMIC) || 0;
    const speed = Blockly.Python.valueToCode(block, 'SPEED', Blockly.Python.ORDER_ATOMIC) || 0;
    let a = 0, b = 0;
    if (direction === 'forward') {
      b = speed;
    } else if (direction === 'backward') {
      b = `-${speed}`;
    } else if (direction === 'left') {
      a = `-${speed}`;
    } else if (direction === 'right') {
      a = speed;
    }
    return `
tello.send_rc_control(${a}, ${b}, 0, 0)
time.sleep(${seconds})
tello.send_rc_control(0, 0, 0, 0)
`;
  };
  generator.forBlock['tello_flip_left'] = () => "tello.flip('l')\n";
  generator.forBlock['tello_flip_right'] = () => "tello.flip('r')\n";
  generator.forBlock['tello_flip_forward'] = () => "tello.flip('f')\n";
  generator.forBlock['tello_flip_backward'] = () => "tello.flip('b')\n";
  generator.forBlock['print_message'] = (block) => {
    const msg = Blockly.Python.valueToCode(block, 'TEXT', Blockly.Python.ORDER_ATOMIC) || '""';
    return `print(${msg})\n`;
  };
  generator.forBlock['tello_wait'] = (block) => {
    const seconds = Blockly.Python.valueToCode(block, 'SECONDS', Blockly.Python.ORDER_ATOMIC) || 0;
    return `time.sleep(${seconds})\n`;
  };
  generator.forBlock['tello_read_battery'] = () => ['tello.get_battery()', Blockly.Python.ORDER_FUNCTION_CALL];
  generator.forBlock['tello_read_height'] = () => ['tello.get_height()', Blockly.Python.ORDER_FUNCTION_CALL];

  // Also set no-op JavaScript generators to avoid Blockly defaulting to JavaScript and throwing.
  if (Blockly.JavaScript) {
    Blockly.JavaScript['tello_takeoff'] = () => '// takeoff (python only)\n';
    Blockly.JavaScript['tello_land'] = () => '// land (python only)\n';
    Blockly.JavaScript['tello_move_direction'] = () => '// move direction (python only)\n';
    Blockly.JavaScript['tello_turn'] = () => '// turn (python only)\n';
    Blockly.JavaScript['tello_move_rc'] = () => '// move rc (python only)\n';
    Blockly.JavaScript['tello_flip_left'] = () => '// flip left (python only)\n';
    Blockly.JavaScript['tello_flip_right'] = () => '// flip right (python only)\n';
    Blockly.JavaScript['tello_flip_forward'] = () => '// flip forward (python only)\n';
    Blockly.JavaScript['tello_flip_backward'] = () => '// flip backward (python only)\n';
    Blockly.JavaScript['print_message'] = () => '// print (python only)\n';
    Blockly.JavaScript['tello_wait'] = () => '// wait (python only)\n';
    Blockly.JavaScript['tello_read_battery'] = () => ['0', Blockly.JavaScript.ORDER_ATOMIC];
    Blockly.JavaScript['tello_read_height'] = () => ['0', Blockly.JavaScript.ORDER_ATOMIC];
  }
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function sendDroneCommand(op) {
  const response = await fetch(`${TELLO_BRIDGE_URL}/command`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(op),
  });
  const data = await response.json();
  if (!response.ok) {
    throw new Error(data?.error || data?.status || 'Command failed');
  }
  return data;
}

async function executeOpsSequentially(ops, logToConsole) {
  for (const op of ops) {
    if (stopRequested) {
      return false;
    }
    if (op.type === 'wait') {
      logToConsole?.(`--- [SIM] Wait ${op.value || 0}s`);
      await sleep((op.value || 0) * 1000);
      if (stopRequested) {
        return false;
      }
      continue;
    }
    try {
      logToConsole?.(`--- [CMD] ${op.type} ${op.dir ?? ''} ${op.value ?? ''}`.trim());
      const result = await sendDroneCommand(op);
      if (op.type === 'emergency') {
        return 'emergency';
      }
      logToConsole?.(`--- [OK] ${op.type} done${result?.photo ? ` (saved ${result.photo})` : ''}`);
    } catch (err) {
      logToConsole?.(`--- [ERROR] ${op.type} failed: ${err.message}`);
      return false;
    }
  }
  return true;
}

// Tello SDK limits (same as tello_bridge.py). Checked before the first command is sent,
// so the drone never takes off and then fails mid-flight on an out-of-range block.
const TELLO_LIMITS = {
  move: { min: 20, max: 500, label: 'Distance', unit: 'cm' },
  turn: { min: 1, max: 360, label: 'Turn angle', unit: 'degrees' },
};

function findOutOfRangeOps(ops) {
  return ops
    .filter((op) => {
      const limit = TELLO_LIMITS[op.type];
      const value = Math.abs(Number(op.value));
      return limit && (value < limit.min || value > limit.max);
    })
    .map((op) => {
      const limit = TELLO_LIMITS[op.type];
      return `${op.type} ${op.dir ?? ''} ${op.value}: ${limit.label} must be ${limit.min}-${limit.max} ${limit.unit}`;
    });
}

// Follows a /run_script run until the bridge reports it finished, failed or was stopped.
async function waitForScriptResult(logToConsole) {
  for (;;) {
    await sleep(700);
    let data;
    try {
      const response = await fetch(`${TELLO_BRIDGE_URL}/script_status`);
      data = await response.json();
    } catch (err) {
      return { state: 'error', error: 'Lost contact with the bridge while the script was running.' };
    }
    if (data.state !== 'running') {
      (data.photos || []).forEach((path) => logToConsole?.(`--- [PHOTO] saved ${path}`));
      return data;
    }
  }
}

// Register generators once on load, to avoid first-run misses
ensureTelloPythonGenerators();
// Run once on load in case Blockly events fire before DOMContentLoaded
ensureTelloPythonGenerators();

// --- Simulator (top view) ---
// Heading 0 = facing up the screen; turning clockwise increases it. Height is shown beside
// the canvas, since a top view cannot show up/down.
let simCanvas, simCtx, simStatusEl;
const simState = {
  x: 0,
  y: 0,
  heading: 0,
  altitude: 0,
  flying: false,
  trail: [],
};
const SIM_SCALE = 0.6; // pixels per cm: 1 grid square (30 px) = 50 cm
const SIM_GRID_PX = 30;
const SIM_TAKEOFF_CM = 80; // the Tello hovers at roughly this height after takeoff
const SIM_MOVE_ANGLE = { forward: 0, right: 90, back: 180, left: -90 };

function initDroneSim() {
  simCanvas = document.getElementById('droneSimCanvas');
  simStatusEl = document.getElementById('droneSimStatus');
  if (!simCanvas || !simCanvas.getContext) return;
  simCtx = simCanvas.getContext('2d');
  resetSimState();
}

function resetSimState() {
  if (!simCanvas || !simCtx) return;
  simState.x = simCanvas.width / 2;
  simState.y = simCanvas.height / 2;
  simState.heading = 0;
  simState.altitude = 0;
  simState.flying = false;
  simState.trail = [{ x: simState.x, y: simState.y }];
  setSimStatus('Ready');
  drawSim();
}

function setSimStatus(text) {
  if (simStatusEl) {
    simStatusEl.textContent = text;
  }
  const altEl = document.getElementById('simAltitude');
  const headEl = document.getElementById('simHeading');
  if (altEl) altEl.textContent = `${Math.round(simState.altitude)} cm`;
  if (headEl) headEl.textContent = `${((Math.round(simState.heading) % 360) + 360) % 360}°`;
}

function drawSim() {
  if (!simCanvas || !simCtx) return;
  const { width, height } = simCanvas;
  simCtx.clearRect(0, 0, width, height);
  simCtx.fillStyle = '#0f172a';
  simCtx.fillRect(0, 0, width, height);

  // Grid: one square = 50 cm.
  simCtx.strokeStyle = 'rgba(148, 163, 184, 0.12)';
  simCtx.lineWidth = 1;
  for (let gx = (width / 2) % SIM_GRID_PX; gx < width; gx += SIM_GRID_PX) {
    simCtx.beginPath();
    simCtx.moveTo(gx + 0.5, 0);
    simCtx.lineTo(gx + 0.5, height);
    simCtx.stroke();
  }
  for (let gy = (height / 2) % SIM_GRID_PX; gy < height; gy += SIM_GRID_PX) {
    simCtx.beginPath();
    simCtx.moveTo(0, gy + 0.5);
    simCtx.lineTo(width, gy + 0.5);
    simCtx.stroke();
  }

  // Take-off pad.
  simCtx.strokeStyle = 'rgba(250, 204, 21, 0.6)';
  simCtx.beginPath();
  simCtx.arc(width / 2, height / 2, 12, 0, Math.PI * 2);
  simCtx.stroke();

  // Flight path.
  if (simState.trail.length > 1) {
    simCtx.strokeStyle = 'rgba(34, 211, 238, 0.7)';
    simCtx.lineWidth = 2;
    simCtx.beginPath();
    simState.trail.forEach((p, i) => (i ? simCtx.lineTo(p.x, p.y) : simCtx.moveTo(p.x, p.y)));
    simCtx.stroke();
  }

  // Drone: filled while flying, outlined on the ground; the line shows where it faces.
  const rad = (simState.heading * Math.PI) / 180;
  const size = simState.flying ? 10 : 8;
  simCtx.lineWidth = 2;
  simCtx.strokeStyle = '#a855f7';
  simCtx.fillStyle = simState.flying ? '#a855f7' : 'transparent';
  simCtx.beginPath();
  simCtx.arc(simState.x, simState.y, size, 0, Math.PI * 2);
  simCtx.fill();
  simCtx.stroke();
  simCtx.strokeStyle = '#facc15';
  simCtx.beginPath();
  simCtx.moveTo(simState.x, simState.y);
  simCtx.lineTo(simState.x + Math.sin(rad) * 20, simState.y - Math.cos(rad) * 20);
  simCtx.stroke();
}

// Evaluates a plain numeric expression such as "90", "(2)*100" or "1.5"; NaN for anything else
// (variables, function calls), so callers never silently turn an unknown value into 0.
function evalNumericExpr(expr) {
  const text = String(expr ?? '').trim();
  if (!text || !/^[\d\s.+\-*/()]+$/.test(text)) return NaN;
  try {
    const value = Function(`"use strict"; return (${text});`)();
    return typeof value === 'number' && Number.isFinite(value) ? value : NaN;
  } catch (_) {
    return NaN;
  }
}

const MAX_LOOP_REPEATS = 50;

function rangeCount(rangeArg) {
  return evalNumericExpr(String(rangeArg).trim().replace(/^int\((.*)\)$/, '$1'));
}

// Flattens generated Python so the op parser can walk it line by line:
// `for _ in range(N):` bodies are repeated N times, while if/elif/else/while bodies
// cannot be evaluated here, so they are dropped and listed in `skipped`.
function flattenPythonBlocks(lines, skipped = []) {
  const out = [];
  const indentOf = (line) => line.match(/^\s*/)[0].length;
  for (let i = 0; i < lines.length; i += 1) {
    const line = lines[i];
    const trimmed = line.trim();
    if (!trimmed) continue;
    if (!/^(for|while|if|elif|else)\b.*:$/.test(trimmed)) {
      out.push(trimmed);
      continue;
    }
    const baseIndent = indentOf(line);
    const body = [];
    let j = i + 1;
    while (j < lines.length && (!lines[j].trim() || indentOf(lines[j]) > baseIndent)) {
      body.push(lines[j]);
      j += 1;
    }
    i = j - 1;
    const loop = trimmed.match(/^for\s+\w+\s+in\s+range\((.+)\)\s*:$/);
    const times = loop ? rangeCount(loop[1]) : NaN;
    if (loop && Number.isFinite(times)) {
      const inner = flattenPythonBlocks(body, skipped);
      const count = Math.max(0, Math.min(Math.floor(times), MAX_LOOP_REPEATS));
      for (let r = 0; r < count; r += 1) out.push(...inner);
    } else {
      skipped.push(trimmed);
    }
  }
  return out;
}

function parsePythonToOps(pythonCode, skipped = []) {
  const ops = [];
  const lines = flattenPythonBlocks(pythonCode.split('\n'), skipped);
  for (let i = 0; i < lines.length; i += 1) {
    const trimmed = lines[i].trim();
    if (!trimmed) continue;
    if (/tello\.takeoff\(\)/.test(trimmed)) ops.push({ type: 'takeoff' });
    if (/tello\.land\(\)/.test(trimmed)) ops.push({ type: 'land' });
    if (/^tello\.emergency_land\(\)$/.test(trimmed)) {
      // Emergency stop lands and ends the program: nothing after it may run.
      ops.push({ type: 'emergency' });
      break;
    }
    if (/^tello\.take_picture\(\)$/.test(trimmed)) ops.push({ type: 'photo' });

    // The bounce generator emits a try/except fallback; only count the primary call
    // (the fallback line `tello.bounce() if hasattr(...)` previously added a second bounce).
    const bounceMatch = /tello\.send_command_with_return\(['"]bounce['"]\)/.test(trimmed)
      || /^tello\.bounce\(\)$/.test(trimmed);
    if (bounceMatch) {
      ops.push({ type: 'bounce' });
      continue;
    }

    const circleStart = /tello\.send_rc_control\(\s*0\s*,\s*40\s*,\s*0\s*,\s*40\s*\)/.test(trimmed);
    if (circleStart) {
      let sleepValue = null;
      let stopIndex = null;
      for (let k = i + 1; k < lines.length; k += 1) {
        const next = lines[k].trim();
        if (!next) continue;
        if (sleepValue === null) {
          const sleepMatch = next.match(/time\.sleep\(([^)]+)\)/);
          if (sleepMatch) {
            sleepValue = parseFloat(sleepMatch[1]) || 0;
            continue;
          }
          break;
        }
        if (/tello\.send_rc_control\(\s*0\s*,\s*0\s*,\s*0\s*,\s*0\s*\)/.test(next)) {
          stopIndex = k;
        }
        break;
      }
      if (sleepValue !== null && stopIndex !== null) {
        ops.push({ type: 'circle', value: sleepValue });
        i = stopIndex;
        continue;
      }
    }

    const moveMatch = trimmed.match(/^tello\.move_(forward|back|left|right|up|down)\((.*)\)$/);
    if (moveMatch) {
      const value = evalNumericExpr(moveMatch[2]);
      if (Number.isFinite(value)) {
        ops.push({ type: 'move', dir: moveMatch[1], value });
      } else {
        skipped.push(trimmed);
      }
    }
    const turnMatch = trimmed.match(/^tello\.(rotate_clockwise|rotate_counter_clockwise)\((.*)\)$/);
    if (turnMatch) {
      const dir = turnMatch[1].includes("counter") ? "ccw" : "cw";
      const value = evalNumericExpr(turnMatch[2]);
      if (Number.isFinite(value)) {
        ops.push({ type: 'turn', dir, value });
      } else {
        skipped.push(trimmed);
      }
    }
    const waitMatch = trimmed.match(/^time\.sleep\((.*)\)$/);
    if (waitMatch) {
      const value = evalNumericExpr(waitMatch[1]);
      if (Number.isFinite(value)) {
        ops.push({ type: 'wait', value });
      } else {
        skipped.push(trimmed);
      }
    }
    const flipMatch = trimmed.match(/tello\.flip\('([flrb])'\)/);
    if (flipMatch) {
      ops.push({ type: 'flip', dir: flipMatch[1] });
    }
  }
  return ops;
}

// Step-by-step mode is only safe when every line is something parsePythonToOps fully
// understands. Anything else (if/while conditions, variables, non-literal values) must run as
// the full Python script, otherwise e.g. the body of `if False:` would still be sent to the drone.
function shouldUseSequentialOps(pythonCode) {
  const lines = pythonCode.split('\n');
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#') || trimmed === 'pass') continue;
    const loop = trimmed.match(/^for\s+\w+\s+in\s+range\((.+)\)\s*:$/);
    if (loop) {
      if (Number.isFinite(rangeCount(loop[1]))) continue;
      return false;
    }
    const numericCall = trimmed.match(/^(?:time\.sleep|tello\.move_(?:forward|back|left|right|up|down)|tello\.rotate_(?:clockwise|counter_clockwise))\((.*)\)$/);
    if (numericCall) {
      if (Number.isFinite(evalNumericExpr(numericCall[1]))) continue;
      return false;
    }
    if (/^tello\.(takeoff|land|emergency_land|take_picture|bounce)\(\)$/.test(trimmed)) continue;
    if (/^tello\.flip\('[flrb]'\)$/.test(trimmed)) continue;
    // Bounce helper (try/except wrapper emitted by the tello_bounce generator)
    if (trimmed === 'try:' || trimmed === 'except Exception:') continue;
    if (/^tello\.send_command_with_return\(['"]bounce['"]\)$/.test(trimmed)) continue;
    if (/^tello\.bounce\(\) if hasattr\(tello, ['"]bounce['"]\) else None$/.test(trimmed)) continue;
    if (/^tello\.send_rc_control\(\s*0\s*,\s*40\s*,\s*0\s*,\s*40\s*\)$/.test(trimmed)) continue;
    if (/^tello\.send_rc_control\(\s*0\s*,\s*0\s*,\s*0\s*,\s*0\s*\)$/.test(trimmed)) continue;
    // Anything else means we should run the full script instead.
    return false;
  }
  return true;
}

function applySimOp(op) {
  if (!simCanvas || !simCtx) return;
  switch (op.type) {
    case 'takeoff':
      simState.flying = true;
      simState.altitude = SIM_TAKEOFF_CM;
      setSimStatus('Takeoff');
      break;
    case 'land':
    case 'emergency':
      simState.flying = false;
      simState.altitude = 0;
      setSimStatus(op.type === 'land' ? 'Landed' : 'Emergency stop: landed');
      break;
    case 'turn':
      simState.heading += op.dir === 'cw' ? op.value : -op.value;
      setSimStatus(`Turn ${op.dir === 'cw' ? 'right' : 'left'} ${op.value}°`);
      break;
    case 'move': {
      if (!simState.flying) {
        setSimStatus(`Move ${op.dir} skipped: take off first`);
        break;
      }
      if (op.dir === 'up' || op.dir === 'down') {
        simState.altitude = Math.max(0, simState.altitude + (op.dir === 'up' ? op.value : -op.value));
        setSimStatus(`Move ${op.dir} ${op.value} cm`);
        break;
      }
      const rad = ((simState.heading + (SIM_MOVE_ANGLE[op.dir] ?? 0)) * Math.PI) / 180;
      const distancePx = (op.value || 0) * SIM_SCALE;
      simState.x = Math.max(8, Math.min(simCanvas.width - 8, simState.x + Math.sin(rad) * distancePx));
      simState.y = Math.max(8, Math.min(simCanvas.height - 8, simState.y - Math.cos(rad) * distancePx));
      simState.trail.push({ x: simState.x, y: simState.y });
      setSimStatus(`Move ${op.dir} ${op.value} cm`);
      break;
    }
    case 'wait':
      setSimStatus(`Wait ${op.value}s`);
      break;
    case 'flip':
      setSimStatus(`Flip ${{ f: 'forward', b: 'back', l: 'left', r: 'right' }[op.dir] || op.dir}`);
      break;
    case 'photo':
      setSimStatus('Photo taken');
      break;
    case 'bounce':
      setSimStatus('Bounce');
      break;
    case 'circle':
      setSimStatus(`Circle ${op.value}s`);
      break;
    default:
      break;
  }
  drawSim();
}

// Plays the program step by step on the canvas. Resolves 'complete' or 'stopped'.
async function runDroneSimulation(pythonCode, logToConsole) {
  if (!simCanvas || !simCtx) {
    logToConsole?.('[SIM] No sim canvas found.');
    return 'complete';
  }
  const skipped = [];
  const ops = parsePythonToOps(pythonCode, skipped);
  if (skipped.length) {
    // The simulator cannot evaluate conditions or variables; say so instead of guessing.
    logToConsole?.(`[SIM] Not simulated (needs the real drone/Python): ${skipped.join(' | ')}`);
  }
  // Same limits the real drone enforces: warn here so the program works when it flies for real.
  findOutOfRangeOps(ops).forEach((problem) => logToConsole?.(`[SIM] The real drone would reject: ${problem}`));
  resetSimState();
  if (!ops.length) {
    logToConsole?.('[SIM] No flight ops detected.');
    return 'complete';
  }
  for (const op of ops) {
    if (stopRequested) {
      setSimStatus('Stopped');
      logToConsole?.('[SIM] Stopped');
      return 'stopped';
    }
    applySimOp(op);
    logToConsole?.(`[SIM] ${op.type} ${op.dir ?? ''} ${op.value ?? ''}`.replace(/\s+/g, ' ').trim());
    if (op.type === 'emergency') break;
    // Waits play at 40% speed (capped) so long programs stay watchable.
    await sleep(op.type === 'wait' ? Math.min(3000, Math.max(400, (op.value || 0) * 400)) : 600);
  }
  setSimStatus(simState.flying ? 'Complete (still flying: add a Land block)' : 'Complete');
  return 'complete';
}

// In workspace.js, REPLACE the old 'runCode' function:

// === NEW RUN FUNCTION (Sends to Drone) ===
async function runCode() {
  stopRequested = false;
  ensureTelloPythonGenerators(); // guard against missing generators

  if (!commandReady && !simMode) {
    setLaunchButtonIdle();
    setPythonStatus('Connect to Tello first.', 'error');
    const locked = document.body.dataset.simLocked === '1';
    const choice = await Swal.fire({
      icon: 'warning',
      title: 'Drone not connected',
      text: locked
        ? 'Connect the drone first.'
        : 'Connect the drone first, or test your program on the simulator.',
      showDenyButton: !locked,
      showCancelButton: true,
      confirmButtonText: 'Connect drone',
      denyButtonText: 'Use simulator',
      denyButtonColor: '#6366f1',
    });
    if (choice.isConfirmed) {
      connectDrone();
    } else if (choice.isDenied) {
      setRunMode(true);
    }
    return;
  }

  // 1. Get the console element and clear it
  const consoleOutput = document.getElementById('consoleOutput');
  if (consoleOutput) {
    openConsole();
    consoleOutput.textContent = ''; // Clear console
    consoleOutput.style.color = '#00FF00'; // Green "console" color
  }
  
  // Helper function to log messages to our console
  function logToConsole(message) {
    if (consoleOutput) {
      openConsole();
      consoleOutput.textContent += message + '\n';
      consoleOutput.scrollTop = consoleOutput.scrollHeight; // Auto-scroll
    } else {
      console.log(message);
    }
  }

  // 2. Generate the Python code (always show it, even if not connected)
  const activeGenerator =
    typeof pythonGenerator !== "undefined"
      ? pythonGenerator
      : typeof Blockly !== "undefined"
      ? (Blockly.Python || Blockly.PythonGenerator || null)
      : null;
  if (activeGenerator && typeof Blockly !== "undefined" && !Blockly.Python) {
    Blockly.Python = activeGenerator;
  }
  ensureTelloPythonGenerators();

  const codeGenerator =
    typeof pythonGenerator !== "undefined"
      ? pythonGenerator
      : typeof Blockly !== "undefined"
      ? (Blockly.Python || Blockly.PythonGenerator || null)
      : null;
  if (!codeGenerator || !codeGenerator.workspaceToCode) {
    logToConsole("--- [ERR] Python generator is not available.");
    setPythonStatus('Python generator missing.', 'error');
    setLaunchButtonIdle();
    return;
  }

  let pythonCode;
  try {
    pythonCode = codeGenerator.workspaceToCode(workspace);
  } catch (err) {
    logToConsole(`--- [ERR] ${err.message || err}`);
    setPythonStatus('Code generation failed.', 'error');
    setLaunchButtonIdle();
    return;
  }
  if (!pythonCode) {
    logToConsole("--- [SIM] No blocks to run ---");
    setPythonStatus('No blocks to run.', 'error');
    setLaunchButtonIdle();
    return;
  }
  logToConsole("--- [PY] Generated code ---");
  logToConsole(pythonCode.trim());
  sendCodeToParent('running');

  if (simMode) {
    logToConsole('--- [SIM] Running on the simulator (no drone) ---');
    const footer = document.getElementById('consoleFooter');
    const box = footer?.getBoundingClientRect();
    if (box && box.bottom > window.innerHeight) footer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    setPythonStatus('Simulating blocks...', 'running');
    const outcome = await runDroneSimulation(pythonCode, logToConsole);
    if (outcome === 'stopped') {
      setPythonStatus('Simulation stopped.', 'idle');
      sendCodeToParent('stopped');
    } else {
      setPythonStatus('Simulation complete.', 'idle');
      sendCodeToParent('complete');
    }
    setLaunchButtonIdle();
    return;
  }

  // A low battery can make the Tello refuse to take off or land early mid-program.
  if (telloConnection.battery !== null && telloConnection.battery < LOW_BATTERY) {
    const { isConfirmed } = await Swal.fire({
      icon: 'warning',
      title: `Battery is low (${telloConnection.battery}%)`,
      text: 'The drone may refuse to take off or land early. Charge the battery first if you can.',
      showCancelButton: true,
      confirmButtonText: 'Fly anyway',
      cancelButtonText: 'Cancel',
    });
    if (!isConfirmed) {
      logToConsole('--- [STOP] Cancelled: battery low ---');
      setPythonStatus('Cancelled: battery low.', 'error');
      setLaunchButtonIdle();
      return;
    }
  }

  // 2. Make sure the bridge sees an active Tello connection before sending anything
  const connected = await checkStatus();
  if (!connected) {
    logToConsole('--- [WARN] Bridge offline or Tello not connected. Code not sent. ---');
    setPythonStatus('Connect to DJI Tello first.', 'error');
    if (consoleOutput) {
      consoleOutput.style.color = '#FF0000';
    }
    setLaunchButtonIdle();
    return;
  }

  // Parse into discrete ops and run sequentially for block-by-block execution
  const canRunSequential = shouldUseSequentialOps(pythonCode);
  const ops = canRunSequential ? parsePythonToOps(pythonCode) : [];
  const rangeProblems = findOutOfRangeOps(ops);
  if (rangeProblems.length) {
    rangeProblems.forEach((problem) => logToConsole(`--- [ERROR] ${problem}`));
    logToConsole('--- [ERROR] Nothing was sent to the drone. Fix the values above and launch again.');
    setPythonStatus('Value out of Tello range.', 'error');
    setLaunchButtonIdle();
    return;
  }
  if (ops.length) {
    setPythonStatus('Running blocks one by one...', 'running');
    logToConsole('--- [CMD] Executing blocks sequentially ---');
    const ok = await executeOpsSequentially(ops, logToConsole);
    if (ok === 'emergency') {
      logToConsole('--- [STOP] Emergency stop: drone landed, program ended ---');
      setPythonStatus('Emergency stop: drone landed.', 'idle');
      sendCodeToParent('stopped');
      setLaunchButtonIdle();
    } else if (ok) {
      logToConsole('--- [OK] All commands completed ---');
      setPythonStatus('Flight plan complete.', 'idle');
      sendCodeToParent('complete');
      setLaunchButtonIdle();
    } else if (stopRequested) {
      logToConsole('--- [STOP] Landing requested ---');
      setPythonStatus('Stopped. Landing requested.', 'idle');
      sendCodeToParent('stopped');
      setLaunchButtonIdle();
    } else {
      logToConsole('--- [ERROR] Aborted due to command failure ---');
      setPythonStatus('Command failed; stopped.', 'error');
      sendCodeToParent('error');
      setLaunchButtonIdle();
    }
    return;
  }

  if (!canRunSequential && pythonCode.trim()) {
    logToConsole('--- [INFO] Running full script to support non-step commands ---');
  }
  setPythonStatus('Running code...', 'running');
  logToConsole('--- [CMD] Sending script to drone ---');
  console.log('Generated Python code:\n', pythonCode);
  
  // 3. Send the code to the Python bridge
  try {
    const response = await fetch('http://127.0.0.1:5000/run_script', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: pythonCode }),
    });
    
    const data = await response.json();

    if (response.ok) {
      logToConsole(`--- [OK] ${data.status} ---`);
      setPythonStatus('Script running on drone...', 'running');
      // The bridge runs the script in the background; wait for its real outcome.
      const result = await waitForScriptResult(logToConsole);
      if (result.state === 'finished') {
        logToConsole('--- [OK] Script finished ---');
        setPythonStatus('Flight plan complete.', 'idle');
        sendCodeToParent('complete');
      } else if (result.state === 'stopped') {
        logToConsole(`--- [STOP] ${result.error || 'Script stopped'} ---`);
        setPythonStatus('Stopped. Landing requested.', 'idle');
        sendCodeToParent('stopped');
      } else {
        logToConsole(`--- [ERROR] ${result.error || 'Script failed'} ---`);
        setPythonStatus('Script failed on the drone.', 'error');
        if (consoleOutput) {
          consoleOutput.style.color = '#FF0000';
        }
        sendCodeToParent('error');
      }
      setLaunchButtonIdle();
    } else {
      logToConsole(`--- [ERROR] ${data.error} ---`);
      setPythonStatus('Drone rejected the script.', 'error');
      if (consoleOutput) {
        consoleOutput.style.color = '#FF0000'; // Red error color
      }
      sendCodeToParent('error');
      setLaunchButtonIdle();
    }
    
  } catch (err) {
    logToConsole("--- [FATAL] Bridge server is offline. ---");
    logToConsole(err.message);
    setPythonStatus('Bridge offline. Cannot send script.', 'error');
    if (consoleOutput) {
      consoleOutput.style.color = '#FF0000'; // Red error color
    }
    sendCodeToParent('error');
    setLaunchButtonIdle();
  }
}


// In workspace.js, REPLACE the old 'stopCode' function:

// === NEW STOP FUNCTION (Sends to Drone) ===
async function stopCode() {
  stopRequested = true;
  const consoleOutput = document.getElementById('consoleOutput');
  if (consoleOutput) {
    openConsole();
    consoleOutput.textContent += "\n--- [CMD] Sending STOP (Land) to drone ---";
    consoleOutput.style.color = '#FF0000'; // Show an error/stop color
  } else {
    console.log('--- [CMD] Sending STOP (Land) to drone ---');
  }
  console.log('Stop execution');
  if (simMode) {
    // runDroneSimulation sees stopRequested and ends between steps.
    setPythonStatus('Simulation stopped.', 'idle');
    return;
  }
  setPythonStatus('Terminate requested. Landing drone...', 'error');
  
  try {
    const response = await fetch('http://127.0.0.1:5000/stop?mode=land');
    const data = await response.json();
    if (consoleOutput) {
      consoleOutput.textContent += `\n--- [OK] ${data.status} ---`;
    } else {
      console.log(`--- [OK] ${data.status} ---`);
    }
    setPythonStatus('Drone landing sequence acknowledged.', 'idle');
  } catch (err) {
    if (consoleOutput) {
      consoleOutput.textContent += "\n--- [FATAL] Bridge server is offline. ---";
    } else {
      console.error('--- [FATAL] Bridge server is offline. ---');
    }
    setPythonStatus('Failed to contact bridge for landing.', 'error');
  }
}




// These functions already exist and will now be called
function undo() {
  workspace.undo(false);
}

function redo() {
  workspace.undo(true);
}

async function resetWorkspace() {
  const { isConfirmed } = await Swal.fire({
    icon: 'warning',
    title: 'Clear all blocks?',
    text: 'This removes every block from the canvas. Saved programs are kept.',
    showCancelButton: true,
    confirmButtonText: 'Clear',
    confirmButtonColor: '#dc2626',
  });
  if (isConfirmed) {
    workspace.clear();
    setProgramName('');
  }
}

// --- Saved programs (per account, PHP/programs.php) and the unsaved-work draft ---
const PROGRAMS_URL = '../PHP/programs.php';
// The old Save button kept one program per browser, shared by everyone on that computer.
const LEGACY_PROGRAM_KEY = 'blocklyWorkspace';
let currentProgramName = '';
let draftTimer = null;
let restoringDraft = false;

// Drafts are per user and only for the full workspace (lessons start from their own blocks).
function draftKey() {
  const userId = document.body.dataset.user;
  const embedded = document.body.dataset.simLocked === '1';
  return userId && !embedded ? `codefly:draft:${userId}` : null;
}

function setProgramName(name) {
  currentProgramName = name || '';
  const label = document.getElementById('program-name');
  if (label) label.textContent = currentProgramName || 'Untitled';
}

function workspaceHasBlocks() {
  return workspace.getAllBlocks(false).length > 0;
}

function scheduleDraftSave() {
  const key = draftKey();
  if (!key || restoringDraft) return;
  clearTimeout(draftTimer);
  draftTimer = setTimeout(() => {
    try {
      const program = Blockly.serialization.workspaces.save(workspace);
      localStorage.setItem(key, JSON.stringify({ name: currentProgramName, program }));
    } catch (err) {
      console.warn('Draft not saved', err);
    }
  }, 800);
}

function restoreDraft() {
  const key = draftKey();
  if (!key || workspaceHasBlocks()) return;
  let draft = null;
  try {
    draft = JSON.parse(localStorage.getItem(key) || 'null');
  } catch (err) {
    draft = null;
  }
  if (!draft?.program?.blocks) return;
  restoringDraft = true;
  try {
    Blockly.serialization.workspaces.load(draft.program, workspace);
    setProgramName(draft.name);
    Swal.fire({
      toast: true,
      position: 'bottom-end',
      icon: 'info',
      title: 'Restored your unsaved blocks from last time',
      timer: 3000,
      showConfirmButton: false,
    });
  } catch (err) {
    console.warn('Draft could not be restored', err);
  } finally {
    restoringDraft = false;
  }
}

async function programsRequest(options = {}, query = '') {
  const response = await fetch(`${PROGRAMS_URL}${query}`, { credentials: 'same-origin', cache: 'no-store', ...options });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload.status !== 'success') {
    throw new Error(payload.message || 'Saved programs are unavailable right now.');
  }
  return payload;
}

async function saveProgram() {
  if (!workspaceHasBlocks()) {
    Swal.fire({ icon: 'info', title: 'Nothing to save yet', text: 'Drag some blocks onto the canvas first.' });
    return;
  }
  const { value: name } = await Swal.fire({
    title: 'Save program',
    input: 'text',
    inputLabel: 'Program name',
    inputValue: currentProgramName || '',
    inputPlaceholder: 'e.g. Square flight',
    inputAttributes: { maxlength: '60', autocapitalize: 'off' },
    showCancelButton: true,
    confirmButtonText: 'Save',
    inputValidator: (value) => (!value || !value.trim() ? 'Give your program a name.' : undefined),
  });
  if (!name) return;
  try {
    const payload = await programsRequest({
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: name.trim(), program: Blockly.serialization.workspaces.save(workspace) }),
    });
    setProgramName(payload.data.name);
    scheduleDraftSave();
    Swal.fire({
      icon: 'success',
      title: payload.data.overwritten ? 'Program updated' : 'Program saved',
      text: `"${payload.data.name}" is in My programs.`,
      timer: 1600,
      showConfirmButton: false,
    });
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Not saved', text: err.message });
  }
}

async function confirmReplaceCanvas() {
  if (!workspaceHasBlocks()) return true;
  const { isConfirmed } = await Swal.fire({
    icon: 'question',
    title: 'Replace the blocks on the canvas?',
    text: 'Save your current program first if you want to keep it.',
    showCancelButton: true,
    confirmButtonText: 'Replace',
  });
  return isConfirmed;
}

async function openProgram(id) {
  try {
    const { data } = await programsRequest({}, `?id=${encodeURIComponent(id)}`);
    if (!(await confirmReplaceCanvas())) return;
    Blockly.serialization.workspaces.load(data.program || {}, workspace);
    setProgramName(data.name);
    Swal.fire({ icon: 'success', title: `Opened "${data.name}"`, timer: 1200, showConfirmButton: false });
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Could not open program', text: err.message });
  }
}

async function openLegacyProgram() {
  let xmlText = null;
  try {
    xmlText = localStorage.getItem(LEGACY_PROGRAM_KEY);
  } catch (err) {
    xmlText = null;
  }
  if (!xmlText || !(await confirmReplaceCanvas())) return;
  workspace.clear();
  // Blockly.Xml.textToDom was removed in Blockly 11; the helper now lives in utils.xml.
  Blockly.Xml.domToWorkspace(Blockly.utils.xml.textToDom(xmlText), workspace);
  setProgramName('');
  Swal.fire({ icon: 'info', title: 'Opened', text: 'Press Save to keep it in your account.' });
}

function formatProgramDate(value) {
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

async function openProgramsDialog() {
  let programs;
  try {
    programs = (await programsRequest()).data || [];
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'My programs', text: err.message });
    return;
  }
  let hasLegacy = false;
  try {
    hasLegacy = !!localStorage.getItem(LEGACY_PROGRAM_KEY);
  } catch (err) {
    hasLegacy = false;
  }

  const list = document.createElement('ul');
  list.className = 'programs-list';
  programs.forEach((program) => {
    const li = document.createElement('li');
    const info = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = program.name;
    const when = document.createElement('span');
    when.textContent = `Saved ${formatProgramDate(program.updatedAt)}`;
    info.append(name, when);
    const open = document.createElement('button');
    open.type = 'button';
    open.className = 'programs-open';
    open.textContent = 'Open';
    open.addEventListener('click', () => openProgram(program.id));
    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'programs-delete';
    del.setAttribute('aria-label', `Delete ${program.name}`);
    del.innerHTML = "<i class='bx bx-trash'></i>";
    del.addEventListener('click', async () => {
      const { isConfirmed } = await Swal.fire({
        icon: 'warning',
        title: `Delete "${program.name}"?`,
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626',
      });
      if (!isConfirmed) return;
      try {
        await programsRequest({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: program.id }),
        });
        if (program.name === currentProgramName) setProgramName('');
      } catch (err) {
        await Swal.fire({ icon: 'error', title: 'Not deleted', text: err.message });
      }
      openProgramsDialog();
    });
    li.append(info, open, del);
    list.appendChild(li);
  });
  if (hasLegacy) {
    const li = document.createElement('li');
    li.innerHTML = '<div><strong>Saved in this browser</strong><span>From the old Save button</span></div>';
    const open = document.createElement('button');
    open.type = 'button';
    open.className = 'programs-open';
    open.textContent = 'Open';
    open.addEventListener('click', openLegacyProgram);
    li.appendChild(open);
    list.appendChild(li);
  }
  if (!list.children.length) {
    const empty = document.createElement('p');
    empty.className = 'programs-empty';
    empty.textContent = 'No saved programs yet. Build something and press Save.';
    list.replaceWith(empty);
    Swal.fire({ title: 'My programs', html: empty, confirmButtonText: 'Close' });
    return;
  }
  Swal.fire({ title: 'My programs', html: list, showConfirmButton: false, showCloseButton: true, width: 560 });
}

function goHome() {
  // workspace.php sets data-home: admin dashboard for admins, home for students.
  window.location.href = document.body.dataset.home || "../HTML/home.php";
}

function setPythonStatus(message, state = 'idle') {
  const statusEl = document.getElementById('python-status');
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('status-running', 'status-error', 'status-idle');
  const variant = state === 'running' ? 'status-running'
    : state === 'error' ? 'status-error'
    : 'status-idle';
  statusEl.classList.add(variant);
}

function updatePythonPreview() {
  const preview = document.getElementById('pythonPreview');
  if (!preview || typeof Blockly === "undefined" || typeof workspace === "undefined") {
    return;
  }
  const previewGenerator =
    typeof pythonGenerator !== "undefined"
      ? pythonGenerator
      : typeof Blockly !== "undefined"
      ? (Blockly.Python || Blockly.PythonGenerator || null)
      : null;
  if (previewGenerator && typeof Blockly !== "undefined" && !Blockly.Python) {
    Blockly.Python = previewGenerator;
  }
  ensureTelloPythonGenerators();
  if (!previewGenerator || !previewGenerator.workspaceToCode) {
    preview.textContent = '# Python generator unavailable';
    return;
  }
  let code = '';
  try {
    code = previewGenerator.workspaceToCode(workspace).trim();
  } catch (err) {
    console.warn('Preview codegen failed:', err);
    code = '# Unable to generate code';
  }
  if (!code) code = '# Drag blocks to see Python...';
  preview.textContent = code;
  if (window.hljs && typeof window.hljs.highlightElement === 'function') {
    // Clear previous highlight marker to avoid highlight.js warning spam
    preview.removeAttribute('data-highlighted');
    window.hljs.highlightElement(preview);
  }
}

// Listen for parent messages (e.g., module toolbar Run/Stop)
window.addEventListener('message', (event) => {
  const payload = event.data || {};
  if (payload.type === 'block-run' && typeof runCode === 'function') {
    runCode();
  }
  if (payload.type === 'block-stop' && typeof stopCode === 'function') {
    stopCode();
    updateLaunchButton(false);
  }
  if (payload.type === 'code-request') {
    sendCodeToParent('preview');
  }
});

function sendCodeToParent(status = 'idle') {
  try {
    const parentGenerator =
      typeof pythonGenerator !== "undefined"
        ? pythonGenerator
        : typeof Blockly !== "undefined"
        ? (Blockly.Python || Blockly.PythonGenerator || null)
        : null;
    if (parentGenerator && typeof Blockly !== "undefined" && !Blockly.Python) {
      Blockly.Python = parentGenerator;
    }
    ensureTelloPythonGenerators();
    const code = parentGenerator?.workspaceToCode?.(workspace) || '';
    if (window.parent) {
      window.parent.postMessage({ type: 'lesson-code', code, status }, '*');
    }
  } catch (err) {
    if (window.parent) {
      window.parent.postMessage({ type: 'lesson-code', code: '# Unable to generate code', status: 'error' }, '*');
    }
  }
}

if (typeof workspace !== "undefined") {
  workspace.addChangeListener((event) => {
    if (event?.isUiEvent) return; // selection, scrolling, opening the flyout
    updatePythonPreview();
    setPythonStatus('Blocks updated. Ready to run.', 'idle');
    sendCodeToParent('idle');
    scheduleDraftSave();
  });
}

// --- Drone connection, live telemetry and the Simulator / Drone switch ---
const TELLO_BRIDGE_URL = "http://127.0.0.1:5000";
const LOW_BATTERY = 20; // % : warn before flying below this
const TELEMETRY_POLL_MS = 2000;
const RUN_MODE_KEY = 'codefly:runMode';
const telloConnection = {
  connected: false,
  connecting: false,
  battery: null,
};
let telemetryTimer = null;

function initRunMode() {
  const locked = document.body.dataset.simLocked === '1';
  if (!locked) {
    let saved = null;
    try {
      saved = localStorage.getItem(RUN_MODE_KEY);
    } catch (err) {
      saved = null;
    }
    // Simulator by default: most students practise without a drone.
    simMode = IS_LESSON_SIM || saved !== 'drone';
    document.querySelectorAll('.mode-option').forEach((btn) => {
      btn.addEventListener('click', () => setRunMode(btn.dataset.mode === 'sim'));
    });
  }
  applyRunMode();
}

function setRunMode(useSim) {
  if (window.isProgramRunning?.()) return;
  simMode = useSim;
  try {
    localStorage.setItem(RUN_MODE_KEY, useSim ? 'sim' : 'drone');
  } catch (err) {
    // Private windows may block storage; the switch still works for this visit.
  }
  applyRunMode();
  if (useSim) {
    openConsole();
    resetSimState();
  }
}

function applyRunMode() {
  document.body.classList.toggle('sim-mode', simMode);
  document.querySelectorAll('.mode-option').forEach((btn) => {
    btn.setAttribute('aria-checked', String((btn.dataset.mode === 'sim') === simMode));
  });
  renderDroneStatus();
  if (!simMode && telloConnection.connected) startTelemetryPolling();
}

function renderDroneStatus(telemetry = null) {
  const card = document.getElementById('drone-card');
  const label = document.getElementById('sidebar-connection-label');
  const batteryLabel = document.getElementById('sidebar-battery-label');
  const batteryBar = document.getElementById('battery-bar');
  const connectBtn = document.getElementById('connect-btn');
  const connectLabel = document.getElementById('connect-btn-label');
  const telemetryBox = document.getElementById('header-telemetry');
  const offlineLabel = document.getElementById('telemetry-offline');
  const { connected, connecting, battery } = telloConnection;

  let state = connected ? 'connected' : connecting ? 'connecting' : 'offline';
  if (simMode) state = 'sim';
  if (card) card.dataset.state = state;

  if (label) {
    label.textContent = {
      sim: 'Simulator',
      connected: 'Drone connected',
      connecting: 'Connecting...',
      offline: 'Not connected',
    }[state];
  }
  if (batteryLabel) {
    batteryLabel.textContent = simMode
      ? 'No drone needed'
      : battery !== null
        ? `Battery: ${battery}%${battery < LOW_BATTERY ? ' (low)' : ''}`
        : 'Battery: Unknown';
  }
  if (batteryBar) {
    batteryBar.style.width = `${battery !== null && connected ? Math.max(0, Math.min(100, battery)) : 0}%`;
    batteryBar.classList.toggle('is-low', battery !== null && battery < LOW_BATTERY);
  }
  if (connectBtn) {
    connectBtn.disabled = connecting;
    connectBtn.classList.toggle('is-connected', connected);
    connectBtn.setAttribute('aria-pressed', connected ? 'true' : 'false');
    connectBtn.querySelector('i').className = connected ? 'bx bx-unlink' : connecting ? 'bx bx-loader-alt bx-spin' : 'bx bx-wifi';
  }
  if (connectLabel) {
    connectLabel.textContent = connected ? 'Disconnect' : connecting ? 'Connecting...' : 'Connect drone';
  }

  // Telemetry only means something with a connected drone.
  const live = connected && !simMode;
  telemetryBox?.classList.toggle('is-offline', !live);
  if (offlineLabel) offlineLabel.textContent = simMode ? 'Simulator mode' : 'No drone data';
  ['pitch', 'roll', 'yaw'].forEach((axis) => {
    const el = document.getElementById(`telemetry-${axis}`);
    if (!el) return;
    if (!live) el.textContent = '--';
    else if (telemetry && telemetry[axis] !== null && telemetry[axis] !== undefined) el.textContent = `${telemetry[axis]}°`;
  });

  setRunEnabled(commandReady || simMode);
}

function updateStatus(connected, battery, telemetry = null, cmdReady = null) {
  telloConnection.connected = !!connected;
  telloConnection.battery = typeof battery === "number" ? battery : null;
  commandReady = cmdReady !== null ? !!cmdReady && !!connected : !!connected;
  renderDroneStatus(telemetry);
  if (!connected) stopTelemetryPolling();
}

async function checkStatus() {
  try {
    const response = await fetch(`${TELLO_BRIDGE_URL}/status`);
    const data = await response.json();
    updateStatus(data.connected, data.battery, data, data.command_ready);
    return !!data.connected;
  } catch (err) {
    updateStatus(false, null); // Bridge is offline
    return false;
  }
}

// Live pitch/roll/yaw and battery while the drone is connected (paused in a hidden tab).
function startTelemetryPolling() {
  if (telemetryTimer) return;
  telemetryTimer = setInterval(async () => {
    if (document.hidden || simMode || !telloConnection.connected) return;
    const stillConnected = await checkStatus();
    if (!stillConnected) {
      const consoleOutput = document.getElementById('consoleOutput');
      if (consoleOutput) {
        openConsole();
        consoleOutput.textContent += '\n--- [WARN] Lost contact with the drone or the bridge. Reconnect to continue. ---';
      }
    }
  }, TELEMETRY_POLL_MS);
}

function stopTelemetryPolling() {
  clearInterval(telemetryTimer);
  telemetryTimer = null;
}

async function connectDrone() {
  if (telloConnection.connecting) return;
  telloConnection.connecting = true;
  renderDroneStatus();
  let problem = null;
  try {
    let response;
    try {
      response = await fetch(`${TELLO_BRIDGE_URL}/connect`);
    } catch (err) {
      problem = 'bridge';
      throw err;
    }
    const data = await response.json();
    if (!response.ok || data?.status !== "connected") {
      problem = 'drone';
      throw new Error(data?.error || data?.status || "Unable to connect to drone.");
    }
    telloConnection.connecting = false;
    updateStatus(true, data?.battery ?? null, data, data?.command_ready ?? true);
    startTelemetryPolling();
    checkStatus(); // /connect has no pitch/roll/yaw; fill them now, not after the first poll
  } catch (err) {
    console.warn("Failed to connect to Tello", err);
    telloConnection.connecting = false;
    updateStatus(false, null);
    showConnectHelp(problem, err?.message);
  }
}

// Explains what to fix instead of only flipping the label back to "Not connected".
async function showConnectHelp(problem, detail) {
  const steps = problem === 'bridge'
    ? `<p>The drone bridge is not running on this computer.</p>
       <ol class="connect-steps">
         <li>Open a terminal in the CodeFly folder.</li>
         <li>Run <code>py tello_bridge.py</code> and leave it open.</li>
         <li>Press <strong>Try again</strong>.</li>
       </ol>`
    : `<p>The bridge is running but cannot reach the Tello.</p>
       <ol class="connect-steps">
         <li>Turn the drone on and wait for the light to blink.</li>
         <li>Connect this computer's Wi-Fi to <strong>TELLO-xxxxxx</strong>.</li>
         <li>Press <strong>Try again</strong>.</li>
       </ol>
       ${detail ? `<p class="connect-detail"></p>` : ''}`;
  const locked = document.body.dataset.simLocked === '1';
  const result = await Swal.fire({
    icon: 'error',
    title: 'Could not connect to the drone',
    html: steps,
    showDenyButton: !locked,
    showCancelButton: true,
    confirmButtonText: 'Try again',
    denyButtonText: 'Use simulator',
    denyButtonColor: '#6366f1',
    cancelButtonText: 'Close',
    didOpen: (popup) => {
      // Bridge error text is shown as text, never as HTML.
      const detailEl = popup.querySelector('.connect-detail');
      if (detailEl) detailEl.textContent = `Bridge said: ${detail}`;
    },
  });
  if (result.isConfirmed) connectDrone();
  else if (result.isDenied) setRunMode(true);
}

document.addEventListener('DOMContentLoaded', function() {
  const connectBtn = document.getElementById("connect-btn");
  connectBtn?.addEventListener("click", () => {
    if (telloConnection.connected) {
      // Disconnect in the UI only; the bridge keeps its link to the drone.
      updateStatus(false, null);
    } else {
      connectDrone();
    }
  });
});

// ===============================
// Tello Generator Alias Layer
// ===============================
(function addTelloGeneratorAliases() {
  if (typeof Blockly === 'undefined' || !Blockly.Python) return;

  const moveReal = Blockly.Python['tello_move_direction'];
  if (moveReal) {
    Blockly.Python['tello_move'] = function(block) {
      return moveReal(block);
    };
  }

  const turnReal = Blockly.Python['tello_turn'];
  if (turnReal) {
    Blockly.Python['tello_turn'] = function(block) {
      return turnReal(block);
    };
  }

  Blockly.Python['tello_flip'] = function(block) {
    const dir = block.getFieldValue('DIR');
    const map = {
      'f': 'tello_flip_forward',
      'b': 'tello_flip_backward',
      'l': 'tello_flip_left',
      'r': 'tello_flip_right'
    };

    const type = map[dir];
    if (Blockly.Python[type]) {
      return Blockly.Python[type](block);
    }
    return '';
  };
})();

