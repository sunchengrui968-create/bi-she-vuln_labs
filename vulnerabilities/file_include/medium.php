<?php
$page = (string) ($_GET['page'] ?? '');
$includeOutput = '';
$safeIncludeError = '';
$targetPath = '';

if ($page !== '') {
    $cleanPage = str_replace('../', '', $page);
    $targetPath = __DIR__ . '/../../assets/' . $cleanPage;
    $content = @file_get_contents($targetPath);
    if ($content === false) {
        $safeIncludeError = '文件读取失败，路径已被 Medium 过滤器处理。';
    } else {
        $includeOutput = $content;
    }
}

return [
    'page' => $page,
    'output' => $includeOutput,
    'error' => $safeIncludeError,
    'target_path' => '',
    'show_target_path' => false,
    'hint' => '分析一次性路径清理对嵌套目录片段的影响',
    'defense_note' => 'Medium 只执行一次 ../ 字符串删除，并隐藏服务器绝对路径与 PHP Warning。',
];
