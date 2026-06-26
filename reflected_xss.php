<?php
/**
 * 反射型 XSS 靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，故意把 q 参数直接输出到 HTML 响应中。
 * 这会让恶意脚本随请求即时反射到浏览器页面。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('reflected_xss');
$keyword = $_GET['q'] ?? '';
set_lab_flag_cookie('reflected_xss');

render_header('反射型 XSS', 'reflected_xss');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面会直接回显搜索关键词，仅用于本地 XSS 练习。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Reflected XSS</span>
        <h2>网站全局搜索</h2>
        <p>
            搜索关键词会通过 URL 参数传给后端。为了演示反射型 XSS，
            本页面会把关键词直接写回 HTML，而不做转义处理。
        </p>

        <form class="lab-form" method="get" action="reflected_xss.php">
            <label for="q" class="form-label">搜索关键词</label>
            <div class="input-group">
                <input id="q" name="q" class="form-control" value="<?= e((string) $keyword) ?>" placeholder="请输入关键词">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-search"></i>
                    <span>搜索</span>
                </button>
            </div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            反射型 XSS 通常不写入数据库，而是把请求参数直接放入响应。
            当用户点击带恶意参数的链接时，脚本就可能在浏览器中执行。
        </p>
        <div class="code-chip">您搜索的关键词是：[q]</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Search Echo</span>
            <h2>搜索结果</h2>
        </div>
    </div>

    <?php if ($keyword !== ''): ?>
        <div class="search-result">
            您搜索的关键词是：<?= $keyword ?>
        </div>
    <?php else: ?>
        <p class="empty-hint">输入关键词后，这里会展示直接回显的搜索内容。</p>
    <?php endif; ?>

</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你让搜索关键词作为 HTML/脚本被页面解析后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="reflected_xss.php" class="flag-form">
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

<?php render_footer(); ?>
