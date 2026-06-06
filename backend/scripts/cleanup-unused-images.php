#!/usr/bin/env php
<?php
/**
 * 清理未使用的图片脚本
 *
 * 功能：
 * - 扫描 uploads/covers 目录中的所有图片
 * - 对比数据库中正在使用的图片
 * - 删除超过指定天数且未被使用的图片
 * - 生成清理日志
 *
 * 使用方法：
 * php cleanup-unused-images.php [--days=30] [--dry-run] [--keep-test]
 *
 * 参数：
 * --days=N      只删除 N 天前的未使用图片（默认：30）
 * --dry-run     预览模式，不实际删除文件
 * --keep-test   保留测试图片（test-cover-*.jpg）
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 获取脚本所在目录
$scriptDir = dirname(__FILE__);
$baseDir = dirname($scriptDir);

// 加载配置
require_once $baseDir . '/config/database.php';

// 解析命令行参数
$options = getopt('', ['days:', 'dry-run', 'keep-test', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$daysThreshold = isset($options['days']) ? intval($options['days']) : 30;
$dryRun = isset($options['dry-run']);
$keepTest = isset($options['keep-test']);

// 配置
$uploadsDir = $baseDir . '/uploads/covers';
$backupDir = $baseDir . '/uploads/backup';
$logFile = $baseDir . '/logs/cleanup-images.log';

// 确保日志目录存在
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 开始清理
logMessage("========================================");
logMessage("图片清理任务开始");
logMessage("时间: " . date('Y-m-d H:i:s'));
logMessage("天数阈值: {$daysThreshold} 天");
logMessage("预览模式: " . ($dryRun ? '是' : '否'));
logMessage("保留测试图片: " . ($keepTest ? '是' : '否'));
logMessage("========================================");

try {
    // 连接数据库
    $db = getDB();

    // 获取数据库中正在使用的图片列表
    $stmt = $db->query("SELECT DISTINCT cover_url FROM video WHERE cover_url IS NOT NULL AND cover_url != ''");
    $usedImages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $coverUrl = $row['cover_url'];
        // 提取文件名
        $filename = basename($coverUrl);
        $usedImages[$filename] = true;
    }

    logMessage("数据库中正在使用的图片数量: " . count($usedImages));

    // 扫描 uploads/covers 目录
    if (!is_dir($uploadsDir)) {
        logMessage("错误: 上传目录不存在: {$uploadsDir}");
        exit(1);
    }

    $files = scandir($uploadsDir);
    $totalFiles = 0;
    $deletedFiles = 0;
    $skippedFiles = 0;
    $totalSize = 0;
    $deletedSize = 0;

    foreach ($files as $file) {
        // 跳过 . 和 ..
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $uploadsDir . '/' . $file;

        // 只处理图片文件
        if (!is_file($filePath) || !preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            continue;
        }

        $totalFiles++;
        $fileSize = filesize($filePath);
        $totalSize += $fileSize;

        // 检查是否是测试图片
        if ($keepTest && preg_match('/^test-cover-\d+\.(jpg|jpeg|png)$/i', $file)) {
            logMessage("跳过测试图片: {$file}");
            $skippedFiles++;
            continue;
        }

        // 检查是否被使用
        if (isset($usedImages[$file])) {
            logMessage("跳过使用中的图片: {$file}");
            $skippedFiles++;
            continue;
        }

        // 检查文件修改时间
        $fileTime = filemtime($filePath);
        $daysSinceModified = (time() - $fileTime) / 86400;

        if ($daysSinceModified < $daysThreshold) {
            logMessage("跳过最近修改的图片: {$file} (修改于 " . round($daysSinceModified, 1) . " 天前)");
            $skippedFiles++;
            continue;
        }

        // 删除或移动文件
        if ($dryRun) {
            logMessage("[预览] 将删除: {$file} (" . formatSize($fileSize) . ", 修改于 " . round($daysSinceModified, 1) . " 天前)");
            $deletedFiles++;
            $deletedSize += $fileSize;
        } else {
            // 创建备份目录
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // 移动到备份目录（而不是直接删除，更安全）
            $backupPath = $backupDir . '/' . date('Ymd_His') . '_' . $file;
            if (rename($filePath, $backupPath)) {
                logMessage("已移动到备份: {$file} -> {$backupPath} (" . formatSize($fileSize) . ")");
                $deletedFiles++;
                $deletedSize += $fileSize;
            } else {
                logMessage("错误: 无法移动文件: {$file}");
            }
        }
    }

    // 输出统计信息
    logMessage("========================================");
    logMessage("清理完成");
    logMessage("扫描文件总数: {$totalFiles}");
    logMessage("跳过文件数: {$skippedFiles}");
    logMessage("清理文件数: {$deletedFiles}");
    logMessage("总文件大小: " . formatSize($totalSize));
    logMessage("清理文件大小: " . formatSize($deletedSize));
    logMessage("========================================");

} catch (Exception $e) {
    logMessage("错误: " . $e->getMessage());
    exit(1);
}

/**
 * 记录日志
 */
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";

    // 输出到控制台
    echo $logLine;

    // 写入日志文件
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

/**
 * 格式化文件大小
 */
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * 显示帮助信息
 */
function showHelp() {
    echo <<<HELP
图片清理脚本

功能：
  自动清理 uploads/covers 目录中未使用的图片文件

使用方法：
  php cleanup-unused-images.php [选项]

选项：
  --days=N      只删除 N 天前的未使用图片（默认：30）
  --dry-run     预览模式，不实际删除文件
  --keep-test   保留测试图片（test-cover-*.jpg）
  --help        显示此帮助信息

示例：
  # 预览将要删除的文件
  php cleanup-unused-images.php --dry-run

  # 删除 7 天前的未使用图片
  php cleanup-unused-images.php --days=7

  # 删除未使用图片但保留测试图片
  php cleanup-unused-images.php --keep-test

注意：
  - 文件不会被直接删除，而是移动到 uploads/backup 目录
  - 清理日志保存在 logs/cleanup-images.log

HELP;
}
