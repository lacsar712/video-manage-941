<?php
// 静态文件服务器
// 处理 /uploads/ 路径的静态文件请求

$requestUri = $_SERVER['REQUEST_URI'];

// 检查是否是上传文件请求
if (strpos($requestUri, '/uploads/') === 0) {
    $filePath = __DIR__ . $requestUri;

    if (file_exists($filePath) && is_file($filePath)) {
        // 获取文件MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // 设置响应头
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');

        // 输出文件内容
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
}

// 如果不是静态文件请求，处理API请求
// 从URI中提取路径参数
if (strpos($requestUri, '/api/') === 0) {
    $path = substr($requestUri, 5); // 移除 '/api/' 前缀
    // 移除查询字符串
    if (($pos = strpos($path, '?')) !== false) {
        $path = substr($path, 0, $pos);
    }
    $_GET['path'] = $path;
}

require __DIR__ . '/api/index.php';
