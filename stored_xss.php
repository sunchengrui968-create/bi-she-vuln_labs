<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('stored_xss', $difficulty);
set_lab_flag_cookie('stored_xss', $difficulty);
$state = require resolve_vulnerability_source('stored_xss');

render_header('存储型 XSS', 'stored_xss');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>留言内容会以故意不安全的方式渲染，仅用于本地 XSS 实验。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Stored XSS</span><h2>留言板 / 评论区</h2>
        <p>Low 与 Medium 使用独立留言数据和实验 Cookie。</p>
        <form class="lab-form" method="post" action="stored_xss.php">
            <input type="hidden" name="form_action" value="add_message">
            <div class="row g-3">
                <div class="col-md-4"><label for="name" class="form-label">姓名</label><input id="name" name="name" class="form-control" placeholder="Alice"></div>
                <div class="col-md-8"><label for="content" class="form-label">留言内容</label><textarea id="content" name="content" class="form-control" rows="3" placeholder="写下你的留言"></textarea></div>
            </div>
            <button class="btn btn-lab mt-3" type="submit"><i class="bi bi-send"></i><span>发布留言</span></button>
        </form>
    </article>
    <aside class="lab-card">
        <span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span>
        <h3>当前防护</h3><p><?= e($state['defense_note']) ?></p><div class="code-chip">echo $message['content'];</div>
    </aside>
</section>

<?php if ($state['notice'] !== ''): ?><div class="alert alert-info"><?= e($state['notice']) ?></div><?php endif; ?>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Message History</span><h2>当前难度留言</h2></div></div>
    <?php if ($state['error'] !== ''): ?><div class="alert alert-danger"><?= e($state['error']) ?></div>
    <?php elseif (count($state['messages']) === 0): ?><p class="empty-hint">暂无留言。</p>
    <?php else: ?><div class="message-list">
        <?php foreach ($state['messages'] as $message): ?><article class="message-item">
            <div class="message-meta"><strong><?php if ($state['escape_name']): ?><?= e((string) $message['name']) ?><?php else: ?><?= $message['name'] ?><?php endif; ?></strong><span><?= e((string) $message['created_at']) ?> · <?= e((string) $message['ip_address']) ?></span></div>
            <div class="message-content"><?= $message['content'] ?></div>
        </article><?php endforeach; ?>
    </div><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>读取当前难度专属 XSS Cookie 中的 Flag 后提交。</p></div>
    <form method="post" action="stored_xss.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
