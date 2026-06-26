<?php
/**
 * 主页 Dashboard
 *
 * 本页面负责展示系统状态、漏洞模块入口和通关状态。
 * 本页面负责汇总展示各漏洞模块入口，点击后可进入对应练习页面。
 */

require_once __DIR__ . '/config.php';

$resetResult = handle_lab_reset_request();

if ($resetResult['checked']) {
    $resetStatus = $resetResult['ok'] ? 'ok' : 'error';
    $resetMessage = $resetResult['ok'] ? 'reset' : rawurlencode($resetResult['message']);

    header('Location: index.php?reset_status=' . $resetStatus . '&reset_message=' . $resetMessage);
    exit;
}

$dbStatus = get_db_status();
$challenges = get_challenges();
$progress = get_challenge_progress();

$totalChallenges = count($challenges);
$passedCount = 0;
foreach ($challenges as $challenge) {
    $slug = $challenge['slug'];
    if (($progress[$slug]['status'] ?? 'incomplete') === 'passed') {
        $passedCount++;
    }
}

$progressPercent = $totalChallenges > 0 ? (int) round(($passedCount / $totalChallenges) * 100) : 0;
$resetStatus = (string) ($_GET['reset_status'] ?? '');
$resetMessage = '';

if ($resetStatus === 'ok') {
    $resetMessage = '靶场已重置，所有模块恢复为未通关。';
} elseif ($resetStatus === 'error') {
    $resetMessage = rawurldecode((string) ($_GET['reset_message'] ?? '重置失败，请检查数据库连接和目录权限。'));
}

render_header('靶场练习中心', 'home');
?>

<section class="hero-panel">
    <div class="hero-copy">
        <span class="section-label">DVWA Inspired Local Lab</span>
        <h2>面向常见 Web 漏洞的本地练习平台</h2>
        <p>
            本平台采用 PHP + MySQL 实现，后续将在各模块中故意保留典型漏洞代码，
            用于毕业论文分析、课堂演示和本地授权渗透测试练习。
        </p>
    </div>
    <div class="hero-meter" aria-label="通关进度">
        <span class="meter-number"><?= e((string) $progressPercent) ?>%</span>
        <span class="meter-label">当前通关进度</span>
        <div class="progress slim-progress" role="progressbar" aria-valuenow="<?= e((string) $progressPercent) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= e((string) $progressPercent) ?>%"></div>
        </div>
    </div>
</section>

<section class="status-grid" aria-label="系统状态">
    <article class="status-card <?= $dbStatus['ok'] ? 'is-ok' : 'is-error' ?>">
        <div class="status-icon">
            <i class="bi <?= $dbStatus['ok'] ? 'bi-database-check' : 'bi-database-x' ?>"></i>
        </div>
        <div>
            <span class="card-kicker">系统状态</span>
            <h3><?= e($dbStatus['label']) ?></h3>
            <p><?= e($dbStatus['detail']) ?></p>
        </div>
    </article>

    <article class="status-card">
        <div class="status-icon">
            <i class="bi bi-hdd-network"></i>
        </div>
        <div>
            <span class="card-kicker">部署方式</span>
            <h3>phpStudy 本地运行</h3>
            <p>无需 Docker，复制到网站根目录后导入 SQL 即可开始实验。</p>
        </div>
    </article>

    <article class="status-card">
        <div class="status-icon">
            <i class="bi bi-flag"></i>
        </div>
        <div>
            <span class="card-kicker">Flag 机制</span>
            <h3><?= e((string) $passedCount) ?> / <?= e((string) $totalChallenges) ?> 已通关</h3>
            <p>每个漏洞模块独立设置 Flag，用于记录练习进度和论文截图。</p>
        </div>
    </article>
</section>

<section class="reset-panel" aria-label="重置靶场">
    <div class="reset-copy">
        <span class="section-label">Lab Reset</span>
        <h2>重置靶场</h2>
        <p>
            将通关进度、存储型 XSS 留言、上传文件和当前会话实验状态恢复到初始值。
            漏洞页面和 Flag 本身不会被修改，方便后续自动化工具从干净状态重新测试。
        </p>

        <?php if ($resetMessage !== ''): ?>
            <div class="alert <?= $resetStatus === 'ok' ? 'alert-success' : 'alert-danger' ?> mb-0">
                <?= e($resetMessage) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="reset-summary">
        <div class="reset-progress-card">
            <span class="card-kicker">当前通关进度</span>
            <strong><?= e((string) $passedCount) ?> / <?= e((string) $totalChallenges) ?></strong>
            <span>已通关模块</span>
        </div>

        <div class="reset-checklist" aria-label="重置内容">
            <span><i class="bi bi-database"></i>进度恢复为未通关</span>
            <span><i class="bi bi-chat-square-text"></i>留言板恢复默认数据</span>
            <span><i class="bi bi-folder-x"></i>清理上传目录测试文件</span>
            <span><i class="bi bi-cookie"></i>清除 XSS 实验 Cookie</span>
        </div>

        <form class="reset-actions" method="post" action="index.php" onsubmit="return confirm('确定要重置靶场吗？这会清空通关状态、测试留言和上传文件。');">
            <input type="hidden" name="form_action" value="reset_lab">
            <button class="btn btn-reset" type="submit">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>重置靶场</span>
            </button>
        </form>
    </div>
</section>

<section class="section-heading">
    <div>
        <span class="section-label">Challenge Modules</span>
        <h2>漏洞列表</h2>
    </div>
    <p>点击任一模块即可进入对应练习页面，所有实验仅面向本地授权环境。</p>
</section>

<section class="challenge-grid" aria-label="漏洞模块列表">
    <?php foreach ($challenges as $challenge): ?>
        <?php
        $slug = $challenge['slug'];
        $status = $progress[$slug]['status'] ?? 'incomplete';
        $isPassed = $status === 'passed';
        ?>
        <article class="challenge-card accent-<?= e($challenge['accent']) ?>">
            <div class="challenge-card-header">
                <span class="challenge-tag"><?= e($challenge['tag']) ?></span>
                <span class="challenge-status <?= $isPassed ? 'passed' : 'pending' ?>">
                    <?= $isPassed ? '已通关' : '未通关' ?>
                </span>
            </div>

            <h3><?= e($challenge['title']) ?></h3>
            <p><?= e($challenge['summary']) ?></p>

            <div class="principle-box">
                <span>漏洞原理</span>
                <p><?= e($challenge['principle']) ?></p>
            </div>

            <div class="challenge-meta">
                <span><i class="bi bi-bar-chart"></i><?= e($challenge['level']) ?></span>
                <span><i class="bi bi-filetype-php"></i><?= e($challenge['file']) ?></span>
            </div>

            <a class="btn btn-lab" href="<?= e($challenge['file']) ?>">
                <span>开始练习</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </article>
    <?php endforeach; ?>
</section>

<section class="notice-panel">
    <div>
        <h2>实现说明</h2>
        <p>
            当前版本已经补齐 Dashboard 与 8 个漏洞练习页面。页面代码会保留清晰中文注释，
            并在对应模块中刻意呈现不安全写法，便于论文分析漏洞成因和修复思路。
        </p>
    </div>
    <div class="notice-list">
        <span><i class="bi bi-check-circle"></i> SQL 初始化脚本</span>
        <span><i class="bi bi-check-circle"></i> 公共 PDO 配置</span>
        <span><i class="bi bi-check-circle"></i> 主控台 UI 框架</span>
        <span><i class="bi bi-check-circle"></i> 8 个靶场页面</span>
    </div>
</section>

<?php render_footer(); ?>
