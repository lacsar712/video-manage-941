<?php
// 上传封面图片
function uploadCover() {
    // 检查是否有文件上传
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error('请选择要上传的文件');
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // 验证文件大小（5MB）
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        error('文件大小不能超过 5MB');
    }

    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        error('只支持 JPG、PNG、GIF、WebP 格式的图片');
    }

    // 获取文件扩展名
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        error('不支持的文件扩展名');
    }

    // 生成唯一文件名
    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    // 确保上传目录存在
    $uploadDir = __DIR__ . '/../../uploads/covers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 移动文件
    $destination = $uploadDir . $newFileName;
    if (!move_uploaded_file($fileTmpName, $destination)) {
        error('文件上传失败');
    }

    // 返回相对路径
    $url = '/uploads/covers/' . $newFileName;

    success(['url' => $url], '上传成功');
}

// 处理上传请求
function handleUploadRequest($path, $method) {
    if ($method === 'POST' && $path === 'upload/cover') {
        uploadCover();
    } else {
        error('接口不存在', 404);
    }
}
