@echo off
rem For computers that run the bridge from Python (no CodeFlyBridge.exe):
rem starts tello_bridge.py hidden at Windows login and registers codefly-bridge:// so the
rem CodeFly site's "Start bridge" button can launch it. Its output goes to bridge.log.
rem   setup-autostart.bat          set it up (and start the bridge now)
rem   setup-autostart.bat remove   undo it
title CodeFly Bridge autostart
cd /d "%~dp0.."

if /i "%~1"=="remove" (
    reg delete "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v CodeFlyBridge /f >nul 2>nul
    reg delete "HKCU\Software\Classes\codefly-bridge" /f >nul 2>nul
    echo Removed: the bridge no longer starts at login.
    pause
    exit /b 0
)

where py >nul 2>nul
if errorlevel 1 (
    echo Python was not found. Install Python 3 from https://www.python.org and tick "Add to PATH".
    pause
    exit /b 1
)
for /f "delims=" %%P in ('py -c "import os, sys; print(os.path.join(os.path.dirname(sys.executable), 'pythonw.exe'))"') set "PYW=%%P"
if not exist "%PYW%" (
    echo pythonw.exe was not found next to Python: %PYW%
    pause
    exit /b 1
)
py -c "import flask, flask_cors, djitellopy, cv2" 2>nul || py -m pip install flask flask-cors djitellopy opencv-python

set "BRIDGE=%CD%\tello_bridge.py"
reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v CodeFlyBridge /t REG_SZ /d "\"%PYW%\" \"%BRIDGE%\"" /f >nul
reg add "HKCU\Software\Classes\codefly-bridge" /ve /t REG_SZ /d "URL:CodeFly Bridge" /f >nul
reg add "HKCU\Software\Classes\codefly-bridge" /v "URL Protocol" /t REG_SZ /d "" /f >nul
reg add "HKCU\Software\Classes\codefly-bridge\shell\open\command" /ve /t REG_SZ /d "\"%PYW%\" \"%BRIDGE%\" \"%%1\"" /f >nul

start "" "%PYW%" "%BRIDGE%"
echo Done. The bridge is running now and will start by itself at every login.
echo To add the hosted CodeFly site, put its address in allowed_origins.txt next to tello_bridge.py.
pause
