# Phase 1 structure check.
# This script verifies the first delivery batch without requiring PHP CLI.
# Run from project root:
#   powershell -ExecutionPolicy Bypass -File tests/phase1_structure_check.ps1

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")

$requiredFiles = @(
    "database/init.sql",
    "config.php",
    "index.php",
    "assets/css/style.css",
    "sqli.php",
    "command.php",
    "stored_xss.php",
    "reflected_xss.php",
    "upload.php",
    "file_include.php",
    "csrf.php",
    "dom_xss.php",
    "flags/.htaccess",
    "flags/command.txt",
    "flags/include.txt",
    "flags/upload.txt",
    "uploads/README.txt"
)

foreach ($file in $requiredFiles) {
    $path = Join-Path $projectRoot $file
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        throw "Missing required file: $file"
    }
}

$sql = Get-Content -LiteralPath (Join-Path $projectRoot "database/init.sql") -Raw -Encoding UTF8
foreach ($needle in @('CREATE DATABASE', 'users', 'messages', 'challenge_progress')) {
    if ($sql -notlike "*$needle*") {
        throw "init.sql missing required content: $needle"
    }
}

function Assert-Contains {
    param(
        [string] $Content,
        [string] $Needle,
        [string] $Message
    )

    if (-not $Content.Contains($Needle)) {
        throw $Message
    }
}

function Assert-NotContains {
    param(
        [string] $Content,
        [string] $Needle,
        [string] $Message
    )

    if ($Content.Contains($Needle)) {
        throw $Message
    }
}

$config = Get-Content -LiteralPath (Join-Path $projectRoot "config.php") -Raw -Encoding UTF8
foreach ($needle in @("function get_pdo", "function render_header", "function render_footer", "function get_challenges", "function get_lab_flag_file_path", "function set_lab_flag_cookie", "function decode_shell_output", "function is_local_request", "function reset_lab_state", "function handle_lab_reset_request", "function clear_lab_flag_cookies")) {
    Assert-Contains $config $needle "config.php missing required function: $needle"
}

foreach ($needle in @("UPDATE challenge_progress SET status = 'incomplete'", "DELETE FROM messages", "student@vuln-lab.local", "lab_stored_xss_flag", "lab_reflected_xss_flag", "lab_dom_xss_flag", "uploads", "README.txt")) {
    Assert-Contains $config $needle "config.php missing reset behavior: $needle"
}

$index = Get-Content -LiteralPath (Join-Path $projectRoot "index.php") -Raw -Encoding UTF8
$pageSources = $index + "`n" + $config
foreach ($needle in @("sqli.php", "command.php", "stored_xss.php", "reflected_xss.php", "upload.php", "file_include.php", "csrf.php", "dom_xss.php")) {
    if ($pageSources -notlike "*$needle*") {
        throw "page configuration missing required challenge link: $needle"
    }
}

foreach ($needle in @("handle_lab_reset_request", "reset-panel", "reset_lab", "reset_message", "reset_status", "btn-reset", "method=""post""")) {
    Assert-Contains $index $needle "index.php missing reset module content: $needle"
}

foreach ($needle in @("file_include", "csrf", "dom_xss", "FLAG{FILE_INCLUDE_PATH_TRAVERSAL}", "FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}", "FLAG{DOM_XSS_LOCATION_INNERHTML}")) {
    Assert-Contains $config $needle "config.php missing new module configuration: $needle"
}

foreach ($needle in @("'file_include'", "'csrf'", "'dom_xss'", "FLAG{FILE_INCLUDE_PATH_TRAVERSAL}", "FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}", "FLAG{DOM_XSS_LOCATION_INNERHTML}")) {
    Assert-Contains $sql $needle "init.sql missing new module seed content: $needle"
}

$css = Get-Content -LiteralPath (Join-Path $projectRoot "assets/css/style.css") -Raw -Encoding UTF8
foreach ($needle in @(".app-shell", ".sidebar", ".challenge-card", ".status-dot", ".lab-layout", ".vuln-terminal", ".reset-panel", ".reset-actions", ".reset-checklist", ".btn-reset")) {
    if ($css -notlike "*$needle*") {
        throw "style.css missing required selector: $needle"
    }
}

$pageChecks = @{
    "sqli.php" = @("SELECT id, username AS first_name, nickname AS surname FROM users WHERE id =", "query(", "id=")
    "command.php" = @("shell_exec", "decode_shell_output", "flags\\command.txt", "& whoami")
    "stored_xss.php" = @("INSERT INTO messages", "message-list", "set_lab_flag_cookie('stored_xss'")
    "reflected_xss.php" = @('$_GET', "search-result", "set_lab_flag_cookie('reflected_xss'")
    "upload.php" = @("move_uploaded_file", "uploads/", "flags/upload.txt")
    "file_include.php" = @("includeHint", "file_get_contents", "flags/include.txt")
    "csrf.php" = @("session_start", "change_email", "CSRF Token")
    "dom_xss.php" = @("set_lab_flag_cookie('dom_xss')", "location.hash", "innerHTML")
}

foreach ($page in $pageChecks.Keys) {
    $source = Get-Content -LiteralPath (Join-Path $projectRoot $page) -Raw -Encoding UTF8
    foreach ($needle in $pageChecks[$page]) {
        if ($source -notlike "*$needle*") {
            throw "$page missing required content: $needle"
        }
    }
}

$forbiddenPageContent = @{
    "sqli.php" = @("SELECT * FROM users", "password_hint", "profile", "array_keys(`$rows[0])", "<th>First name</th>", "<th>Surname</th>", "`$row['first_name']", "`$row['surname']")
    "command.php" = @("get_challenge_flag('command')", "`$commandFlag")
    "stored_xss.php" = @("get_challenge_flag('stored_xss')", "`$storedFlag")
    "reflected_xss.php" = @("get_challenge_flag('reflected_xss')", "`$reflectedFlag")
    "upload.php" = @("get_challenge_flag('upload')", "`$uploadFlag")
    "file_include.php" = @("get_challenge_flag('file_include')", "`$includeFlag")
    "csrf.php" = @("get_challenge_flag('csrf')", "`$csrfFlag")
    "dom_xss.php" = @("get_challenge_flag('dom_xss')", "`$domFlag")
}

foreach ($page in $forbiddenPageContent.Keys) {
    $source = Get-Content -LiteralPath (Join-Path $projectRoot $page) -Raw -Encoding UTF8
    foreach ($needle in $forbiddenPageContent[$page]) {
        Assert-NotContains $source $needle "$page still contains forbidden direct-leak content: $needle"
    }
}

Assert-NotContains $sql "password_hint" "init.sql still contains old users.password_hint field"

foreach ($needle in @('`password`', '`password_hash`', 'SHA2(')) {
    Assert-Contains $sql $needle "init.sql missing SQLi password seed content: $needle"
}

foreach ($needle in @("Admin@Lab2026!", "Alice#Sqlmap2026", "Bob#Union2026", "Teacher#Demo2026")) {
    Assert-Contains $sql $needle "init.sql missing expected SQLi demonstration password: $needle"
}

Write-Host "Lab structure check passed."
