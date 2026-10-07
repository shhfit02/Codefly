"""
Simple color-based object tracking for DJI Tello using OpenCV and djitellopy.

Usage:
- Install deps: pip install djitellopy opencv-python numpy
- Connect your computer to the Tello Wi-Fi.
- Run: python tello_tracker.py
- Press 'q' to quit; the script will land the drone.

Tune LOWER/UPPER for your target color in HSV space, and adjust KP_XY/KP_Z/TARGET_AREA
to change responsiveness and preferred distance.
"""

import cv2
import numpy as np
from djitellopy import Tello

# HSV bounds for a green object; change to track another color.
LOWER = np.array([40, 70, 70])
UPPER = np.array([80, 255, 255])

# Control gains (reduce for smoother, increase for snappier movement).
KP_XY = 0.3  # horizontal/vertical responsiveness
KP_Z = 0.4   # distance responsiveness

FRAME_W, FRAME_H = 960, 720
TARGET_AREA = 14_000  # desired contour area representing distance to target
MIN_AREA = 500        # ignore tiny contours/noise


def main() -> None:
    tello = Tello()
    tello.connect()
    tello.streamon()
    tello.takeoff()
    tello.send_rc_control(0, 0, 0, 0)

    try:
        while True:
            frame = tello.get_frame_read().frame
            frame = cv2.resize(frame, (FRAME_W, FRAME_H))
            hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)

            mask = cv2.inRange(hsv, LOWER, UPPER)
            mask = cv2.erode(mask, None, iterations=2)
            mask = cv2.dilate(mask, None, iterations=2)

            contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
            cx = cy = area = 0
            detected = False

            if contours:
                contour = max(contours, key=cv2.contourArea)
                area = cv2.contourArea(contour)
                if area > MIN_AREA:
                    x, y, w, h = cv2.boundingRect(contour)
                    cx, cy = x + w // 2, y + h // 2
                    cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
                    cv2.circle(frame, (cx, cy), 5, (0, 0, 255), -1)
                    detected = True

            # PID-ish control based on target offset and size.
            err_x = cx - FRAME_W // 2
            err_y = cy - FRAME_H // 2
            err_z = TARGET_AREA - area

            yaw = int(np.clip(-KP_XY * err_x, -60, 60))   # left/right rotate
            up = int(np.clip(-KP_XY * err_y, -60, 60))    # up/down
            fwd = int(np.clip(KP_Z * err_z, -40, 40))     # forward/back

            if detected:
                tello.send_rc_control(0, fwd, up, yaw)
            else:
                tello.send_rc_control(0, 0, 0, 0)  # hover if no target

            # Overlay helpful UI: center crosshair, detection status, and telemetry.
            center_x, center_y = FRAME_W // 2, FRAME_H // 2
            cv2.drawMarker(frame, (center_x, center_y), (255, 255, 255), cv2.MARKER_CROSS, 20, 1)
            status = "TARGET" if detected else "SEARCHING"
            cv2.putText(frame, f"{status} area={int(area)} fwd={fwd} up={up} yaw={yaw}",
                        (15, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2, cv2.LINE_AA)

            cv2.imshow("mask", mask)
            cv2.imshow("frame", frame)
            if cv2.waitKey(1) & 0xFF == ord("q"):
                break
    finally:
        tello.send_rc_control(0, 0, 0, 0)
        tello.land()
        tello.streamoff()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
