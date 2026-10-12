import os
import threading
import time
from flask import Flask, jsonify, request
from flask_cors import CORS
from djitellopy import Tello

# ===============================
# DJI TELLO BRIDGE SERVER
# ===============================

app = Flask(__name__)
# Only CodeFly pages served from this PC (XAMPP :8080, Live Server, ...) may call the bridge.
# With CORS open to every origin, any website open in the browser could POST to
# /run_script and run Python on this computer.
LOCAL_ORIGIN = r"^https?://(localhost|127\.0\.0\.1)(:\d+)?$"
CORS(app, origins=[LOCAL_ORIGIN])


@app.before_request
def reject_foreign_host():
    """
    Block DNS rebinding: a site whose domain is re-pointed at 127.0.0.1 would count as
    same-origin and skip CORS, but its requests still carry its own Host header.
    """
    host = (request.host or "").split(":")[0]
    if host not in ("127.0.0.1", "localhost"):
        return jsonify({"status": "failed", "error": "Forbidden host"}), 403

# Create a global Tello object
tello = Tello()
lock = threading.Lock()  # Simple command lock to avoid overlapping scripts

# Global state
is_connected = False
battery_level = None

# Set by /stop so a running Blockly script stops sending commands after landing.
stop_event = threading.Event()
# Last /run_script result, polled by the workspace via /script_status.
script_state = {"state": "idle", "error": None, "photos": []}

PHOTO_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "photos")
MOVE_MIN_CM, MOVE_MAX_CM = 20, 500      # Tello SDK limits for up/down/left/right/forward/back
TURN_MIN_DEG, TURN_MAX_DEG = 1, 360     # Tello SDK limits for cw/ccw
BOUNCE_HEIGHT_CM = 30


def set_disconnected():
    """Helper to reset connection flags."""
    global is_connected, battery_level
    is_connected = False
    battery_level = None


def check_range(value, low, high, what, unit):
    """Tello rejects out-of-range values with a vague 'out of range'; explain it instead."""
    if not low <= value <= high:
        raise ValueError(f"{what} must be between {low} and {high} {unit} (got {value})")
    return value


def take_picture():
    """
    Save one camera frame as a JPEG in ./photos and return its path.
    djitellopy has no take_picture(); photos come from the video stream.
    """
    import cv2  # installed with djitellopy

    reader = tello.background_frame_read
    if reader is None:
        tello.streamon()
        reader = tello.get_frame_read()
    # The reader starts with a black placeholder frame; wait for real video.
    deadline = time.time() + 5
    frame = reader.frame
    while (frame is None or not frame.any()) and time.time() < deadline:
        time.sleep(0.1)
        frame = reader.frame
    if frame is None or not frame.any():
        raise RuntimeError("No video frame received from the Tello camera")

    os.makedirs(PHOTO_DIR, exist_ok=True)
    stamp = time.strftime("%Y%m%d_%H%M%S")
    path = os.path.join(PHOTO_DIR, f"photo_{stamp}_{int(time.time() * 1000) % 1000:03d}.jpg")
    # djitellopy 2.5 frames are RGB (PyAV); OpenCV writes BGR.
    cv2.imwrite(path, cv2.cvtColor(frame, cv2.COLOR_RGB2BGR))
    print(f"Photo saved: {path}")
    return path


def emergency_land():
    """
    EMERGENCY STOP block: land immediately instead of tello.emergency(), which cuts the
    motors and drops the drone. Skipped when the drone is already on the ground
    (Tello answers 'error' to land while landed).
    """
    if tello.is_flying:
        tello.land()


def bounce():
    """Tello has no 'bounce' SDK command, so do two short up/down pulses."""
    for _ in range(2):
        tello.move_up(BOUNCE_HEIGHT_CM)
        tello.move_down(BOUNCE_HEIGHT_CM)


# ===============================
# API ROUTES
# ===============================
@app.route('/connect')
def connect_drone():
    """Connect to the Tello and report battery."""
    global is_connected, battery_level
    try:
        print("Connecting to Tello...")
        tello.connect()
        # Explicit SDK command handshake and a short pause
        tello.send_command_with_return("command")
        time.sleep(1.0)
        is_connected = True
        battery_level = tello.get_battery()
        print(f"Connection successful! Battery: {battery_level}%")
        return jsonify({"status": "connected", "battery": battery_level, "command_ready": True})
    except Exception as e:
        print("Connect error:", e)
        set_disconnected()
        return jsonify({"status": "failed", "error": str(e)}), 500


@app.route('/status')
def status():
    """Returns connection + battery in one JSON."""
    global battery_level
    pitch = None
    roll = None
    yaw = None
    if is_connected:
        try:
            battery_level = tello.get_battery()
            pitch = tello.get_pitch()
            roll = tello.get_roll()
            yaw = tello.get_yaw()
        except Exception as e:
            print(f"Status check failed: {e}")
            battery_level = None
            # Assume connection lost if status call fails
            set_disconnected()

    return jsonify({
        "connected": is_connected,
        "battery": battery_level,
        "command_ready": is_connected,
        "pitch": pitch,
        "roll": roll,
        "yaw": yaw
    })


@app.route('/takeoff', methods=['POST'])
def takeoff():
    """Direct takeoff endpoint for REST-driven commands."""
    if not is_connected:
        return jsonify({"status": "failed", "error": "Drone not connected"}), 400
    try:
        print("TAKEOFF command received via REST")
        tello.takeoff()
        return jsonify({"status": "takeoff started"})
    except Exception as e:
        print("Takeoff error:", e)
        return jsonify({"status": "failed", "error": str(e)}), 500


@app.route('/stop')
def stop_script():
    """Emergency stop / land."""
    mode = request.args.get('mode', 'land')
    # Tell any running Blockly script to stop issuing commands (checked before each one).
    stop_event.set()
    try:
        if mode == 'emergency':
            print("EMERGENCY stop command received")
            tello.emergency()
            return jsonify({"status": "emergency command sent"})
        else:
            print("STOP/LAND command received")
            # Tello answers 'error' to land while already on the ground.
            if not tello.is_flying:
                return jsonify({"status": "already on the ground"})
            tello.land()  # Use land for safety
            return jsonify({"status": "land command sent"})
    except Exception as e:
        return jsonify({"status": "failed", "error": str(e)}), 500


@app.route('/command', methods=['POST'])
def single_command():
    """Execute a single drone command; used for step-by-step runs."""
    if not is_connected:
        return jsonify({"status": "failed", "error": "Drone not connected"}), 400

    payload = request.json or {}
    cmd_type = payload.get("type")
    result: dict = {}  # extra fields for the response (landed, photo path)

    try:
        with lock:
            if cmd_type == "takeoff":
                tello.takeoff()
            elif cmd_type == "land":
                tello.land()
            elif cmd_type == "emergency":
                emergency_land()
                result["landed"] = True
            elif cmd_type == "turn":
                direction = payload.get("dir")
                value = check_range(abs(int(float(payload.get("value", 0)))), TURN_MIN_DEG, TURN_MAX_DEG, "Turn angle", "degrees")
                if direction == "cw":
                    tello.rotate_clockwise(value)
                else:
                    tello.rotate_counter_clockwise(value)
            elif cmd_type == "move":
                direction = str(payload.get("dir", ""))
                value = check_range(round(float(payload.get("value", 0))), MOVE_MIN_CM, MOVE_MAX_CM, "Distance", "cm")
                method_map = {
                    "forward": tello.move_forward,
                    "back": tello.move_back,
                    "left": tello.move_left,
                    "right": tello.move_right,
                    "up": tello.move_up,
                    "down": tello.move_down,
                }
                mover = method_map.get(direction)
                if not mover:
                    raise ValueError(f"Invalid move direction: {direction}")
                mover(value)
            elif cmd_type == "flip":
                direction = payload.get("dir", "").lower()[:1]
                if direction not in ("f", "b", "l", "r"):
                    raise ValueError(f"Invalid flip direction: {direction}")
                tello.flip(direction)
            elif cmd_type == "bounce":
                bounce()
            elif cmd_type == "photo":
                result["photo"] = take_picture()
            elif cmd_type == "circle":
                duration = float(payload.get("value", 0))
                speed = int(float(payload.get("speed", 40)))
                speed = max(10, min(speed, 60))
                tello.send_rc_control(0, speed, 0, speed)
                time.sleep(max(0, duration))
                tello.send_rc_control(0, 0, 0, 0)
            elif cmd_type == "wait":
                wait_seconds = float(payload.get("value", 0))
                time.sleep(wait_seconds)
            else:
                return jsonify({"status": "failed", "error": f"Unknown command type: {cmd_type}"}), 400
    except Exception as e:
        return jsonify({"status": "failed", "error": str(e)}), 500

    return jsonify({"status": "ok", "type": cmd_type, **result})


class ScriptStopped(Exception):
    """Raised inside a running script after /stop was called."""


def check_stop():
    if stop_event.is_set():
        raise ScriptStopped("Stopped by user")


class ScriptTello:
    """
    The 'tello' object Blockly scripts see: the real Tello plus the helpers the blocks use
    (take_picture, bounce), and a stop check before every drone command.
    """

    def take_picture(self):
        check_stop()
        path = take_picture()
        script_state["photos"].append(path)
        return path

    def bounce(self):
        check_stop()
        bounce()

    def emergency_land(self):
        # Always land, even if Stop was already pressed, then end the script.
        emergency_land()
        raise ScriptStopped("Emergency stop: drone landed")

    def __getattr__(self, name):
        attr = getattr(tello, name)
        if not callable(attr):
            return attr

        def guarded(*args, **kwargs):
            check_stop()
            return attr(*args, **kwargs)

        return guarded


class ScriptTime:
    """time module for scripts, with a sleep that wakes up as soon as /stop is called."""

    def sleep(self, seconds):
        if stop_event.wait(max(0.0, float(seconds))):
            raise ScriptStopped("Stopped by user")

    def __getattr__(self, name):
        return getattr(time, name)


def execute_tello_script(code_to_run: str):
    """
    Runs the received code in a separate thread.
    'tello' and 'time' are made available to the executed code.
    """

    def custom_rotate(degrees):
        check_stop()
        degrees = int(degrees)
        if degrees > 0:
            tello.rotate_clockwise(degrees)
        elif degrees < 0:
            tello.rotate_counter_clockwise(abs(degrees))

    script_globals = {
        'tello': ScriptTello(),
        'time': ScriptTime(),
        'custom_rotate': custom_rotate,
    }

    try:
        print(f"--- Executing Script ---\n{code_to_run}\n--------------------------")
        with lock:
            exec(code_to_run, script_globals)
        script_state.update(state="finished", error=None)
        print("--- Script Finished ---")
    except ScriptStopped as e:
        script_state.update(state="stopped", error=str(e))
        print("--- Script Stopped ---")
    except Exception as e:
        script_state.update(state="error", error=f"{type(e).__name__}: {e}")
        print(f"Script Error: {e}")


@app.route('/run_script', methods=['POST'])
def run_script():
    """Receives Python code from Blockly and executes it."""
    if not is_connected:
        return jsonify({"status": "failed", "error": "Drone not connected"}), 400

    data = request.json
    if not data or 'code' not in data:
        return jsonify({"status": "failed", "error": "No 'code' provided"}), 400

    if script_state["state"] == "running":
        return jsonify({"status": "failed", "error": "Another script is still running"}), 409

    code_from_blockly = data['code']
    stop_event.clear()
    script_state.update(state="running", error=None, photos=[])

    # Run the script in a new thread so it doesn't block the server
    threading.Thread(target=execute_tello_script, args=(code_from_blockly,), daemon=True).start()

    return jsonify({"status": "script_running"})


@app.route('/script_status')
def get_script_status():
    """Lets the workspace show whether the last script finished, failed or was stopped."""
    return jsonify(script_state)


# ===============================
# MAIN
# ===============================
if __name__ == '__main__':
    print("Tello bridge (djitellopy) running on http://127.0.0.1:5000")
    print("Make sure your PC is connected to TELLO Wi-Fi before using the UI.")
    # Localhost only: /run_script executes Python, so it must not be reachable from the network.
    app.run(host='127.0.0.1', port=5000)
