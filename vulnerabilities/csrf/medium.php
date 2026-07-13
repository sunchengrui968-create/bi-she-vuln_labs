<?php
$emailNotice = '';
$emailKey = get_csrf_email_session_key('medium');
if (!isset($_SESSION[$emailKey])) {
    $_SESSION[$emailKey] = 'student@vuln-lab.local';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_email') {
    $newEmail = trim((string) ($_POST['email'] ?? ''));
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

    if ($newEmail === '') {
        $emailNotice = '邮箱不能为空。';
    } elseif ($referer === '' || $host === '' || strpos($referer, $host) === false) {
        $emailNotice = '请求来源检查失败：Referer 未包含当前主机名。';
    } else {
        $_SESSION[$emailKey] = $newEmail;
        $emailNotice = '来源检查通过，邮箱已修改为：' . $newEmail;
    }
}

return [
    'email' => (string) $_SESSION[$emailKey],
    'notice' => $emailNotice,
    'attack_email' => 'medium-attacker@evil.test',
    'defense_note' => 'Medium 仅检查 Referer 字符串是否包含当前主机名，没有使用随机同步 Token。',
];
