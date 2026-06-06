<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * 管理员 API 集成测试
 */
class AdminApiTest extends TestCase
{
    /**
     * 测试管理员登录成功
     */
    public function testAdminLoginSuccess()
    {
        // 创建测试管理员
        $adminId = $this->createTestAdmin('admin', 'admin123');

        // 模拟登录请求
        $_POST['username'] = 'admin';
        $_POST['password'] = 'admin123';

        // 捕获输出
        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/admin.php';
            adminLogin();
        } catch (\Exception $e) {
            // jsonResponse 会调用 exit，捕获异常
        }
        $output = ob_get_clean();

        // 解析响应
        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertEquals('登录成功', $response['message']);
        $this->assertArrayHasKey('token', $response['data']);
        $this->assertArrayHasKey('username', $response['data']);
        $this->assertEquals('admin', $response['data']['username']);

        // 验证 token 已保存到数据库
        $this->assertDatabaseHas('admin_token', [
            'admin_id' => $adminId,
            'token' => $response['data']['token']
        ]);
    }

    /**
     * 测试管理员登录失败 - 用户名错误
     */
    public function testAdminLoginFailureWrongUsername()
    {
        $this->createTestAdmin('admin', 'admin123');

        $_POST['username'] = 'wrong_user';
        $_POST['password'] = 'admin123';

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/admin.php';
            adminLogin();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('用户名或密码错误', $response['message']);
    }

    /**
     * 测试管理员登录失败 - 密码错误
     */
    public function testAdminLoginFailureWrongPassword()
    {
        $this->createTestAdmin('admin', 'admin123');

        $_POST['username'] = 'admin';
        $_POST['password'] = 'wrong_password';

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/admin.php';
            adminLogin();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('用户名或密码错误', $response['message']);
    }

    /**
     * 测试管理员登录失败 - 缺少必填字段
     */
    public function testAdminLoginFailureMissingFields()
    {
        $_POST['username'] = '';
        $_POST['password'] = '';

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/admin.php';
            adminLogin();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不能为空', $response['message']);
    }
}
