<?php
/**
 * DOM 型 XSS 靶场页面
 *
 * 漏洞点说明：
 * 本页面后端不直接输出攻击参数，漏洞发生在浏览器端 JavaScript。
 * 脚本会读取 location.hash / URL 参数，并使用 innerHTML 写入页面。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('dom_xss');
set_lab_flag_cookie('dom_xss');

render_header('DOM 型 XSS', 'dom_xss');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面会在浏览器端把 URL 数据写入 innerHTML，仅用于本地 DOM XSS 练习。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">DOM XSS</span>
        <h2>前端公告预览器</h2>
        <p>
            后端页面本身不会把 payload 输出进 HTML。页面加载后，前端脚本会读取 URL hash
            或 text 参数，并直接赋值给公告区域的 innerHTML。
        </p>

        <form class="lab-form" method="get" action="dom_xss.php">
            <label for="text" class="form-label">公告内容 text</label>
            <div class="input-group">
                <input id="text" name="text" class="form-control" placeholder="<strong>Hello DOM</strong>">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-window-sidebar"></i>
                    <span>预览公告</span>
                </button>
            </div>
            <div class="form-text">教学提示：也可以在 URL 后追加 #&lt;img src=x onerror=alert(document.cookie)&gt;。</div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            DOM 型 XSS 的危险点在前端数据流。即使后端没有反射 payload，
            只要脚本把不可信数据写入 innerHTML，浏览器仍可能解析并执行恶意片段。
        </p>
        <div class="code-chip">preview.innerHTML = location.hash</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">DOM Preview</span>
            <h2>公告预览结果</h2>
        </div>
    </div>

    <div id="domSource" class="vuln-terminal mb-3">
        <strong>当前前端输入源：</strong>
        <code>等待浏览器脚本读取 URL...</code>
    </div>
    <div id="domPreview" class="search-result">URL 中没有可预览的内容。</div>
</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你通过 DOM XSS 让浏览器解析 URL 中的 HTML/脚本后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="dom_xss.php" class="flag-form">
        <input type="hidden" name="form_action" value="submit_flag">
        <input name="submitted_flag" class="form-control" placeholder="FLAG{...}">
        <button class="btn btn-lab" type="submit">提交 Flag</button>
    </form>

    <?php if ($flagResult['checked']): ?>
        <div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0">
            <?= e($flagResult['message']) ?>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    const preview = document.getElementById('domPreview');
    const source = document.getElementById('domSource');
    const params = new URLSearchParams(window.location.search);
    const hashValue = window.location.hash ? decodeURIComponent(window.location.hash.slice(1)) : '';
    const queryValue = params.get('text') || '';
    const payload = hashValue || queryValue;
    const sourceLabel = hashValue ? 'location.hash' : 'URLSearchParams(text)';

    if (!payload) {
        return;
    }

    source.innerHTML = '<strong>当前前端输入源：</strong> <code>' + sourceLabel + '</code>';
    // 故意使用 innerHTML 写入 URL 数据，形成 DOM 型 XSS 漏洞。
    preview.innerHTML = payload;
})();
</script>

<?php render_footer(); ?>
