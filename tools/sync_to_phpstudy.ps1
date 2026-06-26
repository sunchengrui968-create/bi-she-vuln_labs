# Sync current project files to the active phpStudy site directory.
#
# This script is useful when Codex can edit the workspace but does not have
# permission to write to E:\phpstudy_pro\WWW\labs directly.
#
# Run from the project root with:
#   powershell -ExecutionPolicy Bypass -File tools/sync_to_phpstudy.ps1

$ErrorActionPreference = "Stop"

$sourceRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$targetRoot = "E:\phpstudy_pro\WWW\labs"

if (-not (Test-Path -LiteralPath $targetRoot -PathType Container)) {
    throw "Target phpStudy directory does not exist: $targetRoot"
}

$files = @(
    "config.php",
    "index.php",
    "sqli.php",
    "command.php",
    "stored_xss.php",
    "reflected_xss.php",
    "upload.php",
    "flags/.htaccess",
    "flags/command.txt",
    "flags/upload.txt",
    "database/init.sql",
    "assets/css/style.css",
    "tests/phase1_structure_check.ps1",
    "uploads/README.txt"
)

foreach ($file in $files) {
    $source = Join-Path $sourceRoot $file
    $target = Join-Path $targetRoot $file
    $targetDir = Split-Path -Parent $target

    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
        throw "Missing source file: $file"
    }

    if (-not (Test-Path -LiteralPath $targetDir -PathType Container)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }

    Copy-Item -LiteralPath $source -Destination $target -Force
}

Write-Host "Synced project files to $targetRoot"
