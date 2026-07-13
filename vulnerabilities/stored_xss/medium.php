<?php
$messageNotice = '';
$stripScriptTags = static function (string $value): string {
    return (string) preg_replace('#<script\b[^>]*>.*?</script>#is', '', $value);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_message') {
    $name = (string) ($_POST['name'] ?? '');
    $content = $stripScriptTags((string) ($_POST['content'] ?? ''));
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    try {
        $statement = get_pdo()->prepare('INSERT INTO messages (difficulty, name, content, ip_address) VALUES (?, ?, ?, ?)');
        $statement->execute(['medium', $name, $content, $ipAddress]);
        $messageNotice = '留言已保存。Medium 已尝试移除 script 标签。';
    } catch (Throwable $exception) {
        $messageNotice = '留言保存失败，请检查数据库迁移。';
    }
}

$messages = [];
$messageError = '';
try {
    $statement = get_pdo()->prepare(
        'SELECT id, name, content, ip_address, created_at FROM messages WHERE difficulty = ? ORDER BY id DESC LIMIT 20'
    );
    $statement->execute(['medium']);
    $messages = $statement->fetchAll();
    foreach ($messages as &$message) {
        $message['content'] = $stripScriptTags((string) $message['content']);
    }
    unset($message);
} catch (Throwable $exception) {
    $messageError = '留言读取失败，请检查数据库迁移。';
}

return [
    'notice' => $messageNotice,
    'error' => $messageError,
    'messages' => $messages,
    'escape_name' => true,
    'defense_note' => 'Medium 正常转义姓名，并尝试删除 script 标签，但仍允许其他 HTML 标签及事件属性。',
];
