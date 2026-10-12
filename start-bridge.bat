@echo off
rem Double-click to start the CodeFly drone bridge (tello_bridge.py).
rem Keep this window open while flying; close it to stop the bridge.
title CodeFly Drone Bridge
cd /d "%~dp0"

where py >nul 2>nul
if errorlevel 1 (
    echo Python was not found. Install Python 3 from https://www.python.org and tick "Add to PATH".
    pause
    exit /b 1
)

echo Starting the CodeFly drone bridge on http://127.0.0.1:5000
echo 1. Connect this computer's Wi-Fi to TELLO-xxxxxx
echo 2. In CodeFly, choose Drone and press Connect drone
echo Keep this window open while flying.
echo.
py tello_bridge.py

echo.
echo The bridge has stopped.
echo If it said a module is missing, run:  py -m pip install flask flask-cors djitellopy opencv-python
pause
