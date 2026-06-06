<?php
// 数据库配置
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'video_app');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root123');
define('DB_CHARSET', 'utf8mb4');

// 获取数据库连接
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}
