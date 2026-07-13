<?php
$ip = $_POST['ip'] ?? '127.0.0.1';
$command = '';
$output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'ping') {
    $pingOption = PHP_OS_FAMILY === 'Windows' ? '-n 4' : '-c 4';
    $command = 'ping ' . $pingOption . ' ' . $ip;
    $output = decode_shell_output((string) shell_exec($command . ' 2>&1'));
}

return [
    'ip' => $ip,
    'command' => $command,
    'output' => $output,
    'show_command' => true,
    'command_example' => PHP_OS_FAMILY === 'Windows' ? '& whoami' : '&& whoami',
    'defense_note' => 'Low 会把输入直接拼接给系统 Shell。',
];
