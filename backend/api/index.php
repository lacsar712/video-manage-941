<?php
// 加载环境变量
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入配置文件
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 获取请求路径
$path = $_GET['path'] ?? '';
// 如果没有path参数，尝试从REQUEST_URI中提取
if (empty($path) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    // 移除查询字符串
    if (($pos = strpos($requestUri, '?')) !== false) {
        $requestUri = substr($requestUri, 0, $pos);
    }
    // 提取 /api/ 后面的路径
    if (preg_match('#^/api/(.+)$#', $requestUri, $matches)) {
        $path = $matches[1];
    }
}
$method = $_SERVER['REQUEST_METHOD'];

// 路由分发
try {
    // 管理员登录（不需要token）
    if (($path === 'login' || $path === 'admin/login') && $method === 'POST') {
        require __DIR__ . '/routes/admin.php';
        adminLogin();
        exit;
    }

    // APP API（不需要token）
    if (strpos($path, 'app/') === 0) {
        require __DIR__ . '/routes/app.php';
        handleAppRequest($path, $method);
        exit;
    }

    // 以下接口需要验证token
    $tokenData = validateToken();

    // 管理员相关
    if (strpos($path, 'admin/') === 0) {
        require __DIR__ . '/routes/admin.php';
        handleAdminRequest($path, $method, $tokenData);
        exit;
    }

    // 影片管理
    if (strpos($path, 'videos') === 0) {
        require __DIR__ . '/routes/videos.php';
        handleVideoRequest($path, $method);
        exit;
    }

    // 播放源管理
    if (strpos($path, 'sources') === 0) {
        require __DIR__ . '/routes/sources.php';
        handleSourceRequest($path, $method);
        exit;
    }

    // 文件上传
    if (strpos($path, 'upload/') === 0) {
        require __DIR__ . '/routes/upload.php';
        handleUploadRequest($path, $method);
        exit;
    }

    error('接口不存在', 404);

} catch (Exception $e) {
    error('服务器错误：' . $e->getMessage(), 500);
}
