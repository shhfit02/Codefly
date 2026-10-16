@echo off
rem Builds CodeFlyBridge.exe (no Python needed on students' computers) and, if Inno Setup 6
rem is installed, the installer CodeFlyBridgeSetup.exe that starts the bridge at login and
rem lets the CodeFly site start it with a codefly-bridge:// link.
rem Before building, put the hosted CodeFly address in allowed_origins.txt.
title Build CodeFly Bridge
cd /d "%~dp0"

where py >nul 2>nul
if errorlevel 1 (
    echo Python was not found. Install Python 3 from https://www.python.org and tick "Add to PATH".
    pause
    exit /b 1
)

echo Installing build tools and bridge libraries...
py -m pip install --upgrade pyinstaller flask flask-cors djitellopy opencv-python || goto :failed

echo Building CodeFlyBridge.exe...
py -m PyInstaller --noconfirm --onefile --noconsole --name CodeFlyBridge ^
    --distpath dist --workpath build --specpath build ..\tello_bridge.py || goto :failed

set "ISCC=%ProgramFiles(x86)%\Inno Setup 6\ISCC.exe"
if not exist "%ISCC%" set "ISCC=%ProgramFiles%\Inno Setup 6\ISCC.exe"
if not exist "%ISCC%" set "ISCC=%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe"
if not exist "%ISCC%" (
    echo.
    echo Built dist\CodeFlyBridge.exe.
    echo To also build the installer, install Inno Setup 6 from https://jrsoftware.org/isdl.php
    echo and run this file again.
    pause
    exit /b 0
)

echo Building the installer...
"%ISCC%" codefly-bridge.iss || goto :failed
echo.
echo Done: dist\CodeFlyBridgeSetup.exe
pause
exit /b 0

:failed
echo.
echo The build failed; see the messages above.
pause
exit /b 1
