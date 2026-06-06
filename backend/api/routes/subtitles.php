<?php

$allowedLanguages = ['zh', 'en', 'ja'];
$allowedFormats = ['vtt', 'srt'];
$allowedExtensions = ['vtt', 'srt'];
$maxSubtitleSize = 2 * 1024 * 1024;

function getSubtitleList()
{
    global $allowedLanguages;

    $videoId = $_GET['video_id'] ?? '';

    if (empty($videoId)) {
        error('影片ID不能为空');
    }

    validateInt($videoId, '影片ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, title FROM video WHERE id = ?");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在', 404);
        }

        $stmt = $db->prepare("
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

        success([
            'video' => $video,
            'list' => $list
        ]);
    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function uploadSubtitle()
{
    global $allowedLanguages, $allowedFormats, $allowedExtensions, $maxSubtitleSize;

    $videoId = $_POST['video_id'] ?? '';
    $language = $_POST['language'] ?? '';

    validateRequired([
        'video_id' => '影片ID',
        'language' => '语言'
    ], ['video_id' => $videoId, 'language' => $language]);

    validateInt($videoId, '影片ID');

    if (!in_array($language, $allowedLanguages)) {
        error('语言值不正确，仅支持 zh、en、ja');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error('请选择要上传的字幕文件');
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    if ($fileSize > $maxSubtitleSize) {
        error('文件大小不能超过 2MB');
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        error('仅支持 .vtt 和 .srt 格式的字幕文件');
    }

    $format = $ext;

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video WHERE id = ?");
        $stmt->execute([$videoId]);
        if (!$stmt->fetch()) {
            error('影片不存在', 404);
        }

        $targetDir = 'uploads/subtitles/';
        $uploadDir = __DIR__ . '/../../' . $targetDir;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = $videoId . '_' . $language . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmpName, $destination)) {
            error('文件上传失败');
        }

        $fileUrl = '/uploads/subtitles/' . $newFileName;

        $stmt = $db->prepare("
            INSERT INTO video_subtitle (video_id, language, format, file_url, file_name, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$videoId, $language, $format, $fileUrl, $fileName]);

        $subtitleId = $db->lastInsertId();

        $previewLines = readSubtitlePreview($destination, 20);

        success([
            'id' => $subtitleId,
            'file_url' => $fileUrl,
            'preview' => $previewLines
        ], '上传成功');
    } catch (Exception $e) {
        if (isset($destination) && file_exists($destination)) {
            unlink($destination);
        }
        error('上传失败：' . $e->getMessage());
    }
}

function readSubtitlePreview($filePath, $maxLines = 20)
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

function getSubtitlePreview($id)
{
    validateInt($id, '字幕ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, file_url FROM video_subtitle WHERE id = ?");
        $stmt->execute([$id]);
        $subtitle = $stmt->fetch();

        if (!$subtitle) {
            error('字幕不存在', 404);
        }

        $filePath = __DIR__ . '/../../' . ltrim($subtitle['file_url'], '/');
        $preview = readSubtitlePreview($filePath, 50);

        success([
            'preview' => $preview
        ]);
    } catch (Exception $e) {
        error('获取预览失败：' . $e->getMessage());
    }
}

function deleteSubtitle($id)
{
    validateInt($id, '字幕ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, file_url FROM video_subtitle WHERE id = ?");
        $stmt->execute([$id]);
        $subtitle = $stmt->fetch();

        if (!$subtitle) {
            error('字幕不存在', 404);
        }

        $filePath = __DIR__ . '/../../' . ltrim($subtitle['file_url'], '/');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $db->prepare("DELETE FROM video_subtitle WHERE id = ?");
        $stmt->execute([$id]);

        success(null, '删除成功');
    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

function updateSubtitleStatus($id)
{
    global $allowedLanguages;

    validateInt($id, '字幕ID');

    $status = $_POST['status'] ?? '';

    if ($status === '') {
        error('状态不能为空');
    }

    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video_subtitle WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('字幕不存在', 404);
        }

        $stmt = $db->prepare("UPDATE video_subtitle SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '启用成功' : '禁用成功');
    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

function handleSubtitleRequest($path, $method)
{
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'subtitles') {
        getSubtitleList();
    } elseif ($method === 'POST' && $path === 'subtitles') {
        uploadSubtitle();
    } elseif ($method === 'GET' && count($parts) === 3 && $parts[2] === 'preview') {
        getSubtitlePreview($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteSubtitle($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateSubtitleStatus($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
