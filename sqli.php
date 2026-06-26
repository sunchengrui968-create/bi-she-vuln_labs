<?php
session_start();
/**
 * SQL 注入靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，故意把 id 参数直接拼接到 SQL 语句中。
 * 攻击者可以通过联合查询、布尔条件或报错信息改变原始查询逻辑。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('sqli');
$id = $_GET['id'] ?? '1';
$sql = 'SELECT id, username AS first_name, nickname AS surname FROM users WHERE id = ' . $id;
$rows = [];
$dbError = '';
$hasQuery = array_key_exists('id', $_GET);

if ($hasQuery) {
    try {
        // 故意不使用预处理，直接执行拼接后的 SQL，形成 SQL 注入漏洞。
        $rows = get_pdo()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        // 为了演示报错注入风险，这里直接把数据库错误展示给用户。
        $dbError = $exception->getMessage();
    }
}

render_header('SQL 注入', 'sqli');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面仅用于本地授权靶场实验，故意保留 SQL 注入漏洞，请勿部署到公网。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">SQL Injection</span>
        <h2>用户查询</h2>
        <p>
            输入用户编号后，后端会把参数直接拼接进 SQL 查询语句。
            这种写法会让攻击者有机会改变 WHERE 条件或拼接 UNION 查询。
        </p>

        <form class="lab-form" method="get" action="sqli.php">
            <label for="id" class="form-label">查询编号 id</label>
            <div class="input-group">
                <input id="id" name="id" class="form-control" value="<?= e((string) $id) ?>" placeholder="例如：1">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-search"></i>
                    <span>执行查询</span>
                </button>
            </div>
            <div class="form-text">教学提示：页面只回显 id，后台仍会执行拼接后的 SQL 查询。</div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            用户输入没有经过类型校验，也没有使用参数化查询。
            当输入被拼入 SQL 后，数据库会把它当作 SQL 语法的一部分执行。
        </p>
        <div class="code-chip">SELECT id, username, nickname FROM users WHERE id = [用户输入]</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Query Result</span>
            <h2>查询结果回显</h2>
        </div>
    </div>

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-danger mt-3">
            <strong>数据库错误：</strong><?= e($dbError) ?>
        </div>
    <?php elseif ($hasQuery && count($rows) === 0): ?>
        <div class="alert alert-warning mt-3">没有查询到数据。</div>
    <?php elseif (count($rows) > 0): ?>
        <div class="mt-3">
            <?php foreach ($rows as $row): ?>
                <div><code>id=<?= e((string) ($row['id'] ?? '')) ?></code></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-hint">输入编号并执行查询后，这里会显示数据库返回内容。</p>
    <?php endif; ?>
</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>利用 SQL 注入观察非普通查询结果，发现 Flag 后在这里提交验证。</p>
    </div>

    <form method="post" action="sqli.php" class="flag-form">
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
