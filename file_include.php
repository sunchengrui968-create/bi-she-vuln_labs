<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('file_include', $difficulty);
$state = require resolve_vulnerability_source('file_include');
$defaultContent = '这里是默认教学片段。真实系统应使用模板白名单，不能让用户控制文件路径。';

render_header('文件包含', 'file_include');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>本页面会根据参数读取本地文件，仅用于本地授权路径穿越实验。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">File Inclusion</span><h2>帮助文档查看器</h2>
        <p>page 参数由当前难度实现处理后拼入 assets 路径。</p>
        <form class="lab-form" method="get" action="file_include.php">
            <label for="page" class="form-label">文档路径 page</label>
            <div class="input-group"><input id="page" name="page" class="form-control" value="<?= e($state['page']) ?>" placeholder="css/style.css"><button class="btn btn-lab" type="submit"><i class="bi bi-file-earmark-text"></i><span>读取文档</span></button></div>
            <div class="form-text">实验提示：<?= e($state['hint']) ?></div>
        </form>
    </article>
    <aside class="lab-card"><span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span><h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">file_get_contents("assets/" . page)</div></aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Include Result</span><h2>文件读取结果</h2></div></div>
    <?php if ($state['error'] !== ''): ?><div class="alert alert-danger"><?= e($state['error']) ?></div>
    <?php elseif ($state['output'] !== ''): ?>
        <?php if ($state['show_target_path']): ?><div class="vuln-terminal"><strong>当前读取路径：</strong><code><?= e($state['target_path']) ?></code></div><?php endif; ?>
        <pre class="command-output"><?= e($state['output']) ?></pre>
    <?php else: ?><p class="empty-hint"><?= e($defaultContent) ?></p><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>读取当前难度 include Flag 后提交。</p></div>
    <form method="post" action="file_include.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
