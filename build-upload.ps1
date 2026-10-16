# Builds codefly-upload.zip: only the files the hosted website needs, ready to upload
# to the host's htdocs / public_html folder.
#
#   powershell -ExecutionPolicy Bypass -File build-upload.ps1
#
# Left out: tests, archive, drone bridge and installer sources (the bridge runs on each
# student's computer), build tooling, the database dump (import it in phpMyAdmin instead)
# and the *_config.php files (create those on the host: they hold passwords).

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$excludedFolders = @('tests/', '_archive/', 'Python/', 'installer/', 'node_modules/', 'photos/')
$excludedFiles = @(
    '.gitignore', 'build-upload.ps1', 'codefly_database.sql',
    'package.json', 'package-lock.json', 'playwright.config.js', 'postcss.config.js',
    'tailwind.config.js', 'start-bridge.bat', 'tello_bridge.py',
    'PHP/db_config.php', 'PHP/mail_config.php', 'PHP/weather_config.php',
    'PHP/db_config.example.php', 'PHP/mail_config.example.php', 'PHP/weather_config.example.php'
)

# Tracked files plus new files not yet committed; .gitignore'd files are skipped.
$files = git ls-files --cached --others --exclude-standard | Where-Object {
    $path = $_
    -not ($excludedFiles -contains $path) -and
    -not ($excludedFolders | Where-Object { $path.StartsWith($_) }) -and
    (Test-Path -LiteralPath $path)
}

# The bridge installer is git-ignored but the workspace links to it.
$installer = 'Assets/downloads/CodeFlyBridgeSetup.exe'
if (Test-Path $installer) {
    $files += $installer
} else {
    Write-Warning "$installer not found: build it (installer/build-bridge.bat) or the workspace download link will be broken."
}

$stage = Join-Path $PSScriptRoot 'codefly-upload'
$zip = Join-Path $PSScriptRoot 'codefly-upload.zip'
if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
if (Test-Path $zip) { Remove-Item $zip -Force }

foreach ($file in $files) {
    $target = Join-Path $stage $file
    New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
    Copy-Item -LiteralPath $file $target
}

# Windows' own tar (bsdtar) writes zip paths with "/". Compress-Archive in Windows
# PowerShell 5.1 can write "\", which a Linux host extracts as files named "PHP\db.php".
$topLevel = Get-ChildItem $stage -Force | ForEach-Object { $_.Name }
& "$env:SystemRoot\System32\tar.exe" -a -c -f $zip -C $stage @topLevel
if ($LASTEXITCODE -ne 0) { throw "tar failed with exit code $LASTEXITCODE" }
Write-Host "$($files.Count) files -> codefly-upload.zip (folder: codefly-upload\)"
