<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('csrf', $difficulty);
$state = require resolve_vulnerability_source('csrf');

render_header('CSRF', 'csrf');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>敏感操作故意不使用可靠同步 Token，仅用于本地 CSRF 实验。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Cross-Site Request Forgery</span><h2>账号邮箱修改</h2>
        <p>Low 和 Medium 使用独立 Session 邮箱；切换难度不会覆盖另一难度状态。</p>
        <form class="lab-form" method="post" action="csrf.php">
            <input type="hidden" name="form_action" value="change_email">
            <label for="email" class="form-label">当前账号邮箱</label>
            <div class="input-group"><input id="email" name="email" class="form-control" value="<?= e($state['email']) ?>"><button class="btn btn-lab" type="submit"><i class="bi bi-envelope-at"></i><span>保存邮箱</span></button></div>
        </form>
    </article>
    <aside class="lab-card"><span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span><h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">change_email without a reliable synchronizer token</div></aside>
</section>

<?php if ($state['notice'] !== ''): ?><div class="alert alert-info"><?= e($state['notice']) ?></div><?php endif; ?>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Attack Preview</span><h2>本地恶意表单演示</h2></div></div>
    <p>Low 可继续使用现有个人测试页。Medium 使用独立演示页观察弱 Referer 子串检查。</p>
    <?php if ($difficulty === 'low'): ?>
        <form class="lab-form" method="post" action="csrf.php"><input type="hidden" name="form_action" value="change_email"><input type="hidden" name="email" value="<?= e($state['attack_email']) ?>"><button class="btn btn-lab" type="submit"><i class="bi bi-send-exclamation"></i><span>模拟 Low 攻击提交</span></button></form>
    <?php else: ?>
        <a class="btn btn-lab" href="http://localhost.localhost/labs/tools/csrf_medium.html" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i><span>打开 Medium 本地演示页</span></a>
    <?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>完成当前难度的跨站邮箱修改后提交对应 Flag。</p></div>
    <form method="post" action="csrf.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
