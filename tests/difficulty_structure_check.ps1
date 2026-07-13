# Low / Medium difficulty structure and isolation check.
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

$slugs = @("sqli", "command", "stored_xss", "reflected_xss", "upload", "file_include", "csrf", "dom_xss")

foreach ($slug in $slugs) {
    foreach ($difficulty in @("low", "medium")) { Read-ProjectFile "vulnerabilities/$slug/$difficulty.php" | Out-Null }
    Read-ProjectFile "$slug.php" | Out-Null
}

foreach ($file in @(
    "database/migrations/002_add_difficulty_levels.sql",
    "flags/low/command.txt", "flags/low/include.txt", "flags/low/upload.txt",
    "flags/medium/command.txt", "flags/medium/include.txt", "flags/medium/upload.txt",
    "uploads/low/README.txt", "uploads/medium/README.txt", "tools/csrf_medium.html"
)) { Read-ProjectFile $file | Out-Null }

$config = Read-ProjectFile "config.php"
foreach ($needle in @(
    "function ensure_session_started", "function get_allowed_difficulties", "function get_lab_difficulty",
    "function set_lab_difficulty", "function handle_difficulty_change_request", "function resolve_vulnerability_source",
    "function get_challenge_progress(string `$difficulty)",
    "function get_challenge_flag(string `$slug, string `$difficulty)",
    "function get_lab_flag_file_path(string `$slug, string `$difficulty)",
    "function reset_lab_state(string `$difficulty)",
    "`$_SESSION['lab_difficulty'] = 'low'", "['low', 'medium']", "WHERE difficulty = ?",
    "csrf_demo_email_", "lab_", "_flag", "DIRECTORY_SEPARATOR . `$difficulty"
)) { Assert-Contains $config $needle "config.php missing difficulty/isolation behavior: $needle" }

foreach ($needle in @(
    "resolve_vulnerability_source(`$_GET", "resolve_vulnerability_source(`$_POST",
    "'vulnerabilities/' . `$_GET", "'vulnerabilities/' . `$_POST"
)) { Assert-NotContains $config $needle "config.php uses request data in an include path: $needle" }

$index = Read-ProjectFile "index.php"
foreach ($needle in @(
    "difficulty-panel", "Security Level", "change_difficulty", "btn-apply-difficulty",
    "current-difficulty", "reset-current-difficulty"
)) { Assert-Contains $index $needle "index.php missing difficulty dashboard content: $needle" }

$css = Read-ProjectFile "assets/css/style.css"
foreach ($needle in @(".difficulty-panel", ".difficulty-options", ".difficulty-badge", ".difficulty-choice")) {
    Assert-Contains $css $needle "style.css missing difficulty selector: $needle"
}

$sql = Read-ProjectFile "database/init.sql"
foreach ($needle in @('`difficulty` VARCHAR(10)', 'uk_challenge_slug_difficulty', 'INSERT INTO `challenge_progress`', 'INSERT INTO `messages`')) {
    Assert-Contains $sql $needle "init.sql missing difficulty schema/seed content: $needle"
}

$seedPattern = '\(''(?<slug>[a-z_]+)'',\s*''(?<difficulty>low|medium)'',[^\r\n]*''(?<flag>FLAG\{[^}]+\})'''
$seedRows = [regex]::Matches($sql, $seedPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
if ($seedRows.Count -ne 16) { throw "init.sql must contain 16 single-line Low/Medium progress seeds; found $($seedRows.Count)" }

foreach ($slug in $slugs) {
    $rows = @($seedRows | Where-Object { $_.Groups['slug'].Value -eq $slug })
    if ($rows.Count -ne 2) { throw "init.sql must contain Low and Medium rows for $slug" }
    $lowFlag = ($rows | Where-Object { $_.Groups['difficulty'].Value -eq 'low' }).Groups['flag'].Value
    $mediumFlag = ($rows | Where-Object { $_.Groups['difficulty'].Value -eq 'medium' }).Groups['flag'].Value
    if ([string]::IsNullOrWhiteSpace($lowFlag) -or [string]::IsNullOrWhiteSpace($mediumFlag) -or $lowFlag -eq $mediumFlag) {
        throw "Low and Medium flags must be present and different for $slug"
    }
}

$migration = Read-ProjectFile "database/migrations/002_add_difficulty_levels.sql"
foreach ($needle in @(
    'ADD COLUMN `difficulty` VARCHAR(10)', 'DROP INDEX `uk_challenge_slug`',
    'ADD UNIQUE KEY `uk_challenge_slug_difficulty`', 'INSERT INTO `challenge_progress`', 'INSERT INTO `messages`'
)) { Assert-Contains $migration $needle "migration missing required operation: $needle" }

$mediumChecks = @{
    "sqli" = @("str_replace", "safeDbError", "query(")
    "command" = @("blockedConnectors", "shell_exec", "PHP_OS_FAMILY")
    "stored_xss" = @("preg_replace", "<script", "INSERT INTO messages")
    "reflected_xss" = @("strip_tags", "<img><svg>")
    "upload" = @("getimagesize", "UPLOAD_ERR_OK", "move_uploaded_file")
    "file_include" = @("str_replace('../'", "file_get_contents", "safeIncludeError")
    "csrf" = @("HTTP_REFERER", "HTTP_HOST", "strpos")
    "dom_xss" = @("replace", "innerHTML")
}

foreach ($slug in $mediumChecks.Keys) {
    $source = Read-ProjectFile "vulnerabilities/$slug/medium.php"
    foreach ($needle in $mediumChecks[$slug]) { Assert-Contains $source $needle "Medium $slug missing flawed defense: $needle" }
}

Write-Host "Difficulty structure check passed."
