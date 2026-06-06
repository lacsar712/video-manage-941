<?php
/**
 * PHPUnit 测试引导文件
 */

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入配置文件
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/helpers.php';

// 测试数据库配置
define('TEST_DB_NAME', 'video_app_test');

/**
 * 创建测试数据库
 */
function createTestDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE " . TEST_DB_NAME);

        // 创建表结构
        $sql = file_get_contents(__DIR__ . '/../mysql/init/init.sql');
        // 移除 USE 语句
        $sql = preg_replace('/USE\s+\w+;/i', '', $sql);
        $pdo->exec($sql);

        echo "✅ 测试数据库创建成功\n";
    } catch (PDOException $e) {
        echo "❌ 测试数据库创建失败: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * 清理测试数据库
 */
function cleanTestDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->exec("DROP DATABASE IF EXISTS " . TEST_DB_NAME);
        echo "✅ 测试数据库清理成功\n";
    } catch (PDOException $e) {
        echo "❌ 测试数据库清理失败: " . $e->getMessage() . "\n";
    }
}

// 注册关闭时清理
register_shutdown_function('cleanTestDatabase');

// 创建测试数据库
createTestDatabase();
