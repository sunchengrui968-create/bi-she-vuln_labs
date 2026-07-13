<?php
$emailNotice = '';
$emailKey = get_csrf_email_session_key('low');
if (!isset($_SESSION[$emailKey])) {
    $_SESSION[$emailKey] = 'student@vuln-lab.local';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_email') {
    $newEmail = trim((string) ($_POST['email'] ?? ''));
    if ($newEmail === '') {
        $emailNotice = '邮箱不能为空。';
    } else {
        $_SESSION[$emailKey] = $newEmail;
        $emailNotice = '邮箱已修改为：' . $newEmail;
    }
}

return [
    'email' => (string) $_SESSION[$emailKey],
    'notice' => $emailNotice,
    'attack_email' => 'attacker-controlled@evil.test',
    'defense_note' => 'Low 不使用 CSRF Token，也不检查请求来源。',
];
