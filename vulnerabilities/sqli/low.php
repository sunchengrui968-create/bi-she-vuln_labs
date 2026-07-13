<?php
$id = $_GET['id'] ?? '1';
$sql = 'SELECT id, username AS first_name, nickname AS surname FROM users WHERE id = ' . $id;
$rows = [];
$dbError = '';
$hasQuery = array_key_exists('id', $_GET);

if ($hasQuery) {
    try {
        $rows = get_pdo()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        $dbError = $exception->getMessage();
    }
}

return [
    'id' => $id,
    'sql' => $sql,
    'rows' => $rows,
    'db_error' => $dbError,
    'has_query' => $hasQuery,
    'show_sql' => true,
    'defense_note' => 'Low 不进行类型校验、关键字过滤或错误隐藏。',
];
