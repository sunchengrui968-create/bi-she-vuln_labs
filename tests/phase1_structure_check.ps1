# Baseline lab structure check adapted for difficulty implementations.
$ErrorActionPreference = "Stop"
$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")

function Read-ProjectFile {
    param([string] $RelativePath)
    $path = Join-Path $projectRoot $RelativePath
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Missing required file: $RelativePath" }
    return Get-Content -LiteralPath $path -Raw -Encoding UTF8
}

function Assert-Contains {
    param([string] $Content, [string] $Needle, [string] $Message)
    if (-not $Content.Contains($Needle)) { throw $Message }
}

function Assert-NotContains {
    param([string] $Content, [string] $Needle, [string] $Message)
    if ($Content.Contains($Needle)) { throw $Message }
}

$rootPages = @("sqli", "command", "stored_xss", "reflected_xss", "upload", "file_include", "csrf", "dom_xss")
foreach ($file in @("database/init.sql", "config.php", "index.php", "assets/css/style.css", "flags/.htaccess", "uploads/README.txt")) {
    Read-ProjectFile $file | Out-Null
}

foreach ($slug in $rootPages) {
    $rootSource = Read-ProjectFile "$slug.php"
    Assert-Contains $rootSource "resolve_vulnerability_source('$slug')" "$slug.php does not load its whitelisted implementation"
    Assert-Contains $rootSource "handle_flag_submission('$slug', `$difficulty)" "$slug.php does not isolate flag submission"
    Assert-NotContains $rootSource "get_challenge_flag(" "$slug.php directly fetches a flag"
    Read-ProjectFile "vulnerabilities/$slug/low.php" | Out-Null
}

$config = Read-ProjectFile "config.php"
foreach ($needle in @(
    "function get_pdo", "function render_header", "function render_footer", "function get_challenges",
    "function get_lab_flag_file_path", "function set_lab_flag_cookie", "function decode_shell_output",
    "function is_local_request", "function reset_lab_state", "function handle_lab_reset_request",
    "function clear_lab_flag_cookies", "function resolve_vulnerability_source"
)) { Assert-Contains $config $needle "config.php missing required function: $needle" }

$index = Read-ProjectFile "index.php"
foreach ($needle in @("handle_lab_reset_request", "reset-panel", "reset_lab", "btn-reset", 'method="post"')) {
    Assert-Contains $index $needle "index.php missing reset content: $needle"
}

$sql = Read-ProjectFile "database/init.sql"
foreach ($needle in @('CREATE DATABASE', 'users', 'messages', 'challenge_progress', '`password`', '`password_hash`', 'SHA2(')) {
    Assert-Contains $sql $needle "init.sql missing baseline content: $needle"
}
Assert-NotContains $sql "password_hint" "init.sql still contains removed password_hint field"
foreach ($needle in @("Admin@Lab2026!", "Alice#Sqlmap2026", "Bob#Union2026", "Teacher#Demo2026")) {
    Assert-Contains $sql $needle "init.sql missing expected SQLi demonstration password"
}

$lowChecks = @{
    "sqli" = @("SELECT id, username AS first_name, nickname AS surname FROM users WHERE id =", "query(")
    "command" = @("shell_exec", "decode_shell_output")
    "stored_xss" = @("INSERT INTO messages", "WHERE difficulty = ?")
    "reflected_xss" = @('$_GET', "output")
    "upload" = @("move_uploaded_file", "uploads/low")
    "file_include" = @("file_get_contents", "../flags/low/include.txt")
    "csrf" = @("change_email", "get_csrf_email_session_key")
    "dom_xss" = @("innerHTML", "script")
}

foreach ($slug in $lowChecks.Keys) {
    $source = Read-ProjectFile "vulnerabilities/$slug/low.php"
    foreach ($needle in $lowChecks[$slug]) {
        Assert-Contains $source $needle "Low $slug missing preserved behavior: $needle"
    }
}

$css = Read-ProjectFile "assets/css/style.css"
foreach ($needle in @(".app-shell", ".sidebar", ".challenge-card", ".lab-layout", ".reset-panel", ".btn-reset")) {
    Assert-Contains $css $needle "style.css missing baseline selector: $needle"
}

Write-Host "Lab structure check passed."
