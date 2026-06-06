<?php
// 统一响应格式
function jsonResponse($code, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200); // 所有业务接口返回200
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 成功响应
function success($data = null, $message = '操作成功') {
    jsonResponse(0, $message, $data);
}

// 错误响应
function error($message, $code = 1) {
    jsonResponse($code, $message, null);
}

// 验证必填字段
function validateRequired($fields, $data) {
    foreach ($fields as $field => $label) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            error("{$label}不能为空");
        }
    }
}

// 验证字符串长度
function validateLength($value, $min, $max, $label) {
    $len = mb_strlen($value, 'UTF-8');
    if ($len < $min || $len > $max) {
        error("{$label}长度必须在{$min}-{$max}个字符之间");
    }
}

// 验证URL格式
function validateUrl($url, $label) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error("{$label}格式不正确");
    }
}

// 验证整数
function validateInt($value, $label) {
    if (!is_numeric($value) || intval($value) != $value) {
        error("{$label}必须是整数");
    }
}

// 清理输入（防止XSS）
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// 清理输出（用于HTML显示）
function sanitizeOutput($output) {
    if (is_array($output)) {
        return array_map('sanitizeOutput', $output);
    }
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

// 格式化日期时间（统一格式：YYYY-MM-DD HH:mm:ss）
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date('Y-m-d H:i:s', $timestamp);
}

// 生成随机token
function generateToken() {
    return bin2hex(random_bytes(32));
}

// 验证token
function validateToken() {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        error('未登录或登录已过期', 401);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT at.*, au.username
            FROM admin_token at
            JOIN admin_user au ON at.admin_id = au.id
            WHERE at.token = ? AND at.expire_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();

        if (!$tokenData) {
            error('登录已过期，请重新登录', 401);
        }

        return $tokenData;
    } catch (Exception $e) {
        error('验证失败：' . $e->getMessage());
    }
}
