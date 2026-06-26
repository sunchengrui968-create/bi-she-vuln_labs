<?php
/**
 * 存储型 XSS 靶场页面
 *
 * 漏洞点说明：
 * 本页面把留言内容原样存入数据库，并在读取时直接输出到 HTML。
 * 如果留言中包含脚本标签，后续访问者打开页面时会触发脚本执行。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('stored_xss');
$messageNotice = '';
set_lab_flag_cookie('stored_xss');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_message') {
    $name = $_POST['name'] ?? '';
    $content = $_POST['content'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        // 这里使用预处理只负责避免 SQL 语法被破坏；XSS 漏洞来自“原样存储 + 原样输出”。
        $statement = get_pdo()->prepare('INSERT INTO messages (`name`, `content`, `ip_address`) VALUES (?, ?, ?)');
        $statement->execute([$name, $content, $ipAddress]);
        $messageNotice = '留言已保存。页面下方会直接渲染历史留言内容。';
    } catch (Throwable $exception) {
        $messageNotice = '留言保存失败：' . $exception->getMessage();
    }
}

$messages = [];
$messageError = '';

try {
    $messages = get_pdo()
        ->query('SELECT id, name, content, ip_address, created_at FROM messages ORDER BY id DESC LIMIT 20')
        ->fetchAll();
} catch (Throwable $exception) {
    $messageError = $exception->getMessage();
}

render_header('存储型 XSS', 'stored_xss');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面会原样渲染留言内容，脚本可能在浏览器中执行，仅用于本地实验。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Stored XSS</span>
        <h2>留言板 / 评论区</h2>
        <p>
            留言提交后会保存到数据库。为了演示存储型 XSS，历史留言区域会直接输出数据库内容，
            不调用 htmlspecialchars 进行 HTML 转义。
        </p>

        <form class="lab-form" method="post" action="stored_xss.php">
            <input type="hidden" name="form_action" value="add_message">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="name" class="form-label">姓名</label>
                    <input id="name" name="name" class="form-control" placeholder="Alice">
                </div>
                <div class="col-md-8">
                    <label for="content" class="form-label">留言内容</label>
                    <textarea id="content" name="content" class="form-control" rows="3" placeholder="写下你的留言"></textarea>
                </div>
            </div>
            <button class="btn btn-lab mt-3" type="submit">
                <i class="bi bi-send"></i>
                <span>发布留言</span>
            </button>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            攻击脚本被写入数据库后，会长期存在。
            任何访问留言板的用户都可能触发这段脚本，因此影响范围比反射型 XSS 更持久。
        </p>
        <div class="code-chip">echo $message['content'];</div>
    </aside>
</section>

<?php if ($messageNotice !== ''): ?>
    <div class="alert alert-info"><?= e($messageNotice) ?></div>
<?php endif; ?>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Message History</span>
            <h2>历史留言</h2>
        </div>
    </div>

    <?php if ($messageError !== ''): ?>
        <div class="alert alert-danger">读取留言失败：<?= e($messageError) ?></div>
    <?php elseif (count($messages) === 0): ?>
        <p class="empty-hint">暂无留言。</p>
    <?php else: ?>
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
                <article class="message-item">
                    <div class="message-meta">
                        <strong><?= $message['name'] ?></strong>
                        <span><?= e((string) $message['created_at']) ?> · <?= e((string) $message['ip_address']) ?></span>
                    </div>
                    <div class="message-content"><?= $message['content'] ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你让留言内容作为 HTML/脚本被页面解析后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="stored_xss.php" class="flag-form">
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
