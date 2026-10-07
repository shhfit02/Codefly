/*
================================================
          BLOCKLY INJECTION & TOOLBOX
================================================
*/
const blocklyDiv = document.getElementById('blocklyDiv');

// Custom theme to align block colors with category palette
const CodeflyTheme = Blockly.Theme.defineTheme('codefly', {
  base: Blockly.Themes.Classic,
  blockStyles: {
    math_blocks: {
      colourPrimary: '#5a63b5',
      colourSecondary: '#4b538f',
      colourTertiary: '#3a426e',
    },
    logic_blocks: {
      colourPrimary: '#b84040',
      colourSecondary: '#9e3535',
      colourTertiary: '#7c2a2a',
    },
    loop_blocks: {
      colourPrimary: '#d9b24c',
      colourSecondary: '#bf9c42',
      colourTertiary: '#9a7b32',
    },
    variable_blocks: {
      colourPrimary: '#8b3f7c',
      colourSecondary: '#743568',
      colourTertiary: '#5b294f',
    },
    procedure_blocks: {
      colourPrimary: '#694fa3',
      colourSecondary: '#574185',
      colourTertiary: '#46346a',
    },
    text_blocks: {
      colourPrimary: '#4fc3e5',
      colourSecondary: '#42a6c4',
      colourTertiary: '#3386a0',
    },
  },
});

const workspace = window.workspace = Blockly.inject(blocklyDiv, {
  toolbox: document.getElementById('toolbox'),
  renderer: 'zelos',
  theme: CodeflyTheme,
  // Local media for offline use
  media: '../Assets/blockly/media/',
  trashcan: true,
  move: {
    scrollbars: true,
    drag: true,
    wheel: true,
  },
  zoom: {
    controls: true,
    wheel: true,
    startScale: 0.9,
  },
});

const numberShadow = (value) => ({
  shadow: {
    type: 'math_number',
    fields: { NUM: value },
  },
});

const toolboxContents = {
  'Takeoff': [
    { kind: 'block', type: 'tello_takeoff' },
    { kind: 'block', type: 'tello_takeoff_delay', inputs: { DELAY: numberShadow(5) } },
  ],
  'Navigation': [
    { kind: 'block', type: 'tello_move_direction', inputs: { DISTANCE: numberShadow(50) } },
    { kind: 'block', type: 'tello_turn', inputs: { DEGREES: numberShadow(90) } },
    { kind: 'block', type: 'tello_wait', inputs: { SECONDS: numberShadow(1) } },
  ],
  'Camera': [
    { kind: 'block', type: 'tello_take_photo' },
    { kind: 'block', type: 'tello_take_photo_burst', inputs: { COUNT: numberShadow(5), INTERVAL: numberShadow(5) } },
  ],
  'Flip': [
    { kind: 'block', type: 'tello_flip_forward' },
    { kind: 'block', type: 'tello_flip_backward' },
    { kind: 'block', type: 'tello_flip_left' },
    { kind: 'block', type: 'tello_flip_right' },
  ],
  'Loops': [
    { kind: 'block', type: 'controls_repeat_ext', inputs: { TIMES: numberShadow(10) } },
    { kind: 'block', type: 'controls_whileUntil' },
  ],
  'Logic': [
    { kind: 'block', type: 'controls_if' },
    { kind: 'block', type: 'logic_compare' },
    { kind: 'block', type: 'logic_operation' },
    { kind: 'block', type: 'logic_boolean' },
  ],
  'Math': [
    { kind: 'block', type: 'math_number', fields: { NUM: 0 } },
    { kind: 'block', type: 'math_arithmetic' },
    { kind: 'block', type: 'math_single' },
    { kind: 'block', type: 'math_trig' },
    { kind: 'block', type: 'math_constant' },
    { kind: 'block', type: 'math_number_property' },
    { kind: 'block', type: 'math_round' },
    { kind: 'block', type: 'math_modulo' },
    { kind: 'block', type: 'math_constrain' },
    { kind: 'block', type: 'math_random_int' },
    { kind: 'block', type: 'math_random_float' },
  ],
  'Variables': [
    { kind: 'block', type: 'variables_get' },
    { kind: 'block', type: 'variables_set' },
  ],
  'Functions': [
    { kind: 'block', type: 'tello_bounce' },
    { kind: 'block', type: 'tello_circle', inputs: { DURATION: numberShadow(3) } },
    { kind: 'block', type: 'tello_spin_360' },
  ],
  'Land': [
    { kind: 'block', type: 'tello_land' },
    { kind: 'block', type: 'tello_land_delay', inputs: { DELAY: numberShadow(5) } },
  ],
};


/*
================================================
          BLOCK DEFINITIONS
================================================
*/

Blockly.Blocks['tello_takeoff'] = {
  init: function () {
    this.appendDummyInput().appendField('take off');
    // Hat-style start: no block above, can stack below
    this.setPreviousStatement(false);
    this.setNextStatement(true, 'Command');
    this.setColour('#234a52');
    this.setTooltip('Command the drone to take off');
  },
};

// Early guard: define Python generators immediately if the generator object exists
(function ensureEarlyTelloGenerators() {
  const maybeSet = (target) => {
    if (!target) return;
    if (!target['tello_takeoff']) target['tello_takeoff'] = () => 'tello.takeoff()\n';
    if (!target['tello_land']) target['tello_land'] = () => 'tello.land()\n';
  };
  if (typeof Blockly !== 'undefined' && Blockly.Python) {
    maybeSet(Blockly.Python);
  }
  if (typeof pythonGenerator !== 'undefined') {
    pythonGenerator.forBlock = pythonGenerator.forBlock || pythonGenerator;
    maybeSet(pythonGenerator.forBlock);
  }
})();

// Define Python code generation for the takeoff block (explicit alias form)
if (typeof pythonGenerator !== 'undefined') {
  pythonGenerator.forBlock = pythonGenerator.forBlock || pythonGenerator;
  pythonGenerator.forBlock['tello_takeoff'] = function (block) {
    return 'tello.takeoff()\n';
  };
}

Blockly.Blocks['tello_takeoff_delay'] = {
  init: function () {
    this.appendValueInput('DELAY')
      .setCheck('Number')
      .appendField('takeoff after');
    this.appendDummyInput().appendField('seconds');
    // Hat-style start: no block above, can stack below
    this.setPreviousStatement(false);
    this.setNextStatement(true, 'Command');
    this.setColour('#234a52');
    this.setTooltip('Wait the specified seconds, then take off');
  },
};

Blockly.Blocks['tello_land'] = {
  init: function () {
    this.appendDummyInput().appendField('land');
    // Terminal action: can stack above, ends chain
    this.setPreviousStatement(true, 'Command');
    this.setNextStatement(false);
    this.setColour('#d96e4d');
    this.setTooltip('Command the drone to land');
  },
};

// Used by the lesson toolbox for block-01 (workspace.js lessonToolboxConfigs).
Blockly.Blocks['tello_emergency'] = {
  init: function () {
    this.appendDummyInput().appendField('EMERGENCY STOP');
    // Lands immediately and ends the program, so nothing can follow it
    this.setPreviousStatement(true, null);
    this.setNextStatement(false);
    this.setColour('#FF4D4F');
    this.setTooltip('Stop the program and land the drone immediately.');
  },
};

Blockly.Blocks['tello_land_delay'] = {
  init: function () {
    this.appendDummyInput().appendField('land for');
    this.appendValueInput('DELAY')
      .setCheck('Number');
    this.appendDummyInput().appendField('seconds then takeoff');
    this.setInputsInline(true);
    // The drone is flying again afterwards, so blocks (e.g. land) must be able to follow.
    this.setPreviousStatement(true, 'Command');
    this.setNextStatement(true, 'Command');
    this.setColour('#d96e4d');
    this.setTooltip('Land for N seconds, then take off again');
  },
};

Blockly.Blocks['tello_move_direction'] = {
  init: function () {
    this.appendValueInput('DISTANCE')
      .setCheck('Number')
      .appendField('fly')
      .appendField(
        new Blockly.FieldDropdown([
          ['forward', 'forward'],
          ['backward', 'back'],
          ['left', 'left'],
          ['right', 'right'],
          ['up', 'up'],
          ['down', 'down'],
        ]),
        'DIRECTION'
      );
    this.appendDummyInput()
      .appendField(
        new Blockly.FieldDropdown([
          ['cm', 'cm'],
          ['in', 'in'],
          ['m', 'm'],
        ]),
        'UNIT'
      );
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#18816c');
    this.setTooltip('Move the drone in the chosen direction for a distance');
  },
};

Blockly.Blocks['tello_turn'] = {
  init: function () {
    this.appendValueInput('DEGREES')
      .setCheck('Number')
      .appendField(
        new Blockly.FieldDropdown([
          ['turn clockwise', 'cw'],
          ['turn counterclockwise', 'ccw'],
        ]),
        'TURN'
      )
      .appendField('by');
    this.appendDummyInput().appendField('degrees');
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#18816c');
    this.setTooltip('Rotate the drone by a given number of degrees');
  },
};

Blockly.Blocks['tello_flip_left'] = {
  init: function () {
    this.appendDummyInput().appendField('flip left');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#006b92');
    this.setTooltip('Flip the drone left');
  },
};

Blockly.Blocks['tello_flip_right'] = {
  init: function () {
    this.appendDummyInput().appendField('flip right');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#006b92');
    this.setTooltip('Flip the drone right');
  },
};

Blockly.Blocks['tello_flip_forward'] = {
  init: function () {
    this.appendDummyInput().appendField('flip forward');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#006b92');
    this.setTooltip('Flip the drone forward');
  },
};

Blockly.Blocks['tello_flip_backward'] = {
  init: function () {
    this.appendDummyInput().appendField('flip backward');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#006b92');
    this.setTooltip('Flip the drone backward');
  },
};

Blockly.Blocks['tello_take_photo'] = {
  init: function () {
    this.appendDummyInput().appendField('take photo');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#4fc3e5');
    this.setTooltip('Capture a single photo');
  },
};

Blockly.Blocks['tello_take_photo_burst'] = {
  init: function () {
    this.appendDummyInput().appendField('take');
    this.appendValueInput('COUNT')
      .setCheck('Number');
    this.appendDummyInput().appendField('photos with');
    this.appendValueInput('INTERVAL')
      .setCheck('Number');
    this.appendDummyInput().appendField('sec interval');
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#4fc3e5');
    this.setTooltip('Take multiple photos with a delay between each shot');
  },
};

Blockly.Blocks['tello_wait'] = {
  init: function () {
    this.appendValueInput('SECONDS')
      .setCheck('Number')
      .appendField('wait');
    this.appendDummyInput().appendField('seconds');
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#18816c');
    this.setTooltip('Wait for a number of seconds');
  },
};

// --- Function / stunt helpers ---
Blockly.Blocks['tello_bounce'] = {
  init: function () {
    this.appendDummyInput().appendField('bounce mode');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#694fa3');
    this.setTooltip('Start bounce mode (short up/down pulses)');
  },
};

Blockly.Blocks['tello_circle'] = {
  init: function () {
    this.appendDummyInput().appendField('circle for');
    this.appendValueInput('DURATION').setCheck('Number');
    this.appendDummyInput().appendField('seconds');
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#694fa3');
    this.setTooltip('Fly a gentle circle for the given time');
  },
};

Blockly.Blocks['tello_spin_360'] = {
  init: function () {
    this.appendDummyInput().appendField('spin 360 deg');
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#694fa3');
    this.setTooltip('Rotate a full 360 degrees');
  },
};

Blockly.Blocks['tello_read_battery'] = {
  init: function () {
    this.appendDummyInput().appendField('battery (%)');
    this.setOutput(true, 'Number');
    this.setColour('#4fc3e5');
    this.setTooltip('Read the battery percentage');
  },
};

Blockly.Blocks['tello_read_height'] = {
  init: function () {
    this.appendDummyInput().appendField('height (cm)');
    this.setOutput(true, 'Number');
    this.setColour('#4fc3e5');
    this.setTooltip('Read the current height');
  },
};

// Legacy block kept for backwards compatibility
Blockly.Blocks['tello_move_rc'] = {
  init: function () {
    this.appendDummyInput()
      .appendField('RC move')
      .appendField(
        new Blockly.FieldDropdown([
          ['forward', 'forward'],
          ['backward', 'backward'],
          ['left', 'left'],
          ['right', 'right'],
        ]),
        'DIRECTION'
      );
    this.appendValueInput('SECONDS')
      .setCheck('Number')
      .appendField('for');
    this.appendValueInput('SPEED')
      .setCheck('Number')
      .appendField('speed');
    this.setInputsInline(true);
    this.setPreviousStatement(true, null);
    this.setNextStatement(true, null);
    this.setColour('#18816c');
    this.setTooltip('Send RC control commands for a number of seconds');
  },
};


/*
================================================
          PYTHON GENERATORS
================================================
*/

Blockly.Python['tello_takeoff'] = function () {
  return 'tello.takeoff()\n';
};

Blockly.Python['tello_takeoff_delay'] = function (block) {
  const delay = Blockly.Python.valueToCode(block, 'DELAY', Blockly.Python.ORDER_ATOMIC) || 0;
  return `time.sleep(${delay})\ntello.takeoff()\n`;
};

Blockly.Python['tello_land'] = function () {
  return 'tello.land()\n';
};

Blockly.Python['tello_land_delay'] = function (block) {
  const delay = Blockly.Python.valueToCode(block, 'DELAY', Blockly.Python.ORDER_ATOMIC) || 0;
  return `tello.land()\ntime.sleep(${delay})\ntello.takeoff()\n`;
};

Blockly.Python['tello_move_direction'] = function (block) {
  const dir = block.getFieldValue('DIRECTION');
  const dist = Blockly.Python.valueToCode(block, 'DISTANCE', Blockly.Python.ORDER_ATOMIC) || 0;
  const unit = block.getFieldValue('UNIT') || 'cm';
  let distExpr = dist;
  if (unit === 'in') {
    distExpr = `(${dist})*2.54`;
  } else if (unit === 'm') {
    distExpr = `(${dist})*100`;
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


Blockly.Python['tello_move_rc'] = function (block) {
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

Blockly.Python['tello_take_photo'] = function () {
  return 'tello.take_picture()\n';
};

Blockly.Python['tello_take_photo_burst'] = function (block) {
  const count = Blockly.Python.valueToCode(block, 'COUNT', Blockly.Python.ORDER_ATOMIC) || 0;
  const interval = Blockly.Python.valueToCode(block, 'INTERVAL', Blockly.Python.ORDER_ATOMIC) || 0;
  return `
for _ in range(int(${count})):
    tello.take_picture()
    time.sleep(${interval})
`;
};

// Ensure generators are registered even if workspace.js helpers don't run first
if (typeof Blockly !== 'undefined' && Blockly.Python) {
  Blockly.Python['tello_takeoff'] = () => 'tello.takeoff()\n';
  Blockly.Python['tello_takeoff_delay'] = (block) => {
    const delay = Blockly.Python.valueToCode(block, 'DELAY', Blockly.Python.ORDER_ATOMIC) || 0;
    return `time.sleep(${delay})\ntello.takeoff()\n`;
  };
  Blockly.Python['tello_take_photo'] = () => 'tello.take_picture()\n';
  Blockly.Python['tello_take_photo_burst'] = (block) => {
    const count = Blockly.Python.valueToCode(block, 'COUNT', Blockly.Python.ORDER_ATOMIC) || 0;
    const interval = Blockly.Python.valueToCode(block, 'INTERVAL', Blockly.Python.ORDER_ATOMIC) || 0;
    return `
for _ in range(int(${count})):
    tello.take_picture()
    time.sleep(${interval})
`;
  };
  Blockly.Python['tello_land'] = () => 'tello.land()\n';
  Blockly.Python['tello_move_direction'] = (block) => {
    const dir = block.getFieldValue('DIRECTION');
    const dist = Blockly.Python.valueToCode(block, 'DISTANCE', Blockly.Python.ORDER_ATOMIC) || 0;
    const unit = block.getFieldValue('UNIT') || 'cm';
    let distExpr = dist;
    if (unit === 'in') {
      distExpr = `(${dist})*2.54`;
    } else if (unit === 'm') {
      distExpr = `(${dist})*100`;
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
  Blockly.Python['tello_turn'] = (block) => {
    const direction = block.getFieldValue('TURN');
    const degrees = Blockly.Python.valueToCode(block, 'DEGREES', Blockly.Python.ORDER_ATOMIC) || 0;
    const method = direction === 'ccw' ? 'rotate_counter_clockwise' : 'rotate_clockwise';
    return `tello.${method}(${degrees})\n`;
  };
  Blockly.Python['tello_move_rc'] = (block) => {
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
  Blockly.Python['tello_flip_left'] = () => "tello.flip('l')\n";
  Blockly.Python['tello_flip_right'] = () => "tello.flip('r')\n";
  Blockly.Python['tello_flip_forward'] = () => "tello.flip('f')\n";
  Blockly.Python['tello_flip_backward'] = () => "tello.flip('b')\n";
  Blockly.Python['print_message'] = (block) => {
    const msg = Blockly.Python.valueToCode(block, 'TEXT', Blockly.Python.ORDER_ATOMIC) || '""';
    return `print(${msg})\n`;
  };
  Blockly.Python['tello_wait'] = (block) => {
    const seconds = Blockly.Python.valueToCode(block, 'SECONDS', Blockly.Python.ORDER_ATOMIC) || 0;
    return `time.sleep(${seconds})\n`;
  };
}

Blockly.Python['tello_flip_left'] = function () {
  return "tello.flip('l')\n";
};

Blockly.Python['tello_flip_right'] = function () {
  return "tello.flip('r')\n";
};

Blockly.Python['tello_flip_forward'] = function () {
  return "tello.flip('f')\n";
};

Blockly.Python['tello_flip_backward'] = function () {
  return "tello.flip('b')\n";
};


Blockly.Python['print_message'] = function (block) {
  const msg = Blockly.Python.valueToCode(block, 'TEXT', Blockly.Python.ORDER_ATOMIC) || '""';
  return `print(${msg})\n`;
};

Blockly.Python['tello_wait'] = function (block) {
  const seconds = Blockly.Python.valueToCode(block, 'SECONDS', Blockly.Python.ORDER_ATOMIC) || 0;
  return `time.sleep(${seconds})\n`;
};

// Function helpers
Blockly.Python['tello_bounce'] = function () {
  return `try:
    tello.send_command_with_return("bounce")
except Exception:
    tello.bounce() if hasattr(tello, "bounce") else None
`;
};

Blockly.Python['tello_circle'] = function (block) {
  const duration = Blockly.Python.valueToCode(block, 'DURATION', Blockly.Python.ORDER_ATOMIC) || 0;
  return `tello.send_rc_control(0, 40, 0, 40)
time.sleep(${duration})
tello.send_rc_control(0, 0, 0, 0)
`;
};

Blockly.Python['tello_spin_360'] = function () {
  return 'tello.rotate_clockwise(360)\n';
};

Blockly.Python['tello_read_battery'] = function () {
  return ['tello.get_battery()', Blockly.Python.ORDER_FUNCTION_CALL];
};

Blockly.Python['tello_read_height'] = function () {
  return ['tello.get_height()', Blockly.Python.ORDER_FUNCTION_CALL];
};
