<?php
/**
 * 迁移脚本：将现有的网络图片下载到本地
 *
 * 使用方法：
 * php migrate_covers.php
 */

require_once __DIR__ . '/../config/database.php';

echo "开始迁移封面图片...\n\n";

try {
    $db = getDB();

    // 查询所有有封面URL的影片
    $stmt = $db->query("SELECT id, title, cover_url FROM video WHERE cover_url IS NOT NULL AND cover_url != ''");
    $videos = $stmt->fetchAll();

    $total = count($videos);
    $success = 0;
    $failed = 0;
    $skipped = 0;

    echo "找到 {$total} 个影片需要处理\n\n";

    foreach ($videos as $video) {
        $id = $video['id'];
        $title = $video['title'];
        $coverUrl = $video['cover_url'];

        echo "[{$id}] {$title}\n";
        echo "  原URL: {$coverUrl}\n";

        // 如果已经是本地路径，跳过
        if (strpos($coverUrl, '/uploads/') === 0) {
            echo "  状态: 已经是本地路径，跳过\n\n";
            $skipped++;
            continue;
        }

        // 如果不是HTTP/HTTPS URL，跳过
        if (!preg_match('/^https?:\/\//i', $coverUrl)) {
            echo "  状态: 不是有效的URL，跳过\n\n";
            $skipped++;
            continue;
        }

        try {
            // 下载图片
            $imageData = @file_get_contents($coverUrl);

            if ($imageData === false) {
                echo "  状态: 下载失败\n\n";
                $failed++;
                continue;
            }

            // 获取文件扩展名
            $ext = 'jpg'; // 默认扩展名
            $urlPath = parse_url($coverUrl, PHP_URL_PATH);
            if ($urlPath) {
                $pathExt = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
                if (in_array($pathExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = $pathExt;
                }
            }

            // 生成新文件名
            $newFileName = time() . '_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            // 确保上传目录存在
            $uploadDir = __DIR__ . '/../uploads/covers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 保存文件
            $destination = $uploadDir . $newFileName;
            if (file_put_contents($destination, $imageData) === false) {
                echo "  状态: 保存失败\n\n";
                $failed++;
                continue;
            }

            // 更新数据库
            $newUrl = '/uploads/covers/' . $newFileName;
            $updateStmt = $db->prepare("UPDATE video SET cover_url = ? WHERE id = ?");
            $updateStmt->execute([$newUrl, $id]);

            echo "  新URL: {$newUrl}\n";
            echo "  状态: 成功\n\n";
            $success++;

            // 避免请求过快
            usleep(500000); // 0.5秒

        } catch (Exception $e) {
            echo "  状态: 失败 - " . $e->getMessage() . "\n\n";
            $failed++;
        }
    }

    echo "迁移完成！\n";
    echo "总计: {$total}\n";
    echo "成功: {$success}\n";
    echo "失败: {$failed}\n";
    echo "跳过: {$skipped}\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
