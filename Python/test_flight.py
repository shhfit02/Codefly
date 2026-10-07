from djitellopy import Tello
import time

# Connect to Tello
tello = Tello()
tello.connect()
print(f"Battery: {tello.get_battery()}%")

print("Type commands: takeoff, land, forward <cm>, back <cm>, left <cm>, right <cm>, up <cm>, down <cm>, flip <direction>, rotate <cw/ccw> <degrees>, quit")

while True:
    cmd = input("Enter command: ").strip().lower()
    
    if cmd == "takeoff":
        tello.takeoff()
    elif cmd == "land":
        tello.land()
    elif cmd.startswith("forward "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(0, dist, 0, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("back "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(0, -dist, 0, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("left "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(-dist, 0, 0, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("right "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(dist, 0, 0, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("up "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(0, 0, dist, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("down "):
        dist = int(cmd.split()[1])
        tello.send_rc_control(0, 0, -dist, 0)
        time.sleep(2)
        tello.send_rc_control(0, 0, 0, 0)
    elif cmd.startswith("flip "):
        direction = cmd.split()[1]
        if direction in ["f","b","l","r"]:
            tello.flip(direction)
    elif cmd.startswith("rotate "):
        parts = cmd.split()
        if len(parts) == 3:
            direction, degrees = parts[1], int(parts[2])
            if direction == "cw":
                tello.rotate_clockwise(degrees)
            elif direction == "ccw":
                tello.rotate_counter_clockwise(degrees)
    elif cmd == "quit":
        print("Landing and exiting...")
        tello.land()
        break
    else:
        print("Unknown command")

tello.end()
print("Connection closed")
