<?php
$page = (string) ($_GET['page'] ?? '');
$includeOutput = '';
$includeError = '';
$targetPath = '';

if ($page !== '') {
    $targetPath = __DIR__ . '/../../assets/' . $page;
    $content = @file_get_contents($targetPath);
    if ($content === false) {
        $includeError = '文件读取失败，请检查路径是否存在。';
    } else {
        $includeOutput = $content;
    }
}

return [
    'page' => $page,
    'output' => $includeOutput,
    'error' => $includeError,
    'target_path' => $targetPath,
    'show_target_path' => true,
    'hint' => '../flags/low/include.txt',
    'defense_note' => 'Low 直接把 page 拼接到 assets 路径。',
];
