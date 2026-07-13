<?php
$messageNotice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_message') {
    $name = (string) ($_POST['name'] ?? '');
    $content = (string) ($_POST['content'] ?? '');
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    try {
        $statement = get_pdo()->prepare('INSERT INTO messages (difficulty, name, content, ip_address) VALUES (?, ?, ?, ?)');
        $statement->execute(['low', $name, $content, $ipAddress]);
        $messageNotice = '留言已保存。Low 会原样渲染姓名和内容。';
    } catch (Throwable $exception) {
        $messageNotice = '留言保存失败：' . $exception->getMessage();
    }
}

$messages = [];
$messageError = '';
try {
    $statement = get_pdo()->prepare(
        'SELECT id, name, content, ip_address, created_at FROM messages WHERE difficulty = ? ORDER BY id DESC LIMIT 20'
    );
    $statement->execute(['low']);
    $messages = $statement->fetchAll();
} catch (Throwable $exception) {
    $messageError = $exception->getMessage();
}

return [
    'notice' => $messageNotice,
    'error' => $messageError,
    'messages' => $messages,
    'escape_name' => false,
    'defense_note' => 'Low 对姓名和留言内容都不做 HTML 转义。',
];
