<?php
/**
 * 命令注入靶场页面
 *
 * 漏洞点说明：
 * 本页面为了教学演示，故意把 ip 参数直接拼接进 shell_exec 命令。
 * 当输入中包含命令连接符时，系统可能会执行额外命令。
 */

require_once __DIR__ . '/config.php';

$flagResult = handle_flag_submission('command');
$ip = $_POST['ip'] ?? '127.0.0.1';
$command = '';
$output = '';
$flagFileHint = PHP_OS_FAMILY === 'Windows' ? 'flags\\command.txt' : 'flags/command.txt';
$commandExample = PHP_OS_FAMILY === 'Windows' ? '& whoami' : '&& whoami';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'ping') {
    $pingOption = PHP_OS_FAMILY === 'Windows' ? '-n 4' : '-c 4';
    $command = 'ping ' . $pingOption . ' ' . $ip;

    // 故意不做命令参数过滤，直接拼接用户输入，形成命令注入漏洞。
    $output = decode_shell_output((string) shell_exec($command . ' 2>&1'));
}

render_header('命令注入', 'command');
?>

<section class="lab-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <span>本页面会调用本机 ping 命令，仅允许在本地授权环境中测试。</span>
</section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Command Injection</span>
        <h2>网络 Ping 测试工具</h2>
        <p>
            输入 IP 或域名后，后端会直接拼接系统命令并执行。
            这类设计如果缺少参数校验，会造成额外命令被执行。
        </p>

        <form class="lab-form" method="post" action="command.php">
            <input type="hidden" name="form_action" value="ping">
            <label for="ip" class="form-label">目标 IP / 域名</label>
            <div class="input-group">
                <input id="ip" name="ip" class="form-control" value="<?= e((string) $ip) ?>" placeholder="127.0.0.1">
                <button class="btn btn-lab" type="submit">
                    <i class="bi bi-hdd-network"></i>
                    <span>开始 Ping</span>
                </button>
            </div>
            <div class="form-text">教学提示：可尝试 <?= e($commandExample) ?> 观察额外命令输出；进阶目标是读取 <?= e($flagFileHint) ?>。</div>
        </form>
    </article>

    <aside class="lab-card">
        <h3>漏洞原理</h3>
        <p>
            shell_exec 会把拼接后的字符串交给系统 Shell。
            如果用户输入中包含连接符，后续内容可能被当成新命令执行。
        </p>
        <div class="code-chip">shell_exec("ping " . $ip)</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading">
        <div>
            <span class="section-label">Command Output</span>
            <h2>命令执行结果</h2>
        </div>
    </div>

    <?php if ($command !== ''): ?>
        <div class="vuln-terminal">
            <strong>当前执行命令：</strong>
            <code><?= e($command) ?></code>
        </div>
        <pre class="command-output"><?= e($output !== '' ? $output : '命令没有返回内容。') ?></pre>
    <?php else: ?>
        <p class="empty-hint">提交 Ping 测试后，这里会显示系统命令输出。</p>
    <?php endif; ?>

</section>

<section class="flag-box">
    <div>
        <span class="section-label">Flag Challenge</span>
        <h2>通关提交</h2>
        <p>当你通过命令拼接看到额外命令输出后，提交本模块 Flag。</p>
    </div>

    <form method="post" action="command.php" class="flag-form">
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
