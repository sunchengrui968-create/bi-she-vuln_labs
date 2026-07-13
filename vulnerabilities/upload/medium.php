<?php
$uploadNotice = '';
$uploadedPath = '';
$maxUploadSize = 2 * 1024 * 1024;
$allowedClientMimes = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'upload_avatar') {
    if (!isset($_FILES['avatar'])) {
        $uploadNotice = '没有收到上传文件。';
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $uploadNotice = '文件上传失败，错误码：' . $_FILES['avatar']['error'];
    } elseif ((int) $_FILES['avatar']['size'] > $maxUploadSize) {
        $uploadNotice = '文件过大，Medium 最大允许 2 MB。';
    } elseif (!in_array((string) $_FILES['avatar']['type'], $allowedClientMimes, true)) {
        $uploadNotice = '客户端 MIME 不在图片白名单中。';
    } elseif (@getimagesize((string) $_FILES['avatar']['tmp_name']) === false) {
        $uploadNotice = '文件没有通过图片头检查。';
    } else {
        $uploadDir = __DIR__ . '/../../uploads/medium/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $originalName = basename((string) $_FILES['avatar']['name']);
        $targetPath = $uploadDir . $originalName;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $uploadedPath = 'uploads/medium/' . $originalName;
            $uploadNotice = '图片校验通过，文件按原始名称保存：' . $uploadedPath;
        } else {
            $uploadNotice = '文件移动失败，请检查目录权限。';
        }
    }
}

return [
    'notice' => $uploadNotice,
    'path' => $uploadedPath,
    'target_hint' => '../../flags/medium/upload.txt',
    'defense_note' => 'Medium 检查大小、客户端 MIME 与图片头，但仍保留原始扩展名并存入可解析目录。',
];
