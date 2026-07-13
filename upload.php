<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('upload', $difficulty);
$state = require resolve_vulnerability_source('upload');

render_header('文件上传', 'upload');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>上传目录故意保留脚本解析风险，仅用于本地授权实验。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">File Upload</span><h2>个人头像修改</h2>
        <p>上传文件会保存到当前难度独立目录，切换难度不会看到另一目录的文件。</p>
        <form class="lab-form" method="post" action="upload.php" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="upload_avatar">
            <label for="avatar" class="upload-zone"><i class="bi bi-cloud-arrow-up"></i><span>点击选择文件上传</span><small>Medium 会进行不完整图片校验</small></label>
            <input id="avatar" name="avatar" class="form-control" type="file">
            <div class="form-text">实验目标：上传后分析如何读取 <?= e($state['target_hint']) ?>。</div>
            <button class="btn btn-lab mt-3" type="submit"><i class="bi bi-upload"></i><span>上传头像</span></button>
        </form>
    </article>
    <aside class="lab-card"><span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span><h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">move_uploaded_file(..., uploads/<?= e($difficulty) ?>/文件名)</div></aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Upload Result</span><h2>上传结果</h2></div></div>
    <?php if ($state['notice'] !== ''): ?><div class="alert alert-info"><?= e($state['notice']) ?></div><?php else: ?><p class="empty-hint">选择文件并提交后，这里会显示结果。</p><?php endif; ?>
    <?php if ($state['path'] !== ''): ?><div class="vuln-terminal"><strong>访问路径：</strong><a href="<?= e($state['path']) ?>" target="_blank" rel="noopener"><?= e($state['path']) ?></a></div><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>通过当前难度上传路径获得 Flag 后提交。</p></div>
    <form method="post" action="upload.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
