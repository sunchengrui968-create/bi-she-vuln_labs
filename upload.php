<?php
/**
 * 文件上传靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，只判断文件是否上传成功，不校验后缀、MIME 或文件内容。
 * 在真实系统中，这种写法可能导致脚本文件被上传并执行。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('upload');
$uploadNotice = '';
$uploadedPath = '';
$uploadTargetHint = '../flags/upload.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'upload_avatar') {
    if (!isset($_FILES['avatar'])) {
        $uploadNotice = '没有收到上传文件。';
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $uploadNotice = '文件上传失败，错误码：' . $_FILES['avatar']['error'];
    } else {
        $uploadDir = __DIR__ . '/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename((string) $_FILES['avatar']['name']);
        $targetPath = $uploadDir . $originalName;

        // 故意不检查文件后缀名和文件内容，直接保存上传文件，形成文件上传漏洞。
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $uploadedPath = 'uploads/' . $originalName;
            $uploadNotice = '文件上传成功，保存路径：' . $uploadedPath;
        } else {
            $uploadNotice = '文件移动失败，请检查 uploads 目录权限。';
        }
    }
}

render_header('文件上传', 'upload');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面故意不校验上传文件类型，仅用于本地授权文件上传漏洞实验。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">File Upload</span>
        <h2>个人头像修改</h2>
        <p>
            正常系统应限制头像格式并重命名保存。这里为了演示漏洞，只要 PHP 判断上传成功，
            就会把原始文件名直接保存到 uploads 目录。
        </p>

        <form class="lab-form" method="post" action="upload.php" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="upload_avatar">
            <label for="avatar" class="upload-zone">
                <i class="bi bi-cloud-arrow-up"></i>
                <span>点击选择文件上传</span>
            <small>靶场故意不限制后缀名</small>
        </label>
        <input id="avatar" name="avatar" class="form-control" type="file">
            <div class="form-text">进阶目标：上传脚本后尝试读取 <?= e($uploadTargetHint) ?>。</div>
            <button class="btn btn-lab mt-3" type="submit">
                <i class="bi bi-upload"></i>
                <span>上传头像</span>
            </button>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            如果服务器允许脚本后缀上传，并且上传目录可以解析脚本，
            攻击者可能通过访问上传路径触发脚本执行。
        </p>
        <div class="code-chip">move_uploaded_file(..., uploads/文件名)</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Upload Result</span>
            <h2>上传结果</h2>
        </div>
    </div>

    <?php if ($uploadNotice !== ''): ?>
        <div class="alert alert-info"><?= e($uploadNotice) ?></div>
    <?php else: ?>
        <p class="empty-hint">选择文件并提交后，这里会显示保存路径。</p>
    <?php endif; ?>

    <?php if ($uploadedPath !== ''): ?>
        <div class="vuln-terminal">
            <strong>访问路径：</strong>
            <a href="<?= e($uploadedPath) ?>" target="_blank" rel="noopener"><?= e($uploadedPath) ?></a>
        </div>
    <?php endif; ?>

</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你成功上传脚本后缀文件并获得保存路径后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="upload.php" class="flag-form">
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
