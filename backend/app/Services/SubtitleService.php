<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class SubtitleService extends Service
{
    private array $allowedLanguages = ['zh', 'en', 'ja'];
    private int $maxSubtitleSize = 2 * 1024 * 1024;

    private function readSubtitlePreview(string $filePath, int $maxLines = 20): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $lines = [];
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false && $count < $maxLines) {
                $lines[] = rtrim($line, "\r\n");
                $count++;
            }
            fclose($handle);
        }

        return implode("\n", $lines);
    }

    public function getList(int $videoId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, title FROM video WHERE id = ?");
            $stmt->execute([$videoId]);
            $video = $stmt->fetch();

            if (!$video) {
                Response::error('影片不存在', 404);
            }

            $stmt = $this->db->prepare("
                SELECT id, video_id, language, format, file_url, file_name, status, created_at, updated_at
                FROM video_subtitle
                WHERE video_id = ?
                ORDER BY FIELD(language, 'zh', 'en', 'ja'), id ASC
            ");
            $stmt->execute([$videoId]);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
            }

            return [
                'video' => $video,
                'list' => $list
            ];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function upload(int $videoId, string $language): array
    {
        validateRequired([
            'video_id' => '影片ID',
            'language' => '语言'
        ], ['video_id' => $videoId, 'language' => $language]);

        if (!in_array($language, $this->allowedLanguages)) {
            Response::error('语言值不正确，仅支持 zh、en、ja');
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('请选择要上传的字幕文件');
        }

        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];

        if ($fileSize > $this->maxSubtitleSize) {
            Response::error('文件大小不能超过 2MB');
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['vtt', 'srt'])) {
            Response::error('仅支持 .vtt 和 .srt 格式的字幕文件');
        }

        $format = $ext;
        $destination = null;

        try {
            $stmt = $this->db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$videoId]);
            if (!$stmt->fetch()) {
                Response::error('影片不存在', 404);
            }

            $targetDir = 'uploads/subtitles/';
            $uploadDir = __DIR__ . '/../../' . $targetDir;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = $videoId . '_' . $language . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $newFileName;

            if (!move_uploaded_file($fileTmpName, $destination)) {
                Response::error('文件上传失败');
            }

            $fileUrl = '/uploads/subtitles/' . $newFileName;

            $stmt = $this->db->prepare("
                INSERT INTO video_subtitle (video_id, language, format, file_url, file_name, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$videoId, $language, $format, $fileUrl, $fileName]);

            $subtitleId = (int)$this->db->lastInsertId();
            $previewLines = $this->readSubtitlePreview($destination, 20);

            return [
                'id' => $subtitleId,
                'file_url' => $fileUrl,
                'preview' => $previewLines
            ];
        } catch (\Exception $e) {
            if (isset($destination) && file_exists($destination)) {
                unlink($destination);
            }
            Response::error('上传失败：' . $e->getMessage());
        }
    }

    public function getPreview(int $id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, file_url FROM video_subtitle WHERE id = ?");
            $stmt->execute([$id]);
            $subtitle = $stmt->fetch();

            if (!$subtitle) {
                Response::error('字幕不存在', 404);
            }

            $filePath = __DIR__ . '/../../' . ltrim($subtitle['file_url'], '/');
            $preview = $this->readSubtitlePreview($filePath, 50);

            return ['preview' => $preview];
        } catch (\Exception $e) {
            Response::error('获取预览失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id, file_url FROM video_subtitle WHERE id = ?");
            $stmt->execute([$id]);
            $subtitle = $stmt->fetch();

            if (!$subtitle) {
                Response::error('字幕不存在', 404);
            }

            $filePath = __DIR__ . '/../../' . ltrim($subtitle['file_url'], '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmt = $this->db->prepare("DELETE FROM video_subtitle WHERE id = ?");
            $stmt->execute([$id]);
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, mixed $status): string
    {
        if ($status === '') {
            Response::error('状态不能为空');
        }

        if (!in_array($status, ['0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video_subtitle WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('字幕不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE video_subtitle SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            return $status == 1 ? '启用成功' : '禁用成功';
        } catch (\Exception $e) {
            Response::error('操作失败：' . $e->getMessage());
        }
    }
}
