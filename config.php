<?php
/**
 * 毕业设计项目：基于常见漏洞的靶场平台与自动化渗透工具的设计与实现
 * 文件用途：公共配置、数据库连接、统一页面头部和尾部。
 *
 * 注意：
 * 1. config.php 本身尽量保持规范和可维护，方便论文说明系统架构。
 * 2. 后续具体漏洞页面会故意写出不安全代码，用于本地授权靶场实验。
 * 3. 这些漏洞页面不要部署到公网或生产服务器。
 */

declare(strict_types=1);

// ------------------------------------------------------------
// 数据库连接配置
// phpStudy 常见默认账号为 root/root，如果你的环境不同，只需要修改这里。
// ------------------------------------------------------------
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'vuln_db';
const DB_USER = 'root';
const DB_PASS = 'root';
const DB_CHARSET = 'utf8mb4';

// 项目名称集中定义，便于后续页面和论文截图保持一致。
const APP_NAME = '基于常见漏洞的靶场平台';
const APP_SUBTITLE = '毕业设计本地授权实验环境';

/**
 * 获取 PDO 数据库连接。
 *
 * 这里使用 PDO 是为了让公共框架更规范。后续 SQL 注入页面会刻意
 * 使用字符串拼接查询，形成可复现的漏洞点。
 */
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

/**
 * 页面安全输出函数。
 *
 * Dashboard 属于正常业务页面，所以这里使用 htmlspecialchars 防止误输出。
 * 后续 XSS 靶场页面会故意不使用该函数，以便展示漏洞成因。
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 检查数据库状态，供主页状态卡片展示。
 */
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
            'detail' => $exception->getMessage(),
        ];
    }
}

/**
 * 漏洞模块元信息。
 *
 * 这些数据既用于主页展示，也为后续逐个创建漏洞页面提供统一入口。
 */
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
            'principle' => '输入原样入库，输出时不做 HTML 转义，脚本会在其他访问者浏览器执行。',
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
            'principle' => '后端只判断上传是否成功，不校验扩展名、MIME 和文件内容。',
        ],
        [
            'slug' => 'file_include',
            'title' => '文件包含',
            'file' => 'file_include.php',
            'tag' => 'LFI',
            'level' => '中等',
            'accent' => 'cyan',
            'summary' => '通过帮助文档读取功能，观察用户可控路径拼接造成的本地文件读取风险。',
            'principle' => '后端把 page 参数直接拼接到文件路径中，攻击者可使用目录穿越读取敏感文件。',
        ],
        [
            'slug' => 'csrf',
            'title' => 'CSRF',
            'file' => 'csrf.php',
            'tag' => 'CSRF',
            'level' => '基础',
            'accent' => 'green',
            'summary' => '通过修改邮箱操作，观察缺少 CSRF Token 校验时第三方页面伪造请求的风险。',
            'principle' => '浏览器会自动携带会话 Cookie，如果服务端不校验 Token，跨站请求也可能被当作合法操作。',
        ],
        [
            'slug' => 'dom_xss',
            'title' => 'DOM 型 XSS',
            'file' => 'dom_xss.php',
            'tag' => 'DOM XSS',
            'level' => '基础',
            'accent' => 'violet',
            'summary' => '通过前端公告预览器，观察 location 数据被写入 innerHTML 后触发的浏览器端脚本注入。',
            'principle' => '漏洞发生在浏览器端数据流中，不可信 URL 数据进入 innerHTML 后会被当作页面代码解析。',
        ],
    ];
}

/**
 * 从数据库读取通关状态。
 *
 * 如果数据库尚未导入 init.sql，页面仍然可以展示静态入口，
 * 同时在状态卡片中提示数据库连接问题。
 */
function get_challenge_progress(): array
{
    try {
        $rows = get_pdo()
            ->query('SELECT slug, status, flag, updated_at FROM challenge_progress ORDER BY sort_order ASC')
            ->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }

    $progress = [];
    foreach ($rows as $row) {
        $progress[$row['slug']] = $row;
    }

    return $progress;
}

/**
 * 读取指定漏洞模块的 Flag。
 *
 * 正常情况下从数据库 challenge_progress 表读取；如果数据库尚未导入，
 * 则使用本地 fallback，避免页面因为缺少数据库而完全不可展示。
 */
function get_challenge_flag(string $slug): ?string
{
    try {
        $statement = get_pdo()->prepare('SELECT flag FROM challenge_progress WHERE slug = ? LIMIT 1');
        $statement->execute([$slug]);
        $flag = $statement->fetchColumn();

        if (is_string($flag) && $flag !== '') {
            return $flag;
        }
    } catch (Throwable $exception) {
        // 数据库不可用时走下面的本地 fallback。
    }

    $fallbackFlags = [
        'sqli' => 'FLAG{SQLI_UNION_QUERY_SUCCESS}',
        'command' => 'FLAG{COMMAND_INJECTION_LOCAL_EXEC}',
        'stored_xss' => 'FLAG{STORED_XSS_MESSAGE_BOARD}',
        'reflected_xss' => 'FLAG{REFLECTED_XSS_SEARCH_ECHO}',
        'upload' => 'FLAG{FILE_UPLOAD_WEBSHELL_LAB}',
        'file_include' => 'FLAG{FILE_INCLUDE_PATH_TRAVERSAL}',
        'csrf' => 'FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}',
        'dom_xss' => 'FLAG{DOM_XSS_LOCATION_INNERHTML}',
    ];

    return $fallbackFlags[$slug] ?? null;
}

/**
 * 获取命令注入和文件上传模块使用的本地 Flag 文件路径。
 *
 * 文件放在 flags 目录，并配合 .htaccess 禁止浏览器直接访问。
 * 练习者需要通过漏洞执行命令或上传脚本读取文件内容。
 */
function get_lab_flag_file_path(string $slug): string
{
    $flagFiles = [
        'command' => __DIR__ . DIRECTORY_SEPARATOR . 'flags' . DIRECTORY_SEPARATOR . 'command.txt',
        'file_include' => __DIR__ . DIRECTORY_SEPARATOR . 'flags' . DIRECTORY_SEPARATOR . 'include.txt',
        'upload' => __DIR__ . DIRECTORY_SEPARATOR . 'flags' . DIRECTORY_SEPARATOR . 'upload.txt',
    ];

    if (!isset($flagFiles[$slug])) {
        throw new InvalidArgumentException('未知的本地 Flag 文件模块：' . $slug);
    }

    return $flagFiles[$slug];
}

/**
 * 为 XSS 模块设置可由 JavaScript 读取的实验 Cookie。
 *
 * 这里故意不设置 HttpOnly，用来演示 XSS 读取浏览器端敏感信息的风险。
 */
function set_lab_flag_cookie(string $slug): void
{
    $flag = get_challenge_flag($slug);

    if ($flag === null || headers_sent()) {
        return;
    }

    $cookieName = 'lab_' . $slug . '_flag';
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $cookiePath = rtrim($scriptDir, '/') . '/';

    if ($cookiePath === './') {
        $cookiePath = '/';
    }

    setrawcookie($cookieName, $flag, [
        'expires' => 0,
        'path' => $cookiePath,
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[$cookieName] = $flag;
}

/**
 * 判断当前请求是否来自本机。
 *
 * 重置靶场会清空实验数据，因此只允许本地授权环境触发。
 */
function is_local_request(): bool
{
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return $remoteAddress === ''
        || $remoteAddress === '::1'
        || $remoteAddress === '127.0.0.1'
        || strpos($remoteAddress, '127.') === 0;
}

/**
 * 清除 XSS 练习使用的实验 Cookie。
 */
function clear_lab_flag_cookies(): void
{
    if (headers_sent()) {
        return;
    }

    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $cookiePath = rtrim($scriptDir, '/') . '/';

    if ($cookiePath === './') {
        $cookiePath = '/';
    }

    foreach (['lab_stored_xss_flag', 'lab_reflected_xss_flag', 'lab_dom_xss_flag'] as $cookieName) {
        setrawcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => $cookiePath,
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$cookieName]);
    }
}

/**
 * 清理上传目录，仅保留说明文件。
 */
function clear_uploads_directory(): void
{
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

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

/**
 * 将靶场恢复为初始练习状态。
 */
function reset_lab_state(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $pdo = get_pdo();
    $pdo->beginTransaction();

    try {
        $pdo->exec("UPDATE challenge_progress SET status = 'incomplete'");
        $pdo->exec('DELETE FROM messages');

        $statement = $pdo->prepare('INSERT INTO messages (`name`, `content`, `ip_address`) VALUES (?, ?, ?)');
        $statement->execute(['系统提示', '欢迎来到本地漏洞靶场。这里的留言板用于演示存储型 XSS。', '127.0.0.1']);
        $statement->execute(['Alice', '第一条普通留言，用于对比恶意脚本留言的显示效果。', '127.0.0.1']);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    clear_uploads_directory();
    clear_lab_flag_cookies();
    $_SESSION['csrf_demo_email'] = 'student@vuln-lab.local';
}

/**
 * 处理首页重置请求。
 */
function handle_lab_reset_request(): array
{
    $result = [
        'checked' => false,
        'ok' => false,
        'message' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'reset_lab') {
        return $result;
    }

    $result['checked'] = true;

    if (!is_local_request()) {
        $result['message'] = '重置靶场只允许在本地授权环境中执行。';

        return $result;
    }

    try {
        reset_lab_state();
    } catch (Throwable $exception) {
        $result['message'] = '重置失败：' . $exception->getMessage();

        return $result;
    }

    $result['ok'] = true;
    $result['message'] = '靶场已重置，所有模块恢复为未通关。';

    return $result;
}

/**
 * Windows 的 ping 输出通常是系统本地代码页，页面按 UTF-8 输出会乱码。
 */
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

/**
 * 处理通关 Flag 提交。
 *
 * 这个函数本身使用安全写法，因为它属于平台通用能力；
 * 真正的漏洞点只保留在对应靶场页面的练习逻辑里。
 */
function handle_flag_submission(string $slug): array
{
    $result = [
        'checked' => false,
        'ok' => false,
        'message' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'submit_flag') {
        return $result;
    }

    $result['checked'] = true;
    $submittedFlag = trim((string) ($_POST['submitted_flag'] ?? ''));
    $expectedFlag = get_challenge_flag($slug);

    if ($expectedFlag !== null && hash_equals($expectedFlag, $submittedFlag)) {
        try {
            $statement = get_pdo()->prepare('UPDATE challenge_progress SET status = ? WHERE slug = ?');
            $statement->execute(['passed', $slug]);
        } catch (Throwable $exception) {
            // 即使数据库暂不可写，也允许页面提示 Flag 校验通过。
        }

        $result['ok'] = true;
        $result['message'] = 'Flag 校验成功，本模块已通关。';

        return $result;
    }

    $result['message'] = 'Flag 不正确，请继续利用漏洞寻找正确结果。';

    return $result;
}

/**
 * 渲染统一页面头部。
 *
 * 参数 $active 用来标记当前导航项，例如 home、sqli、command。
 */
function render_header(string $title = APP_NAME, string $active = 'home'): void
{
    $pageTitle = $title === APP_NAME ? APP_NAME : $title . ' - ' . APP_NAME;
    $challenges = get_challenges();
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
                <i class="bi bi-grid-1x2"></i>
                <span>主控台</span>
            </a>

            <div class="nav-section">靶场练习中心</div>
            <?php foreach ($challenges as $challenge): ?>
                <a class="nav-link <?= $active === $challenge['slug'] ? 'active' : '' ?>" href="<?= e($challenge['file']) ?>">
                    <i class="bi bi-shield-lock"></i>
                    <span><?= e($challenge['title']) ?></span>
                </a>
            <?php endforeach; ?>

            <div class="nav-section">后续扩展</div>
            <span class="nav-link nav-link-disabled" title="后续使用 Python 编写自动化渗透测试工具">
                <i class="bi bi-terminal"></i>
                <span>Python 自动化工具</span>
            </span>
        </nav>

        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Local Lab Only</span>
        </div>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <div>
                <span class="eyebrow">Graduation Project Dashboard</span>
                <h1><?= e($title) ?></h1>
            </div>
            <div class="topbar-actions">
                <span class="environment-pill">
                    <i class="bi bi-pc-display"></i>
                    本地授权环境
                </span>
            </div>
        </header>
<?php
}

/**
 * 渲染统一页面尾部。
 */
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
