<?php
$keyword = (string) ($_GET['q'] ?? '');
$filteredKeyword = strip_tags($keyword, '<img><svg>');

return [
    'keyword' => $keyword,
    'output' => $filteredKeyword,
    'defense_note' => 'Medium 删除大多数标签，只保留 img 与 svg；允许标签上的属性仍会被浏览器解析。',
];
