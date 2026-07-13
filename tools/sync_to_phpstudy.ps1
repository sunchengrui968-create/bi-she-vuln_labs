# Sync tracked project delivery files to the configured phpStudy site.
# Personal files tools/csrf.html and tools/readflag.php are intentionally excluded.
$ErrorActionPreference = "Stop"
$sourceRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$targetRoot = "E:\phpstudy_pro\WWW\labs"

if (-not (Test-Path -LiteralPath $targetRoot -PathType Container)) {
    throw "Target phpStudy directory does not exist: $targetRoot"
}

$files = @(
    "config.php", "index.php", "sqli.php", "command.php", "stored_xss.php", "reflected_xss.php",
    "upload.php", "file_include.php", "csrf.php", "dom_xss.php",
    "database/init.sql", "database/migrations/002_add_difficulty_levels.sql",
    "assets/css/style.css", "flags/.htaccess",
    "flags/low/command.txt", "flags/low/include.txt", "flags/low/upload.txt",
    "flags/medium/command.txt", "flags/medium/include.txt", "flags/medium/upload.txt",
    "uploads/README.txt", "uploads/low/README.txt", "uploads/medium/README.txt",
    "tools/csrf_medium.html", "tests/phase1_structure_check.ps1", "tests/difficulty_structure_check.ps1"
)

$slugs = @("sqli", "command", "stored_xss", "reflected_xss", "upload", "file_include", "csrf", "dom_xss")
foreach ($slug in $slugs) {
    $files += "vulnerabilities/$slug/low.php"
    $files += "vulnerabilities/$slug/medium.php"
}

foreach ($file in $files) {
    $source = Join-Path $sourceRoot $file
    $target = Join-Path $targetRoot $file
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) { throw "Missing source file: $file" }
    if (-not (Test-Path -LiteralPath $targetDir -PathType Container)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }
    Copy-Item -LiteralPath $source -Destination $target -Force
}

Write-Host "Synced project files to $targetRoot"
