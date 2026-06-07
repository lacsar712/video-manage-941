<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AdminService;

class AdminController extends Controller
{
    private AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = $input['username'] ?? $_POST['username'] ?? '';
        $password = $input['password'] ?? $_POST['password'] ?? '';

        validateRequired([
            'username' => '用户名',
            'password' => '密码'
        ], ['username' => $username, 'password' => $password]);

        $result = $this->adminService->login($username, $password);
        Response::success($result, '登录成功');
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();

        if ($path === 'admin/logout' && $method === 'POST') {
            $this->adminService->logout($tokenData['token']);
            Response::success(null, '退出成功');
        } elseif ($path === 'admin/info' && $method === 'GET') {
            $result = $this->adminService->getInfo($tokenData);
            Response::success($result);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
