<?php
$ip = $_POST['ip'] ?? '127.0.0.1';
$command = '';
$output = '';
$blockedConnectors = ['&&', '&', ';'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'ping') {
    $filteredIp = str_replace($blockedConnectors, '', (string) $ip);
    $pingOption = PHP_OS_FAMILY === 'Windows' ? '-n 4' : '-c 4';
    $command = 'ping ' . $pingOption . ' ' . $filteredIp;
    $output = decode_shell_output((string) shell_exec($command . ' 2>&1'));
}

return [
    'ip' => $ip,
    'command' => $command,
    'output' => $output,
    'show_command' => false,
    'command_example' => '分析黑名单遗漏的 Shell 组合方式',
    'defense_note' => 'Medium 屏蔽 &、&& 和分号，但不同系统仍存在未覆盖的命令组合方式。',
];
