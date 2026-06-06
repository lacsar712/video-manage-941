#!/usr/bin/env php
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$scriptDir = dirname(__FILE__);
$baseDir = dirname($scriptDir);

require_once $baseDir . '/config/database.php';
require_once $baseDir . '/config/helpers.php';

date_default_timezone_set('Asia/Shanghai');

$logFile = $baseDir . '/logs/cron.log';

$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";
    echo $logLine;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

logMessage("========================================");
logMessage("每日数据快照生成开始");
logMessage("当前时间: " . date('Y-m-d H:i:s'));
logMessage("========================================");

try {
    $db = getDB();

    $statDate = date('Y-m-d', strtotime('-1 day'));

    logMessage("生成日期: {$statDate} 的快照");

    $videoTotalStmt = $db->prepare("SELECT COUNT(*) FROM video");
    $videoTotalStmt->execute();
    $videoTotal = (int)$videoTotalStmt->fetchColumn();

    $videoPublishedStmt = $db->prepare("SELECT COUNT(*) FROM video WHERE status = 1");
    $videoPublishedStmt->execute();
    $videoPublished = (int)$videoPublishedStmt->fetchColumn();

    $sourceTotalStmt = $db->prepare("SELECT COUNT(*) FROM video_source");
    $sourceTotalStmt->execute();
    $sourceTotal = (int)$sourceTotalStmt->fetchColumn();

    $newVideosStmt = $db->prepare("SELECT COUNT(*) FROM video WHERE DATE(created_at) = ?");
    $newVideosStmt->execute([$statDate]);
    $newVideos = (int)$newVideosStmt->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO daily_stats_snapshot (stat_date, video_total, video_published, source_total, new_videos, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            video_total = VALUES(video_total),
            video_published = VALUES(video_published),
            source_total = VALUES(source_total),
            new_videos = VALUES(new_videos),
            created_at = NOW()
    ");
    $stmt->execute([$statDate, $videoTotal, $videoPublished, $sourceTotal, $newVideos]);

    logMessage("快照生成成功:");
    logMessage("  影片总量: {$videoTotal}");
    logMessage("  已上架影片: {$videoPublished}");
    logMessage("  播放源总量: {$sourceTotal}");
    logMessage("  当日新增影片: {$newVideos}");

    logMessage("========================================");
    logMessage("每日数据快照生成完成");
    logMessage("========================================");

} catch (Exception $e) {
    logMessage("错误: " . $e->getMessage());
    exit(1);
}
