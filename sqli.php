<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('sqli', $difficulty);
$state = require resolve_vulnerability_source('sqli');

render_header('SQL 注入', 'sqli');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>本页面故意保留 SQL 注入，仅用于本地授权实验。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">SQL Injection</span>
        <h2>用户查询</h2>
        <p>输入用户编号后，当前难度实现会处理并拼接 SQL。Medium 的过滤并不等于安全修复。</p>
        <form class="lab-form" method="get" action="sqli.php">
            <label for="id" class="form-label">查询编号 id</label>
            <div class="input-group">
                <input id="id" name="id" class="form-control" value="<?= e((string) $state['id']) ?>" placeholder="例如：1">
                <button class="btn btn-lab" type="submit"><i class="bi bi-search"></i><span>执行查询</span></button>
            </div>
        </form>
    </article>
    <aside class="lab-card">
        <span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span>
        <h3>当前防护</h3>
        <p><?= e($state['defense_note']) ?></p>
        <div class="code-chip">SELECT id, username, nickname FROM users WHERE id = [用户输入]</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Query Result</span><h2>查询结果回显</h2></div></div>
    <?php if ($state['db_error'] !== ''): ?>
        <div class="alert alert-danger mt-3"><strong>数据库错误：</strong><?= e($state['db_error']) ?></div>
    <?php elseif ($state['has_query'] && count($state['rows']) === 0): ?>
        <div class="alert alert-warning mt-3">没有查询到数据。</div>
    <?php elseif (count($state['rows']) > 0): ?>
        <?php foreach ($state['rows'] as $row): ?><div><code>id=<?= e((string) ($row['id'] ?? '')) ?></code></div><?php endforeach; ?>
    <?php else: ?>
        <p class="empty-hint">输入编号并执行查询后，这里会显示数据库返回内容。</p>
    <?php endif; ?>
    <?php if ($state['show_sql'] && $state['has_query']): ?><div class="vuln-terminal mt-3"><code><?= e($state['sql']) ?></code></div><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>提交与当前难度匹配的 Flag。</p></div>
    <form method="post" action="sqli.php" class="flag-form">
        <input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button>
    </form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
