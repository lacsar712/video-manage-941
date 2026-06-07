<?php
// 数据库配置
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'video_app');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root123');
define('DB_CHARSET', 'utf8mb4');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

function getDB(): PDO
{
    return Database::getConnection();
}
