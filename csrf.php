<?php
session_start();
/**
 * CSRF 靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，修改邮箱操作不校验 CSRF Token。
 * 只要浏览器携带当前会话 Cookie，第三方页面也可能诱导用户发起同源 POST 请求。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('csrf');
$emailNotice = '';

if (!isset($_SESSION['csrf_demo_email'])) {
    $_SESSION['csrf_demo_email'] = 'student@vuln-lab.local';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_email') {
    $newEmail = trim((string) ($_POST['email'] ?? ''));

    if ($newEmail === '') {
        $emailNotice = '邮箱不能为空。';
    } else {
        // 故意不校验 CSRF Token，直接信任当前 POST 请求，形成 CSRF 漏洞。
        $_SESSION['csrf_demo_email'] = $newEmail;
        $emailNotice = '邮箱已修改为：' . $newEmail;
    }
}

$currentEmail = (string) $_SESSION['csrf_demo_email'];
$attackEmail = 'attacker-controlled@evil.test';

render_header('CSRF', 'csrf');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面的敏感操作故意不校验 CSRF Token，仅用于本地 CSRF 漏洞实验。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Cross-Site Request Forgery</span>
        <h2>账号邮箱修改</h2>
        <p>
            修改邮箱属于敏感操作。为了演示 CSRF，本页面只检查表单字段，
            不生成也不校验一次性 Token。
        </p>

        <form class="lab-form" method="post" action="csrf.php">
            <input type="hidden" name="form_action" value="change_email">
            <label for="email" class="form-label">当前账号邮箱</label>
            <div class="input-group">
                <input id="email" name="email" class="form-control" value="<?= e($currentEmail) ?>" placeholder="student@vuln-lab.local">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-envelope-at"></i>
                    <span>保存邮箱</span>
                </button>
            </div>
            <div class="form-text">教学提示：这个表单没有隐藏的 CSRF Token 字段。</div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            浏览器会自动携带目标站点 Cookie。若服务端只认 Cookie、不校验 Token，
            第三方页面就可能构造表单诱导用户执行敏感操作。
        </p>
        <div class="code-chip">if ($_POST['form_action'] === 'change_email') update_email();</div>
    </aside>
</section>

<?php if ($emailNotice !== ''): ?>
    <div class="alert alert-info"><?= e($emailNotice) ?></div>
<?php endif; ?>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Attack Preview</span>
            <h2>恶意表单演示</h2>
        </div>
    </div>

    <p>
        下面的表单模拟第三方页面伪造请求。真实攻击中，用户可能看不到这些字段，
        页面会自动提交到目标站点。
    </p>

    <div class="vuln-terminal">
        <code>&lt;form method="post" action="http://localhost/labs/csrf.php"&gt;</code><br>
        <code>&nbsp;&nbsp;&lt;input name="form_action" value="change_email"&gt;</code><br>
        <code>&nbsp;&nbsp;&lt;input name="email" value="<?= e($attackEmail) ?>"&gt;</code><br>
        <code>&lt;/form&gt;</code>
    </div>

    <form class="lab-form" method="post" action="csrf.php">
        <input type="hidden" name="form_action" value="change_email">
        <input type="hidden" name="email" value="<?= e($attackEmail) ?>">
        <button class="btn btn-lab" type="submit">
            <i class="bi bi-send-exclamation"></i>
            <span>模拟攻击提交</span>
        </button>
    </form>
</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你完成一次无 Token 的邮箱修改后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="csrf.php" class="flag-form">
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
