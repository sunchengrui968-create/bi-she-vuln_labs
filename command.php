<?php
require_once __DIR__ . '/config.php';

$difficulty = get_lab_difficulty();
$flagResult = handle_flag_submission('command', $difficulty);
$state = require resolve_vulnerability_source('command');
$flagFileHint = PHP_OS_FAMILY === 'Windows'
    ? 'flags\\' . $difficulty . '\\command.txt'
    : 'flags/' . $difficulty . '/command.txt';

render_header('命令注入', 'command');
?>

<section class="lab-warning"><i class="bi bi-exclamation-triangle"></i><span>本页面会调用本机 ping 命令，仅允许在本地授权环境中测试。</span></section>

<section class="lab-layout">
    <article class="lab-panel">
        <span class="section-label">Command Injection</span><h2>网络 Ping 测试工具</h2>
        <p>输入 IP 或域名后，后端会按照当前难度过滤并拼接系统命令。</p>
        <form class="lab-form" method="post" action="command.php">
            <input type="hidden" name="form_action" value="ping">
            <label for="ip" class="form-label">目标 IP / 域名</label>
            <div class="input-group">
                <input id="ip" name="ip" class="form-control" value="<?= e((string) $state['ip']) ?>" placeholder="127.0.0.1">
                <button class="btn btn-lab" type="submit"><i class="bi bi-hdd-network"></i><span>开始 Ping</span></button>
            </div>
            <div class="form-text">实验目标：分析命令组合方式并读取 <?= e($flagFileHint) ?>。Low 提示：<?= e($state['command_example']) ?></div>
        </form>
    </article>
    <aside class="lab-card">
        <span class="current-difficulty">当前难度：<?= e(get_difficulty_label($difficulty)) ?></span>
        <h3>当前防护</h3><p><?= e($state['defense_note']) ?></p>
        <div class="code-chip">shell_exec("ping " . $ip)</div>
    </aside>
</section>

<section class="lab-panel">
    <div class="panel-heading"><div><span class="section-label">Command Output</span><h2>命令执行结果</h2></div></div>
    <?php if ($state['command'] !== ''): ?>
        <?php if ($state['show_command']): ?><div class="vuln-terminal"><strong>当前执行命令：</strong><code><?= e($state['command']) ?></code></div><?php endif; ?>
        <pre class="command-output"><?= e($state['output'] !== '' ? $state['output'] : '命令没有返回内容。') ?></pre>
    <?php else: ?><p class="empty-hint">提交 Ping 测试后，这里会显示系统命令输出。</p><?php endif; ?>
</section>

<section class="flag-box">
    <div><span class="section-label">Flag Challenge</span><h2>通关提交</h2><p>读取当前难度的命令注入 Flag 后提交。</p></div>
    <form method="post" action="command.php" class="flag-form"><input type="hidden" name="form_action" value="submit_flag"><input name="submitted_flag" class="form-control" placeholder="FLAG{...}"><button class="btn btn-lab" type="submit">提交 Flag</button></form>
    <?php if ($flagResult['checked']): ?><div class="alert <?= $flagResult['ok'] ? 'alert-success' : 'alert-danger' ?> mb-0"><?= e($flagResult['message']) ?></div><?php endif; ?>
</section>

<?php render_footer(); ?>
