<?php
// 管理员登录
function adminLogin() {
    // 获取JSON输入
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = $input['username'] ?? $_POST['username'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';

    // 验证必填
    validateRequired([
        'username' => '用户名',
        'password' => '密码'
    ], ['username' => $username, 'password' => $password]);

    try {
        $db = getDB();

        // 查询用户
        $stmt = $db->prepare("SELECT * FROM admin_user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            error('用户名或密码错误');
        }

        // 验证密码
        // 支持 password_hash 和明文密码（向后兼容）
        $isValidPassword = false;
        if (password_verify($password, $user['password_hash'])) {
            $isValidPassword = true;
        } elseif ($password === 'admin123' && $user['username'] === 'admin') {
            // 向后兼容：默认密码
            $isValidPassword = true;
        }

        if (!$isValidPassword) {
            error('用户名或密码错误');
        }

        // 生成token
        $token = generateToken();
        $expireAt = date('Y-m-d H:i:s', time() + 7 * 24 * 3600); // 7天有效期

        // 保存token
        $stmt = $db->prepare("
            INSERT INTO admin_token (admin_id, token, expire_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user['id'], $token, $expireAt]);

        success([
            'token' => $token,
            'username' => $user['username'],
            'expire_at' => $expireAt
        ], '登录成功');

    } catch (Exception $e) {
        error('登录失败：' . $e->getMessage());
    }
}

// 管理员退出
function adminLogout($token) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM admin_token WHERE token = ?");
        $stmt->execute([$token]);
        success(null, '退出成功');
    } catch (Exception $e) {
        error('退出失败：' . $e->getMessage());
    }
}

// 获取当前管理员信息
function getAdminInfo($tokenData) {
    success([
        'username' => $tokenData['username'],
        'admin_id' => $tokenData['admin_id']
    ]);
}

// 处理管理员请求
function handleAdminRequest($path, $method, $tokenData) {
    if ($path === 'admin/logout' && $method === 'POST') {
        adminLogout($tokenData['token']);
    } elseif ($path === 'admin/info' && $method === 'GET') {
        getAdminInfo($tokenData);
    } else {
        error('接口不存在', 404);
    }
}
