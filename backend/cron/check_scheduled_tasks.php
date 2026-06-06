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

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";
    echo $logLine;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

logMessage("========================================");
logMessage("定时任务轮询开始");
logMessage("当前时间: " . date('Y-m-d H:i:s'));
logMessage("========================================");

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT st.*, v.title as video_title, v.status as video_status
        FROM scheduled_task st
        JOIN video v ON st.video_id = v.id
        WHERE st.status = 'pending' AND st.execute_at <= NOW()
        ORDER BY st.execute_at ASC
        LIMIT 50
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll();

    $total = count($tasks);
    logMessage("找到 {$total} 个待执行任务");

    $successCount = 0;
    $failCount = 0;

    foreach ($tasks as $task) {
        $taskId = $task['id'];
        $videoId = $task['video_id'];
        $action = $task['action'];
        $videoTitle = $task['video_title'];
        $actionText = $action === 'publish' ? '上架' : '下架';
        $targetStatus = $action === 'publish' ? 1 : 0;

        logMessage("处理任务 #{$taskId}: 影片【{$videoTitle}】 {$actionText}");

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$targetStatus, $videoId]);

            $stmt = $db->prepare("
                UPDATE scheduled_task
                SET status = 'executed', executed_at = NOW(), result_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $resultMsg = "成功执行{$actionText}操作，影片状态已更新";
            $stmt->execute([$resultMsg, $taskId]);

            $db->commit();

            writeOperationLog(
                $task['created_by'],
                'scheduled_task',
                'execute',
                'scheduled_task',
                $taskId,
                "定时任务自动执行：影片【{$videoTitle}】{$actionText}成功",
                'success'
            );

            logMessage("  任务 #{$taskId} 执行成功");
            $successCount++;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $errorMsg = $e->getMessage();
            $stmt = $db->prepare("
                UPDATE scheduled_task
                SET status = 'executed', executed_at = NOW(), result_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute(["执行失败: {$errorMsg}", $taskId]);

            writeOperationLog(
                $task['created_by'],
                'scheduled_task',
                'execute',
                'scheduled_task',
                $taskId,
                "定时任务自动执行：影片【{$videoTitle}】{$actionText}失败",
                'failed',
                $errorMsg
            );

            logMessage("  任务 #{$taskId} 执行失败: {$errorMsg}");
            $failCount++;
        }
    }

    logMessage("========================================");
    logMessage("轮询完成");
    logMessage("成功: {$successCount}, 失败: {$failCount}, 总计: {$total}");
    logMessage("========================================");

} catch (Exception $e) {
    logMessage("错误: " . $e->getMessage());
    exit(1);
}
