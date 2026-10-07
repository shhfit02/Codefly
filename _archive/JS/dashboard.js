let workspace = Blockly.inject('blocklyDiv', {
  toolbox: document.getElementById('toolbox'),
  scrollbars: true,
  trashcan: true,
});

function runCode() {
  let code = Blockly.JavaScript.workspaceToCode(workspace);
  document.getElementById('consoleOutput').textContent = "Running...\n" + code;

  // Later: Send to ESP32 via HTTP or WebSocket
}

function resetWorkspace() {
  workspace.clear();
  document.getElementById('consoleOutput').textContent = "Workspace cleared.";
}
