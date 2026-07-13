<?php
$uploadNotice = '';
$uploadedPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'upload_avatar') {
    if (!isset($_FILES['avatar'])) {
        $uploadNotice = '没有收到上传文件。';
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $uploadNotice = '文件上传失败，错误码：' . $_FILES['avatar']['error'];
    } else {
        $uploadDir = __DIR__ . '/../../uploads/low/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $originalName = basename((string) $_FILES['avatar']['name']);
        $targetPath = $uploadDir . $originalName;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $uploadedPath = 'uploads/low/' . $originalName;
            $uploadNotice = '文件上传成功，保存路径：' . $uploadedPath;
        } else {
            $uploadNotice = '文件移动失败，请检查目录权限。';
        }
    }
}

return [
    'notice' => $uploadNotice,
    'path' => $uploadedPath,
    'target_hint' => '../../flags/low/upload.txt',
    'defense_note' => 'Low 不检查文件大小、MIME、扩展名或内容。',
];
