<?php
// 验证并处理图片上传的通用逻辑
function processImageUpload($targetDir, $targetUrlPrefix, $saveToMediaLibrary = false, $tokenData = null) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error('请选择要上传的文件');
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        error('文件大小不能超过 5MB');
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        error('只支持 JPG、PNG、GIF、WebP 格式的图片');
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        error('不支持的文件扩展名');
    }

    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $uploadDir = __DIR__ . '/../../' . $targetDir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $newFileName;
    if (!move_uploaded_file($fileTmpName, $destination)) {
        error('文件上传失败');
    }

    $url = $targetUrlPrefix . $newFileName;

    if ($saveToMediaLibrary && $tokenData) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO media_asset (file_path, original_name, mime_type, size_bytes, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$url, $fileName, $mimeType, $fileSize, $tokenData['admin_id']]);
        } catch (Exception $e) {
            if (file_exists($destination)) {
                unlink($destination);
            }
            error('保存媒资记录失败：' . $e->getMessage());
        }
    }

    return $url;
}

// 上传封面图片（兼容旧接口，也写入媒资库）
function uploadCover($tokenData) {
    $url = processImageUpload('uploads/covers/', '/uploads/covers/', true, $tokenData);
    success(['url' => $url], '上传成功');
}

// 统一媒资上传接口
function uploadMedia($tokenData) {
    $url = processImageUpload('uploads/media/', '/uploads/media/', true, $tokenData);
    success(['url' => $url], '上传成功');
}

// 处理上传请求
function handleUploadRequest($path, $method, $tokenData) {
    if ($method === 'POST' && $path === 'upload/cover') {
        uploadCover($tokenData);
    } elseif ($method === 'POST' && $path === 'upload/media') {
        uploadMedia($tokenData);
    } else {
        error('接口不存在', 404);
    }
}
