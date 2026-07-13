<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('reflected_xss', $difficulty);
set_lab_flag_cookie('reflected_xss', $difficulty);
$state = require resolve_vulnerability_source('reflected_xss');

render_header('反射型 XSS', 'reflected_xss');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>本页面会以故意不安全的方式回显搜索关键词，仅用于本地 XSS 练习。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Reflected XSS</span><h2>网站全局搜索</h2>
        <p>搜索关键词会由当前难度过滤器处理后写回 HTML。</p>
        <form class="lab-form" method="get" action="reflected_xss.php">
            <label for="q" class="form-label">搜索关键词</label>
            <div class="input-group"><input id="q" name="q" class="form-control" value="<?= e($state['keyword']) ?>" placeholder="请输入关键词"><button class="btn btn-lab" type="submit"><i class="bi bi-search"></i><span>搜索</span></button></div>
        </form>
    </article>
    <aside class="lab-card"><span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span><h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">您搜索的关键词是：[q]</div></aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Search Echo</span><h2>搜索结果</h2></div></div>
    <?php if ($state['keyword'] !== ''): ?><div class="search-result">您搜索的关键词是：<?= $state['output'] ?></div><?php else: ?><p class="empty-hint">输入关键词后，这里会展示回显内容。</p><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>读取当前难度专属 XSS Cookie 中的 Flag 后提交。</p></div>
    <form method="post" action="reflected_xss.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
