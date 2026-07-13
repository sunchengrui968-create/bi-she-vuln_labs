<?php
$id = $_GET['id'] ?? '1';
$filteredId = str_replace([' ', 'union', 'select', '--', '#'], '', (string) $id);
$sql = 'SELECT id, username AS first_name, nickname AS surname FROM users WHERE id = ' . $filteredId;
$rows = [];
$safeDbError = '';
$hasQuery = array_key_exists('id', $_GET);

if ($hasQuery) {
    try {
        $rows = get_pdo()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        $safeDbError = '数据库查询失败，详细错误已隐藏。';
    }
}

return [
    'id' => $id,
    'sql' => $sql,
    'rows' => $rows,
    'db_error' => $safeDbError,
    'has_query' => $hasQuery,
    'show_sql' => false,
    'defense_note' => 'Medium 会删除小写关键字、普通空格和部分注释符，但该黑名单并不完整。',
];
