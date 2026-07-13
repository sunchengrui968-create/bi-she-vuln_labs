<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('dom_xss', $difficulty);
set_lab_flag_cookie('dom_xss', $difficulty);
$state = require resolve_vulnerability_source('dom_xss');

render_header('DOM 型 XSS', 'dom_xss');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>URL 数据最终会进入 innerHTML，仅用于本地 DOM XSS 练习。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">DOM XSS</span><h2>前端公告预览器</h2>
        <p>后端不输出攻击参数，浏览器脚本读取 location.hash 或 text 参数。</p>
        <form class="lab-form" method="get" action="dom_xss.php">
            <label for="text" class="form-label">公告内容 text</label>
            <div class="input-group"><input id="text" name="text" class="form-control" placeholder="HTML 公告内容"><button class="btn btn-lab" type="submit"><i class="bi bi-window-sidebar"></i><span>预览公告</span></button></div>
        </form>
    </article>
    <aside class="lab-card"><span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span><h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">preview.innerHTML = payload</div></aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">DOM Preview</span><h2>公告预览结果</h2></div></div>
    <div id="domSource" class="vuln-terminal mb-3"><strong>当前前端输入源：</strong><code>等待浏览器读取 URL...</code></div>
    <div id="domPreview" class="search-result">URL 中没有可预览的内容。</div>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>读取当前难度专属 DOM XSS Cookie 中的 Flag 后提交。</p></div>
    <form method="post" action="dom_xss.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<script>
(function () {
    const preview = document.getElementById('domPreview');
    const source = document.getElementById('domSource');
    const params = new URLSearchParams(window.location.search);
    const hashValue = window.location.hash ? decodeURIComponent(window.location.hash.slice(1)) : '';
    const queryValue = params.get('text') || '';
    let payload = hashValue || queryValue;
    const sourceLabel = hashValue ? 'location.hash' : 'URLSearchParams(text)';
    if (!payload) { return; }
    source.textContent = '当前前端输入源：' + sourceLabel;
    <?= $state['script'] ?>
})();
</script>

<?php render_footer(); ?>
