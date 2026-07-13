<?php
require_once __DIR__ . '/config.php';

$difficultyResult = handle_difficulty_change_request();
if ($difficultyResult['checked']) {
    header('Location: index.php?difficulty_status=' . ($difficultyResult['ok'] ? 'ok' : 'error'));
    exit;
}

$resetResult = handle_lab_reset_request();
if ($resetResult['checked']) {
    header('Location: index.php?reset_status=' . ($resetResult['ok'] ? 'ok' : 'error'));
    exit;
}

$difficulty = get_lab_difficulty();
$difficultyLabel = get_difficulty_label($difficulty);
$dbStatus = get_db_status();
$challenges = get_challenges();
$progress = get_challenge_progress($difficulty);
$platformCsrfToken = get_platform_csrf_token();

$totalChallenges = count($challenges);
$passedCount = 0;
foreach ($challenges as $challenge) {
    if (($progress[$challenge['slug']]['status'] ?? 'incomplete') === 'passed') {
        $passedCount++;
    }
}

$progressPercent = $totalChallenges > 0 ? (int) round(($passedCount / $totalChallenges) * 100) : 0;
$difficultyStatus = (string) ($_GET['difficulty_status'] ?? '');
$difficultyMessage = $difficultyStatus === 'ok'
    ? '靶场难度已应用，并会在当前浏览器会话中全站生效。'
    : ($difficultyStatus === 'error' ? '难度切换失败或非法难度值已被拒绝，当前状态保持不变。' : '');
$resetStatus = (string) ($_GET['reset_status'] ?? '');
$resetMessage = $resetStatus === 'ok'
    ? '当前难度已重置，另一难度的数据未受影响。'
    : ($resetStatus === 'error' ? '重置失败，请检查数据库迁移和目录权限。' : '');

render_header('靶场练习中心', 'home');
?>

<section class="hero-panel">
    <div class="hero-copy">
        <span class="section-label">DVWA Inspired Local Lab</span>
        <h2>面向常见 Web 漏洞的本地练习平台</h2>
        <p>PHP + MySQL 本地靶场会故意保留典型漏洞代码，仅用于毕业论文、课堂演示和授权测试。</p>
    </div>
    <div class="hero-meter" aria-label="当前难度通关进度">
        <span class="meter-number"><?= e((string) $progressPercent) ?>%</span>
        <span class="meter-label"><?= e($difficultyLabel) ?> 难度通关进度</span>
        <div class="progress slim-progress" role="progressbar" aria-valuenow="<?= e((string) $progressPercent) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= e((string) $progressPercent) ?>%"></div>
        </div>
    </div>
</section>

<section class="status-grid" aria-label="系统状态">
    <article class="status-card <?= $dbStatus['ok'] ? 'is-ok' : 'is-error' ?>">
        <div class="status-icon"><i class="bi <?= $dbStatus['ok'] ? 'bi-database-check' : 'bi-database-x' ?>"></i></div>
        <div><span class="card-kicker">系统状态</span><h3><?= e($dbStatus['label']) ?></h3><p><?= e($dbStatus['detail']) ?></p></div>
    </article>
    <article class="status-card">
        <div class="status-icon"><i class="bi bi-hdd-network"></i></div>
        <div><span class="card-kicker">部署方式</span><h3>phpStudy 本地运行</h3><p>无需 Docker 或构建工具，导入 SQL 后即可实验。</p></div>
    </article>
    <article class="status-card">
        <div class="status-icon"><i class="bi bi-flag"></i></div>
        <div><span class="card-kicker">当前难度进度</span><h3><?= e((string) $passedCount) ?> / <?= e((string) $totalChallenges) ?> 已通关</h3><p>Low 与 Medium 的进度和 Flag 相互隔离。</p></div>
    </article>
</section>

<section class="difficulty-panel" aria-label="靶场难度 Security Level">
    <div class="difficulty-copy">
        <span class="section-label">Security Level</span>
        <h2>靶场难度</h2>
        <p>当前难度：<strong class="current-difficulty"><?= e($difficultyLabel) ?></strong>。切换只改变会话选择，不会清空任何实验数据。</p>
        <?php if ($difficultyMessage !== ''): ?>
            <div class="alert <?= $difficultyStatus === 'ok' ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($difficultyMessage) ?></div>
        <?php endif; ?>
    </div>

    <form class="difficulty-form" method="post" action="index.php">
        <input type="hidden" name="form_action" value="change_difficulty">
        <input type="hidden" name="platform_csrf_token" value="<?= e($platformCsrfToken) ?>">
        <div class="difficulty-options">
            <label class="difficulty-choice <?= $difficulty === 'low' ? 'is-selected' : '' ?>">
                <input type="radio" name="difficulty" value="low" <?= $difficulty === 'low' ? 'checked' : '' ?>>
                <span><strong>Low</strong><small>几乎没有防护，适合理解漏洞基础原理。</small></span>
            </label>
            <label class="difficulty-choice <?= $difficulty === 'medium' ? 'is-selected' : '' ?>">
                <input type="radio" name="difficulty" value="medium" <?= $difficulty === 'medium' ? 'checked' : '' ?>>
                <span><strong>Medium</strong><small>加入不完整防护，需要分析过滤逻辑并绕过。</small></span>
            </label>
        </div>
        <button class="btn btn-apply-difficulty" type="submit"><i class="bi bi-check2-circle"></i><span>应用难度</span></button>
    </form>
</section>

<section class="reset-panel reset-current-difficulty" aria-label="只重置当前难度">
    <div class="reset-copy">
        <span class="section-label">Lab Reset</span><h2>重置当前难度：<?= e($difficultyLabel) ?></h2>
        <p>只重置当前难度的通关进度、留言、上传文件、XSS Cookie 和 CSRF 邮箱；另一难度完全保留。</p>
        <?php if ($resetMessage !== ''): ?><div class="alert <?= $resetStatus === 'ok' ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($resetMessage) ?></div><?php endif; ?>
    </div>
    <div class="reset-summary">
        <div class="reset-progress-card"><span class="card-kicker"><?= e($difficultyLabel) ?> 当前进度</span><strong><?= e((string) $passedCount) ?> / <?= e((string) $totalChallenges) ?></strong><span>已通关模块</span></div>
        <div class="reset-checklist"><span><i class="bi bi-database"></i>仅重置当前进度</span><span><i class="bi bi-chat-square-text"></i>仅恢复当前留言</span><span><i class="bi bi-folder-x"></i>仅清理当前上传目录</span><span><i class="bi bi-cookie"></i>仅清除当前实验 Cookie</span></div>
        <form class="reset-actions" method="post" action="index.php" onsubmit="return confirm('确定只重置当前难度吗？');">
            <input type="hidden" name="form_action" value="reset_lab"><input type="hidden" name="platform_csrf_token" value="<?= e($platformCsrfToken) ?>">
            <button class="btn btn-reset" type="submit"><i class="bi bi-arrow-counterclockwise"></i><span>重置当前难度</span></button>
        </form>
    </div>
</section>

<section class="section-heading"><div><span class="section-label">Challenge Modules</span><h2>漏洞列表</h2></div><p>所有入口 URL 保持不变，并自动加载当前难度实现。</p></section>

<section class="challenge-grid" aria-label="漏洞模块列表">
    <?php foreach ($challenges as $challenge): ?>
        <?php $status = $progress[$challenge['slug']]['status'] ?? 'incomplete'; $isPassed = $status === 'passed'; ?>
        <article class="challenge-card accent-<?= e($challenge['accent']) ?>">
            <div class="challenge-card-header"><span class="challenge-tag"><?= e($challenge['tag']) ?></span><span class="challenge-status <?= $isPassed ? 'passed' : 'pending' ?>"><?= $isPassed ? '已通关' : '未通关' ?></span></div>
            <h3><?= e($challenge['title']) ?></h3><p><?= e($challenge['summary']) ?></p>
            <div class="principle-box"><span>漏洞原理</span><p><?= e($challenge['principle']) ?></p></div>
            <div class="challenge-meta"><span><i class="bi bi-bar-chart"></i>模块复杂度：<?= e($challenge['level']) ?></span><span class="current-difficulty"><i class="bi bi-sliders"></i>当前难度：<?= e($difficultyLabel) ?></span></div>
            <a class="btn btn-lab" href="<?= e($challenge['file']) ?>"><span>开始练习</span><i class="bi bi-arrow-right"></i></a>
        </article>
    <?php endforeach; ?>
</section>

<section class="notice-panel">
    <div><h2>本地授权边界</h2><p>本项目包含故意不安全的命令执行、文件上传与 XSS 代码，不适合公网或生产部署。</p></div>
    <div class="notice-list"><span><i class="bi bi-check-circle"></i>同一入口按难度加载</span><span><i class="bi bi-check-circle"></i>双难度数据隔离</span><span><i class="bi bi-check-circle"></i>公共解析器严格白名单</span><span><i class="bi bi-check-circle"></i>仅限 localhost 实验</span></div>
</section>

<?php render_footer(); ?>
