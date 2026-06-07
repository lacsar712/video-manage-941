<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;
use App\Core\Database;

class UploadService extends Service
{
    public function processImageUpload(string $targetDir, string $targetUrlPrefix, bool $saveToMediaLibrary, ?array $tokenData): string
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('请选择要上传的文件');
        }

        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];

        $maxSize = 5 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            Response::error('文件大小不能超过 5MB');
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Response::error('只支持 JPG、PNG、GIF、WebP 格式的图片');
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            Response::error('不支持的文件扩展名');
        }

        $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        $uploadDir = __DIR__ . '/../../' . $targetDir;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $newFileName;
        if (!move_uploaded_file($fileTmpName, $destination)) {
            Response::error('文件上传失败');
        }

        $url = $targetUrlPrefix . $newFileName;

        if ($saveToMediaLibrary && $tokenData) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    INSERT INTO media_asset (file_path, original_name, mime_type, size_bytes, uploaded_by, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$url, $fileName, $mimeType, $fileSize, $tokenData['admin_id']]);
            } catch (\Exception $e) {
                if (file_exists($destination)) {
                    unlink($destination);
                }
                Response::error('保存媒资记录失败：' . $e->getMessage());
            }
        }

        return $url;
    }

    public function uploadCover(array $tokenData): string
    {
        return $this->processImageUpload('uploads/covers/', '/uploads/covers/', true, $tokenData);
    }

    public function uploadMedia(array $tokenData): string
    {
        return $this->processImageUpload('uploads/media/', '/uploads/media/', true, $tokenData);
    }
}
