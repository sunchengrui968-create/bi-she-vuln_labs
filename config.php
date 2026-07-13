<?php
/**
 * 毕业设计本地授权漏洞靶场公共配置。
 * 公共平台能力保持安全写法，故意不安全的逻辑只放在 vulnerabilities/ 中。
 */

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'vuln_db';
const DB_USER = 'root';
const DB_PASS = 'root';
const DB_CHARSET = 'utf8mb4';

const APP_NAME = '基于常见漏洞的靶场平台';
const APP_SUBTITLE = '毕业设计本地授权实验环境';

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function get_allowed_difficulties(): array
{
    return ['low', 'medium'];
}

function get_lab_difficulty(): string
{
    ensure_session_started();
    $difficulty = (string) ($_SESSION['lab_difficulty'] ?? 'low');

    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        $_SESSION['lab_difficulty'] = 'low';

        return 'low';
    }

    return $difficulty;
}

function set_lab_difficulty(string $difficulty): bool
{
    ensure_session_started();

    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        return false;
    }

    $_SESSION['lab_difficulty'] = $difficulty;

    return true;
}

function get_platform_csrf_token(): string
{
    ensure_session_started();

    if (!isset($_SESSION['platform_csrf_token']) || !is_string($_SESSION['platform_csrf_token'])) {
        $_SESSION['platform_csrf_token'] = bin2hex(random_bytes(24));
    }

    return $_SESSION['platform_csrf_token'];
}

function verify_platform_csrf_token(): bool
{
    ensure_session_started();
    $submitted = (string) ($_POST['platform_csrf_token'] ?? '');
    $expected = (string) ($_SESSION['platform_csrf_token'] ?? '');

    return $submitted !== '' && $expected !== '' && hash_equals($expected, $submitted);
}

function handle_difficulty_change_request(): array
{
    $result = [
        'checked' => false,
        'ok' => false,
        'difficulty' => get_lab_difficulty(),
        'message' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'change_difficulty') {
        return $result;
    }

    $result['checked'] = true;

    if (!verify_platform_csrf_token()) {
        $result['message'] = '请求校验失败，请刷新页面后重试。';

        return $result;
    }

    $difficulty = (string) ($_POST['difficulty'] ?? '');
    if (!set_lab_difficulty($difficulty)) {
        $result['message'] = '非法难度值已被拒绝，当前难度保持不变。';

        return $result;
    }

    $result['ok'] = true;
    $result['difficulty'] = $difficulty;
    $result['message'] = '靶场难度已应用。';

    return $result;
}

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_db_status(): array
{
    try {
        get_pdo()->query('SELECT 1');

        return [
            'ok' => true,
            'label' => '数据库连接正常',
            'detail' => '已连接到 ' . DB_NAME . '，可以读取靶场状态。',
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'label' => '数据库连接失败',
            'detail' => '请检查 phpStudy、数据库配置和难度迁移脚本。',
        ];
    }
}

function get_challenges(): array
{
    return [
        [
            'slug' => 'sqli',
            'title' => 'SQL 注入',
            'file' => 'sqli.php',
            'tag' => 'SQLi',
            'level' => '基础',
            'accent' => 'cyan',
            'summary' => '通过用户编号查询功能，观察直接拼接 SQL 带来的数据泄露风险。',
            'principle' => '后端把用户输入直接拼入 SELECT 语句，攻击者可以改变原 SQL 语义。',
        ],
        [
            'slug' => 'command',
            'title' => '命令注入',
            'file' => 'command.php',
            'tag' => 'RCE',
            'level' => '中等',
            'accent' => 'green',
            'summary' => '通过网络 Ping 测试功能，观察命令参数未过滤导致的命令执行。',
            'principle' => '后端把 IP 参数直接拼接到 shell_exec 命令中，特殊连接符会扩展命令。',
        ],
        [
            'slug' => 'stored_xss',
            'title' => '存储型 XSS',
            'file' => 'stored_xss.php',
            'tag' => 'XSS',
            'level' => '基础',
            'accent' => 'amber',
            'summary' => '通过留言板保存脚本内容，观察恶意内容被持久化后的影响。',
            'principle' => '输入原样入库，输出时不做完整 HTML 转义，脚本会在访问者浏览器执行。',
        ],
        [
            'slug' => 'reflected_xss',
            'title' => '反射型 XSS',
            'file' => 'reflected_xss.php',
            'tag' => 'XSS',
            'level' => '基础',
            'accent' => 'violet',
            'summary' => '通过搜索关键词回显，观察一次性脚本注入的触发方式。',
            'principle' => '请求参数被页面直接拼入 HTML 响应，浏览器会把恶意片段当作页面代码解析。',
        ],
        [
            'slug' => 'upload',
            'title' => '文件上传',
            'file' => 'upload.php',
            'tag' => 'Upload',
            'level' => '中等',
            'accent' => 'rose',
            'summary' => '通过头像上传组件，观察缺少后缀和内容校验造成的脚本上传风险。',
            'principle' => '后端的类型和图片校验不完整，原始脚本扩展名仍可能被保留。',
        ],
        [
            'slug' => 'file_include',
            'title' => '文件包含',
            'file' => 'file_include.php',
            'tag' => 'LFI',
            'level' => '中等',
            'accent' => 'cyan',
            'summary' => '通过帮助文档读取功能，观察用户可控路径拼接造成的本地文件读取风险。',
            'principle' => '后端把 page 参数拼接到文件路径中，不完整清理仍可被目录穿越绕过。',
        ],
        [
            'slug' => 'csrf',
            'title' => 'CSRF',
            'file' => 'csrf.php',
            'tag' => 'CSRF',
            'level' => '基础',
            'accent' => 'green',
            'summary' => '通过修改邮箱操作，观察缺少可靠 CSRF Token 时伪造请求的风险。',
            'principle' => '只依赖浏览器 Cookie 或弱 Referer 检查，不能可靠证明请求来自合法页面。',
        ],
        [
            'slug' => 'dom_xss',
            'title' => 'DOM 型 XSS',
            'file' => 'dom_xss.php',
            'tag' => 'DOM XSS',
            'level' => '基础',
            'accent' => 'violet',
            'summary' => '通过前端公告预览器，观察 URL 数据写入 innerHTML 后的脚本注入。',
            'principle' => '漏洞发生在浏览器端数据流中，不可信 URL 数据进入 innerHTML 后会被解析。',
        ],
    ];
}

function get_challenge_slugs(): array
{
    return array_column(get_challenges(), 'slug');
}

function resolve_vulnerability_source(string $slug): string
{
    if (!in_array($slug, get_challenge_slugs(), true)) {
        throw new InvalidArgumentException('未知漏洞模块。');
    }

    $difficulty = get_lab_difficulty();
    $source = __DIR__ . DIRECTORY_SEPARATOR . 'vulnerabilities'
        . DIRECTORY_SEPARATOR . $slug
        . DIRECTORY_SEPARATOR . $difficulty . '.php';

    if (!is_file($source)) {
        throw new RuntimeException('当前难度实现文件不存在。');
    }

    return $source;
}

function get_challenge_progress(string $difficulty): array
{
    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        return [];
    }

    try {
        $statement = get_pdo()->prepare(
            'SELECT slug, status, flag, updated_at FROM challenge_progress WHERE difficulty = ? ORDER BY sort_order ASC'
        );
        $statement->execute([$difficulty]);
        $rows = $statement->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }

    $progress = [];
    foreach ($rows as $row) {
        $progress[$row['slug']] = $row;
    }

    return $progress;
}

function get_fallback_flags(): array
{
    return [
        'low' => [
            'sqli' => 'FLAG{SQLI_UNION_QUERY_SUCCESS}',
            'command' => 'FLAG{COMMAND_INJECTION_LOCAL_EXEC}',
            'stored_xss' => 'FLAG{STORED_XSS_MESSAGE_BOARD}',
            'reflected_xss' => 'FLAG{REFLECTED_XSS_SEARCH_ECHO}',
            'upload' => 'FLAG{FILE_UPLOAD_WEBSHELL_LAB}',
            'file_include' => 'FLAG{FILE_INCLUDE_PATH_TRAVERSAL}',
            'csrf' => 'FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}',
            'dom_xss' => 'FLAG{DOM_XSS_LOCATION_INNERHTML}',
        ],
        'medium' => [
            'sqli' => 'FLAG{MEDIUM_SQLI_FILTER_BYPASS}',
            'command' => 'FLAG{MEDIUM_COMMAND_SEPARATOR_BYPASS}',
            'stored_xss' => 'FLAG{MEDIUM_STORED_XSS_EVENT_HANDLER}',
            'reflected_xss' => 'FLAG{MEDIUM_REFLECTED_XSS_ALLOWED_TAG}',
            'upload' => 'FLAG{MEDIUM_UPLOAD_IMAGE_POLYGLOT}',
            'file_include' => 'FLAG{MEDIUM_LFI_NESTED_TRAVERSAL}',
            'csrf' => 'FLAG{MEDIUM_CSRF_WEAK_REFERER}',
            'dom_xss' => 'FLAG{MEDIUM_DOM_XSS_FILTER_BYPASS}',
        ],
    ];
}

function get_challenge_flag(string $slug, string $difficulty): ?string
{
    if (!in_array($slug, get_challenge_slugs(), true)
        || !in_array($difficulty, get_allowed_difficulties(), true)) {
        return null;
    }

    try {
        $statement = get_pdo()->prepare(
            'SELECT flag FROM challenge_progress WHERE slug = ? AND difficulty = ? LIMIT 1'
        );
        $statement->execute([$slug, $difficulty]);
        $flag = $statement->fetchColumn();

        if (is_string($flag) && $flag !== '') {
            return $flag;
        }
    } catch (Throwable $exception) {
        // 数据库不可用或未迁移时使用本地演示 fallback。
    }

    $fallbackFlags = get_fallback_flags();

    return $fallbackFlags[$difficulty][$slug] ?? null;
}

function get_lab_flag_file_path(string $slug, string $difficulty): string
{
    $flagFiles = [
        'command' => 'command.txt',
        'file_include' => 'include.txt',
        'upload' => 'upload.txt',
    ];

    if (!isset($flagFiles[$slug]) || !in_array($difficulty, get_allowed_difficulties(), true)) {
        throw new InvalidArgumentException('未知的本地 Flag 文件。');
    }

    return __DIR__ . DIRECTORY_SEPARATOR . 'flags'
        . DIRECTORY_SEPARATOR . $difficulty
        . DIRECTORY_SEPARATOR . $flagFiles[$slug];
}

function get_cookie_path(): string
{
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $cookiePath = rtrim($scriptDir, '/') . '/';

    return $cookiePath === './' ? '/' : $cookiePath;
}

function set_lab_flag_cookie(string $slug, string $difficulty): void
{
    $flag = get_challenge_flag($slug, $difficulty);
    if ($flag === null || headers_sent()) {
        return;
    }

    $cookieName = 'lab_' . $slug . '_' . $difficulty . '_flag';
    setrawcookie($cookieName, $flag, [
        'expires' => 0,
        'path' => get_cookie_path(),
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$cookieName] = $flag;
}

function clear_lab_flag_cookies(string $difficulty): void
{
    if (headers_sent() || !in_array($difficulty, get_allowed_difficulties(), true)) {
        return;
    }

    foreach (['stored_xss', 'reflected_xss', 'dom_xss'] as $slug) {
        $cookieName = 'lab_' . $slug . '_' . $difficulty . '_flag';
        setrawcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => get_cookie_path(),
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$cookieName]);
    }
}

function is_local_request(): bool
{
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return $remoteAddress === ''
        || $remoteAddress === '::1'
        || $remoteAddress === '127.0.0.1'
        || strpos($remoteAddress, '127.') === 0;
}

function clear_uploads_directory(string $difficulty): void
{
    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        throw new InvalidArgumentException('非法难度。');
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $difficulty;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadRoot = realpath($uploadDir);
    if ($uploadRoot === false) {
        return;
    }

    $normalizedRoot = rtrim(str_replace('\\', '/', $uploadRoot), '/');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        $normalizedPath = str_replace('\\', '/', $path);
        if (strpos($normalizedPath, $normalizedRoot . '/') !== 0) {
            continue;
        }

        $relativePath = substr($normalizedPath, strlen($normalizedRoot) + 1);
        if ($relativePath === 'README.txt') {
            continue;
        }

        if ($item->isDir() && !$item->isLink()) {
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

function get_csrf_email_session_key(string $difficulty): string
{
    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        throw new InvalidArgumentException('非法难度。');
    }

    return 'csrf_demo_email_' . $difficulty;
}

function reset_lab_state(string $difficulty): void
{
    if (!in_array($difficulty, get_allowed_difficulties(), true)) {
        throw new InvalidArgumentException('非法难度。');
    }

    ensure_session_started();
    $pdo = get_pdo();
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare("UPDATE challenge_progress SET status = 'incomplete' WHERE difficulty = ?");
        $statement->execute([$difficulty]);

        $statement = $pdo->prepare('DELETE FROM messages WHERE difficulty = ?');
        $statement->execute([$difficulty]);

        $statement = $pdo->prepare(
            'INSERT INTO messages (`difficulty`, `name`, `content`, `ip_address`) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$difficulty, '系统提示', '欢迎来到本地漏洞靶场。这里的留言板用于演示存储型 XSS。', '127.0.0.1']);
        $statement->execute([$difficulty, 'Alice', '第一条普通留言，用于对比恶意脚本留言的显示效果。', '127.0.0.1']);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    clear_uploads_directory($difficulty);
    clear_lab_flag_cookies($difficulty);
    $_SESSION[get_csrf_email_session_key($difficulty)] = 'student@vuln-lab.local';
}

function handle_lab_reset_request(): array
{
    $result = ['checked' => false, 'ok' => false, 'message' => ''];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'reset_lab') {
        return $result;
    }

    $result['checked'] = true;

    if (!verify_platform_csrf_token()) {
        $result['message'] = '请求校验失败，请刷新页面后重试。';

        return $result;
    }

    if (!is_local_request()) {
        $result['message'] = '重置靶场只允许在本地授权环境中执行。';

        return $result;
    }

    try {
        reset_lab_state(get_lab_difficulty());
    } catch (Throwable $exception) {
        $result['message'] = '重置失败，请检查数据库迁移和目录权限。';

        return $result;
    }

    $result['ok'] = true;
    $result['message'] = '当前难度已重置。';

    return $result;
}

function decode_shell_output(string $output): string
{
    if ($output === '' || PHP_OS_FAMILY !== 'Windows') {
        return $output;
    }

    foreach (['CP936', 'GBK', 'GB2312'] as $encoding) {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($output, 'UTF-8', $encoding);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $output);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }
    }

    return $output;
}

function handle_flag_submission(string $slug, string $difficulty): array
{
    $result = ['checked' => false, 'ok' => false, 'message' => ''];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'submit_flag') {
        return $result;
    }

    $result['checked'] = true;
    $submittedFlag = trim((string) ($_POST['submitted_flag'] ?? ''));
    $expectedFlag = get_challenge_flag($slug, $difficulty);

    if ($expectedFlag !== null && hash_equals($expectedFlag, $submittedFlag)) {
        try {
            $statement = get_pdo()->prepare(
                'UPDATE challenge_progress SET status = ? WHERE slug = ? AND difficulty = ?'
            );
            $statement->execute(['passed', $slug, $difficulty]);
        } catch (Throwable $exception) {
            // 数据库暂不可写时仍允许展示本次 Flag 校验结果。
        }

        $result['ok'] = true;
        $result['message'] = 'Flag 校验成功，本难度模块已通关。';

        return $result;
    }

    $result['message'] = 'Flag 不正确，请确认当前难度后继续实验。';

    return $result;
}

function get_difficulty_label(string $difficulty): string
{
    return $difficulty === 'medium' ? 'Medium' : 'Low';
}

function render_header(string $title = APP_NAME, string $active = 'home'): void
{
    $pageTitle = $title === APP_NAME ? APP_NAME : $title . ' - ' . APP_NAME;
    $challenges = get_challenges();
    $difficulty = get_lab_difficulty();
    $difficultyLabel = get_difficulty_label($difficulty);
    ?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php" aria-label="返回主控台">
            <span class="brand-mark">VL</span>
            <span>
                <strong><?= e(APP_NAME) ?></strong>
                <small><?= e(APP_SUBTITLE) ?></small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="主导航">
            <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-grid-1x2"></i><span>主控台</span>
            </a>
            <div class="nav-section">靶场练习中心</div>
            <?php foreach ($challenges as $challenge): ?>
                <a class="nav-link <?= $active === $challenge['slug'] ? 'active' : '' ?>" href="<?= e($challenge['file']) ?>">
                    <i class="bi bi-shield-lock"></i><span><?= e($challenge['title']) ?></span>
                </a>
            <?php endforeach; ?>
            <div class="nav-section">后续扩展</div>
            <span class="nav-link nav-link-disabled" title="后续使用 Python 编写自动化渗透测试工具">
                <i class="bi bi-terminal"></i><span>Python 自动化工具</span>
            </span>
        </nav>

        <div class="sidebar-footer"><span class="status-dot"></span><span>Local Lab Only</span></div>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <div>
                <span class="eyebrow">Graduation Project Dashboard</span>
                <h1><?= e($title) ?></h1>
            </div>
            <div class="topbar-actions">
                <span class="difficulty-badge difficulty-badge-<?= e($difficulty) ?>">
                    <i class="bi bi-sliders"></i>当前难度：<?= e($difficultyLabel) ?>
                </span>
                <span class="environment-pill"><i class="bi bi-pc-display"></i>本地授权环境</span>
            </div>
        </header>
<?php
}

function render_footer(): void
{
    ?>
        <footer class="page-footer">
            <span>仅用于本地教学、毕业设计演示和授权安全测试。</span>
            <span>PHP + MySQL + Bootstrap 5</span>
        </footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
