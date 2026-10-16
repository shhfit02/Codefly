; Inno Setup 6 script for the CodeFly drone bridge. Build with build-bridge.bat.
; Installs for the current user only (no admin rights needed, fine on school PCs), then:
;  - starts the bridge at Windows login (HKCU Run key),
;  - registers codefly-bridge:// so the CodeFly site's "Start bridge" button can launch it.

#define AppName "CodeFly Bridge"
#define AppExe "CodeFlyBridge.exe"
#define AppVersion "1.0"

[Setup]
AppId={{6F1C3B52-9A4E-4D7B-8C21-CF0D1B7E5A10}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher=CodeFly
DefaultDirName={localappdata}\Programs\{#AppName}
DisableProgramGroupPage=yes
DisableDirPage=yes
PrivilegesRequired=lowest
OutputDir=dist
OutputBaseFilename=CodeFlyBridgeSetup
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
UninstallDisplayIcon={app}\{#AppExe}
CloseApplications=force

[Files]
Source: "dist\{#AppExe}"; DestDir: "{app}"; Flags: ignoreversion
Source: "allowed_origins.txt"; DestDir: "{app}"; Flags: ignoreversion

[Registry]
; Start at login.
Root: HKCU; Subkey: "Software\Microsoft\Windows\CurrentVersion\Run"; ValueType: string; ValueName: "CodeFlyBridge"; ValueData: """{app}\{#AppExe}"""; Flags: uninsdeletevalue
; codefly-bridge:// links start the bridge (the browser asks before opening it).
Root: HKCU; Subkey: "Software\Classes\codefly-bridge"; ValueType: string; ValueName: ""; ValueData: "URL:CodeFly Bridge"; Flags: uninsdeletekey
Root: HKCU; Subkey: "Software\Classes\codefly-bridge"; ValueType: string; ValueName: "URL Protocol"; ValueData: ""
Root: HKCU; Subkey: "Software\Classes\codefly-bridge\shell\open\command"; ValueType: string; ValueName: ""; ValueData: """{app}\{#AppExe}"" ""%1"""

[Run]
Filename: "{app}\{#AppExe}"; Description: "Start {#AppName} now"; Flags: nowait postinstall

[UninstallRun]
Filename: "{sys}\taskkill.exe"; Parameters: "/IM {#AppExe} /F"; Flags: runhidden; RunOnceId: "StopBridge"
