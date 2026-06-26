<?php
/**
 * 文件包含靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，故意把 page 参数拼接到本地路径中读取。
 * 当输入中包含 ../ 时，可能越过预期目录读取敏感文件。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('file_include');
$page = $_GET['page'] ?? '';
$includeOutput = '';
$includeError = '';
$targetPath = '';
$defaultContent = '这里是默认教学片段。真实系统中应使用白名单选择模板，不应让用户直接控制文件路径。';
$includeHint = '../flags/include.txt';

if ($page !== '') {
    $targetPath = __DIR__ . '/assets/' . $page;

    // 故意不做白名单校验，也不限制路径穿越，形成本地文件包含/任意文件读取漏洞。
    $content = @file_get_contents($targetPath);

    if ($content === false) {
        $includeError = '文件读取失败，请检查路径是否存在。';
    } else {
        $includeOutput = $content;
    }
}

render_header('文件包含', 'file_include');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面会根据参数读取本地文件，仅用于本地授权文件包含漏洞实验。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">File Inclusion</span>
        <h2>帮助文档查看器</h2>
        <p>
            正常系统会通过固定编号选择帮助文档。这里为了演示文件包含风险，
            后端会把 URL 中的 page 参数直接拼接到文件路径中读取。
        </p>

        <form class="lab-form" method="get" action="file_include.php">
            <label for="page" class="form-label">文档路径 page</label>
            <div class="input-group">
                <input id="page" name="page" class="form-control" value="<?= e((string) $page) ?>" placeholder="css/style.css">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>读取文档</span>
                </button>
            </div>
            <div class="form-text">教学提示：可尝试 <?= e($includeHint) ?> 观察目录穿越读取效果。</div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            文件路径由用户输入直接控制时，攻击者可以使用 ../ 跳出预期目录，
            读取配置、日志或 Flag 等敏感文件。
        </p>
        <div class="code-chip">file_get_contents("assets/" . $_GET['page'])</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Include Result</span>
            <h2>文件读取结果</h2>
        </div>
    </div>

    <?php if ($includeError !== ''): ?>
        <div class="alert alert-danger"><?= e($includeError) ?></div>
    <?php elseif ($includeOutput !== ''): ?>
        <div class="vuln-terminal">
            <strong>当前读取路径：</strong>
            <code><?= e($targetPath) ?></code>
        </div>
        <pre class="command-output"><?= e($includeOutput) ?></pre>
    <?php else: ?>
        <p class="empty-hint"><?= e($defaultContent) ?></p>
    <?php endif; ?>
</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你通过路径穿越读取到 <?= e($includeHint) ?> 后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="file_include.php" class="flag-form">
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
