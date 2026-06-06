<?php
/**
 * 简单的集成测试 - 验证基本功能
 */

// 设置环境变量
putenv('DB_HOST=mysql');
putenv('DB_NAME=video_app');
putenv('DB_USER=root');
putenv('DB_PASS=root123');

// 引入配置
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/helpers.php';

echo "========================================\n";
echo "运行简单集成测试\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;

// 测试1: 数据库连接
echo "[1/5] 测试数据库连接...\n";
try {
    $db = getDB();
    echo "✓ 数据库连接成功\n\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n\n";
    $failed++;
}

// 测试2: 查询影片列表
echo "[2/5] 测试查询影片列表...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM video");
    $result = $stmt->fetch();
    echo "✓ 查询成功，影片数量: " . $result['count'] . "\n\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ 查询失败: " . $e->getMessage() . "\n\n";
    $failed++;
}

// 测试3: 测试辅助函数 - generateToken
echo "[3/5] 测试 generateToken 函数...\n";
try {
    $token = generateToken();
    if (strlen($token) === 64 && ctype_xdigit($token)) {
        echo "✓ generateToken 函数正常，生成的 token: " . substr($token, 0, 16) . "...\n\n";
        $passed++;
    } else {
        echo "✗ generateToken 函数返回值不正确\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ generateToken 函数失败: " . $e->getMessage() . "\n\n";
    $failed++;
}

// 测试4: 测试辅助函数 - formatDateTime
echo "[4/5] 测试 formatDateTime 函数...\n";
try {
    $formatted = formatDateTime(time());
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $formatted)) {
        echo "✓ formatDateTime 函数正常，格式化结果: $formatted\n\n";
        $passed++;
    } else {
        echo "✗ formatDateTime 函数返回格式不正确\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ formatDateTime 函数失败: " . $e->getMessage() . "\n\n";
    $failed++;
}

// 测试5: 测试辅助函数 - sanitizeInput
echo "[5/5] 测试 sanitizeInput 函数...\n";
try {
    $input = '<script>alert("xss")</script>';
    $sanitized = sanitizeInput($input);
    if (strpos($sanitized, '<script>') === false && strpos($sanitized, '&lt;script&gt;') !== false) {
        echo "✓ sanitizeInput 函数正常，XSS 防护有效\n\n";
        $passed++;
    } else {
        echo "✗ sanitizeInput 函数 XSS 防护无效\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ sanitizeInput 函数失败: " . $e->getMessage() . "\n\n";
    $failed++;
}

// 输出结果
echo "========================================\n";
echo "测试完成\n";
echo "========================================\n";
echo "通过: $passed\n";
echo "失败: $failed\n";
echo "总计: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ 所有测试通过！\n";
    exit(0);
} else {
    echo "\n✗ 有测试失败\n";
    exit(1);
}
