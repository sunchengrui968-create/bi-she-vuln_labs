<?php
$keyword = (string) ($_GET['q'] ?? '');

return [
    'keyword' => $keyword,
    'output' => $keyword,
    'defense_note' => 'Low 将 q 参数原样写入 HTML 响应。',
];
