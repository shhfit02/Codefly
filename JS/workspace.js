const CONSOLE_MIN_HEIGHT = 140;
const CONSOLE_MAX_HEIGHT = 520;
let lastConsoleHeight = 260;
const urlParams = new URLSearchParams(window.location.search);
const LESSON_ID = urlParams.get('lesson') || '';
const IS_LESSON_SIM = urlParams.get('sim') === '1' || urlParams.get('mode') === 'lesson';
let lessonToolboxContents = null;
let lessonDefaultCategory = null;
// In lesson/sim mode we should still allow the Launch button to run (even without the Tello bridge)
let commandReady = IS_LESSON_SIM;
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

// Category click handlers
document.addEventListener('DOMContentLoaded', function() {
  // Keep the Launch button active in sim/lesson mode; otherwise gate by connection status
  setRunEnabled(IS_LESSON_SIM);
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
  
  const undoBtn = document.getElementById('undo-btn');
  const redoBtn = document.getElementById('redo-btn');
  const resetBtn = document.getElementById('reset-btn');
  const saveBtn = document.getElementById('save-btn');
  const loadBtn = document.getElementById('load-btn');

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


  attachBounceHandler(headerFlagBtn, () => {
    if (isRunning) {
      stopCode();
      updateLaunchButton(false);
    } else {
      runCode();
      updateLaunchButton(true);
    }
  });

  if (undoBtn) undoBtn.addEventListener('click', undo);
  if (redoBtn) redoBtn.addEventListener('click', redo);
  if (resetBtn) resetBtn.addEventListener('click', resetWorkspace);
  if (saveBtn) saveBtn.addEventListener('click', saveWorkspace);
  if (loadBtn) loadBtn.addEventListener('click', loadWorkspace);

  // Pilot chip dropdown actions
  const chipTrigger = document.getElementById('pilot-chip-trigger');
  const chipMenu = document.getElementById('pilot-chip-menu');

  if (chipTrigger && chipMenu) {
    const closeMenu = () => {
      chipMenu.classList.remove('open');
      chipTrigger.setAttribute('aria-expanded', 'false');
    };

    chipTrigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = chipMenu.classList.toggle('open');
      chipTrigger.setAttribute('aria-expanded', isOpen.toString());
    });

    document.addEventListener('click', (e) => {
      if (!chipMenu.contains(e.target) && !chipTrigger.contains(e.target)) {
        closeMenu();
      }
    });

    const actionMap = {
      undo,
      redo,
      reset: resetWorkspace,
      save: saveWorkspace,
      load: loadWorkspace,
    };

    chipMenu.querySelectorAll('.chip-option').forEach((btn) => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.action;
        if (action && actionMap[action]) {
          actionMap[action]();
        }
        closeMenu();
      });
    });
  }

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
      view.style.display = isMatch ? 'block' : 'none';
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

// --- Lesson simulation helpers ---
let simCanvas, simCtx, simStatusEl;
const simState = {
  x: 0,
  y: 0,
  heading: 0,
  flying: false
};
const SIM_SCALE = 2; // pixels per cm

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
  simState.flying = false;
  drawSim('Ready');
}

function setSimStatus(text) {
  if (simStatusEl) {
    simStatusEl.textContent = text;
  }
}

function drawSim(statusText) {
  if (!simCanvas || !simCtx) return;
  simCtx.clearRect(0, 0, simCanvas.width, simCanvas.height);
  simCtx.fillStyle = '#0f172a';
  simCtx.fillRect(0, 0, simCanvas.width, simCanvas.height);
  // Trail placeholder could be added later
  simCtx.fillStyle = '#a855f7';
  simCtx.beginPath();
  simCtx.arc(simState.x, simState.y, 8, 0, Math.PI * 2);
  simCtx.fill();

  const headingRad = (simState.heading * Math.PI) / 180;
  simCtx.strokeStyle = '#22d3ee';
  simCtx.beginPath();
  simCtx.moveTo(simState.x, simState.y);
  simCtx.lineTo(simState.x + Math.cos(headingRad) * 18, simState.y + Math.sin(headingRad) * 18);
  simCtx.stroke();

  if (statusText) {
    simCtx.fillStyle = '#e2e8f0';
    simCtx.font = '12px Inter, sans-serif';
    simCtx.fillText(statusText, 12, 18);
  }
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
      setSimStatus('Takeoff');
      break;
    case 'land':
      simState.flying = false;
      setSimStatus('Land');
      break;
    case 'emergency':
      simState.flying = false;
      setSimStatus('Emergency stop: landed');
      break;
    case 'turn':
      simState.heading += op.dir === 'cw' ? op.value : -op.value;
      setSimStatus(`Turn ${op.dir} ${op.value} deg`);
      break;
    case 'move': {
      const radians = (simState.heading * Math.PI) / 180;
      const distancePx = (op.value || 0) * SIM_SCALE;
      const dx = Math.cos(radians) * distancePx;
      const dy = Math.sin(radians) * distancePx;
      simState.x = Math.max(8, Math.min(simCanvas.width - 8, simState.x + dx));
      simState.y = Math.max(8, Math.min(simCanvas.height - 8, simState.y + dy));
      setSimStatus(`Move ${op.dir} ${op.value}cm`);
      break;
    }
    case 'wait':
      setSimStatus(`Wait ${op.value}s`);
      break;
    case 'flip':
      setSimStatus(`Flip ${op.dir}`);
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

function runDroneSimulation(pythonCode, logToConsole) {
  if (!simCanvas || !simCtx) {
    if (logToConsole) logToConsole('[SIM] No sim canvas found.');
    return;
  }
  const skipped = [];
  const ops = parsePythonToOps(pythonCode, skipped);
  if (skipped.length && logToConsole) {
    // The simulator cannot evaluate conditions or variables; say so instead of guessing.
    logToConsole(`[SIM] Not simulated (needs the real drone/Python): ${skipped.join(' | ')}`);
  }
  if (!ops.length) {
    resetSimState();
    if (logToConsole) logToConsole('[SIM] No flight ops detected.');
    return;
  }
  resetSimState();
  let elapsed = 0;
  ops.forEach((op) => {
    const opDuration = op.type === 'wait' ? Math.max(400, op.value * 400) : 500;
    setTimeout(() => {
      applySimOp(op);
      if (logToConsole) logToConsole(`[SIM] ${op.type} ${op.value ?? ''}`.trim());
    }, elapsed);
    elapsed += opDuration;
  });
  setTimeout(() => setSimStatus('Complete'), elapsed + 200);
}

// In workspace.js, REPLACE the old 'runCode' function:

// === NEW RUN FUNCTION (Sends to Drone) ===
async function runCode() {
  stopRequested = false;
  ensureTelloPythonGenerators(); // guard against missing generators

  if (!commandReady && !IS_LESSON_SIM) {
    alert('Connect to the drone first and wait for command mode.');
    setPythonStatus('Connect to Tello first.', 'error');
    setLaunchButtonIdle();
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

  if (IS_LESSON_SIM) {
    logToConsole('--- [SIM] Running lesson sandbox (no drone) ---');
    setPythonStatus('Simulating blocks...', 'running');
    runDroneSimulation(pythonCode, logToConsole);
    setPythonStatus('Simulation complete.', 'idle');
    sendCodeToParent('complete');
    setLaunchButtonIdle();
    return;
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
  if (IS_LESSON_SIM) {
    resetSimState();
    setPythonStatus('Simulation stopped.', 'idle');
    sendCodeToParent('stopped');
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

function resetWorkspace() {
  if (confirm('Are you sure you want to reset the workspace? All unsaved changes will be lost.')) {
    workspace.clear();
  }
}

function saveWorkspace() {
  const xml = Blockly.Xml.workspaceToDom(workspace);
  const xmlText =
  Blockly.Xml.domToText(xml);
  localStorage.setItem('blocklyWorkspace', xmlText);
  alert('Workspace saved!');
}

function loadWorkspace() {
  const xmlText = localStorage.getItem('blocklyWorkspace');
  if (xmlText) {
    // Blockly.Xml.textToDom was removed in Blockly 11; the helper now lives in utils.xml.
    const xml = Blockly.utils.xml.textToDom(xmlText);
    Blockly.Xml.domToWorkspace(xml, workspace);
    alert('Workspace loaded!');
  } else {
    alert('No saved workspace found.');
  }
}

function goHome() {
  // workspace.php sets data-home: admin dashboard for admins, home for students.
  window.location.href = document.body.dataset.home || "../HTML/home.html";
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
  workspace.addChangeListener(() => {
    updatePythonPreview();
    setPythonStatus('Blocks updated. Ready to run.', 'idle');
    sendCodeToParent('idle');
  });
}

// --- New Drone Status Logic ---
const TELLO_BRIDGE_URL = "http://127.0.0.1:5000";
const telloConnection = {
  connected: false,
  battery: null,
};

function updateStatus(connected, battery, telemetry = null, cmdReady = null) {
  telloConnection.connected = !!connected;
  telloConnection.battery = typeof battery === "number" ? battery : null;
  // In lesson/sim mode keep the commandReady flag true so the Launch button works offline
  commandReady = IS_LESSON_SIM
    ? true
    : (cmdReady !== null ? !!cmdReady : !!connected || !!telemetry?.command_ready);

  const connectBtn = document.getElementById("connect-btn");
  const sidebarConnLabel = document.getElementById("sidebar-connection-label");
  const sidebarBatteryLabel = document.getElementById("sidebar-battery-label");
  const telemetryPitch = document.getElementById("telemetry-pitch");
  const telemetryRoll = document.getElementById("telemetry-roll");
  const telemetryYaw = document.getElementById("telemetry-yaw");
  const connStatus = document.getElementById("connection-status");
  const connIcon = document.getElementById("connection-icon");
  const connText = document.getElementById("connection-text");
  const battStatus = document.getElementById("battery-status");
  const battIcon = document.getElementById("battery-icon");
  const battText = document.getElementById("battery-text");
  const iconEl = connectBtn?.querySelector("i");

  connectBtn?.classList.remove("connecting");

  const pitch = telemetry?.pitch ?? null;
  const roll = telemetry?.roll ?? null;
  const yaw = telemetry?.yaw ?? null;

  if (connected) {
    if (connStatus) connStatus.className = "status-box status-green";
    if (battStatus) battStatus.className = "status-box status-yellow";
    if (connIcon) connIcon.className = "bx bx-wifi";
    if (connText) connText.textContent = "Connected (DJI Tello)";
    if (battIcon) battIcon.className = "bx bx-battery";
    if (battText) battText.textContent = battery !== null ? `Battery: ${battery}%` : "Battery: Unknown";
    if (sidebarConnLabel) sidebarConnLabel.textContent = "Connected";
    if (sidebarBatteryLabel) sidebarBatteryLabel.textContent = battery !== null ? `Battery: ${battery}%` : "Battery: Unknown";
    if (telemetryPitch) telemetryPitch.textContent = pitch !== null ? pitch : "--";
    if (telemetryRoll) telemetryRoll.textContent = roll !== null ? roll : "--";
    if (telemetryYaw) telemetryYaw.textContent = yaw !== null ? yaw : "--";
    if (connectBtn) {
      connectBtn.setAttribute("aria-pressed", "true");
      connectBtn.classList.remove("connecting");
      connectBtn.classList.add("connected");
    }
    if (iconEl) iconEl.className = "bx bx-check";
  } else {
    if (connIcon) connIcon.className = "bx bx-wifi";
    if (connText) connText.textContent = "Not Connected";
    if (battIcon) battIcon.className = "bx bx-battery";
    if (battText) battText.textContent = "Battery: Unknown";
    if (sidebarConnLabel) sidebarConnLabel.textContent = "Not Connected";
    if (sidebarBatteryLabel) sidebarBatteryLabel.textContent = "Battery: Unknown";
    if (telemetryPitch) telemetryPitch.textContent = "--";
    if (telemetryRoll) telemetryRoll.textContent = "--";
    if (telemetryYaw) telemetryYaw.textContent = "--";
    if (connectBtn) {
      connectBtn.setAttribute("aria-pressed", "false");
      connectBtn.classList.remove("connected");
    }
    if (iconEl) iconEl.className = "bx bx-rocket";
  }

  // gate the run/takeoff button
  setRunEnabled(commandReady || IS_LESSON_SIM);
}

async function checkStatus() {
  try {
    const response = await fetch(`${TELLO_BRIDGE_URL}/status`);
    const data = await response.json();
    const telem = data.position || data.telemetry || data || null;
    updateStatus(data.connected, data.battery, telem, data.command_ready);
    return !!data.connected;
  } catch (err) {
    updateStatus(false, null); // Bridge is offline
    return false;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const connectBtn = document.getElementById("connect-btn");

  if (connectBtn) {
    connectBtn.addEventListener("click", async () => {
      connectBtn.disabled = true;

      if (telloConnection.connected) {
        // --- If connected, we want to disconnect (UI only) ---
        updateStatus(false, null);
        console.log("Disconnecting from UI.");
        connectBtn.disabled = false;
      } else {
        const sidebarConnLabel = document.getElementById("sidebar-connection-label");
        if (sidebarConnLabel) sidebarConnLabel.textContent = "Connecting...";
        connectBtn.classList.remove("connected");
        connectBtn.classList.add("connecting");
        connectBtn.setAttribute("aria-pressed", "mixed");

        try {
          console.log("Attempting to connect via /connect...");
          const response = await fetch(`${TELLO_BRIDGE_URL}/connect`);
          const data = await response.json();
          if (!response.ok || data?.status !== "connected") {
            throw new Error(data?.error || data?.status || "Unable to connect to drone.");
          }
          updateStatus(true, data?.battery ?? null, data, data?.command_ready);
        } catch (err) {
          updateStatus(false, null);
          console.error("Failed to connect to Tello", err);
        } finally {
          connectBtn.disabled = false;
        }
      }
    });
  }
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

