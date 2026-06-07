<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ClientReleaseService;

class ClientReleaseController extends Controller
{
    private ClientReleaseService $clientReleaseService;

    public function __construct()
    {
        $this->clientReleaseService = new ClientReleaseService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'client_releases') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $platform = $this->getQueryParam('platform', '');
            $status = $this->getQueryParam('status', '');
            $result = $this->clientReleaseService->getList($page, $pageSize, $platform, $status);
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'client_releases/latest') {
            $result = $this->clientReleaseService->getLatest();
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2) {
            validateInt($parts[1], '版本ID');
            $result = $this->clientReleaseService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'client_releases') {
            $input = $this->getJsonInput();
            $data = [
                'platform' => $input['platform'] ?? '',
                'version_name' => $input['version_name'] ?? '',
                'version_code' => $input['version_code'] ?? '',
                'download_url' => $input['download_url'] ?? '',
                'force_update' => $input['force_update'] ?? 0,
                'changelog' => $input['changelog'] ?? '',
                'status' => $input['status'] ?? 0,
            ];
            $releaseId = $this->clientReleaseService->create($data, $tokenData);
            Response::success(['id' => intval($releaseId)], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '版本ID');
            $input = $this->getJsonInput();
            $data = [
                'platform' => $input['platform'] ?? '',
                'version_name' => $input['version_name'] ?? '',
                'version_code' => $input['version_code'] ?? '',
                'download_url' => $input['download_url'] ?? '',
                'force_update' => $input['force_update'] ?? '',
                'changelog' => $input['changelog'] ?? '',
                'status' => $input['status'] ?? '',
            ];
            $this->clientReleaseService->update(intval($parts[1]), $data, $tokenData);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '版本ID');
            $this->clientReleaseService->delete(intval($parts[1]), $tokenData);
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
            validateInt($parts[1], '版本ID');
            $input = $this->getJsonInput();
            $status = $input['status'] ?? '';
            $message = $this->clientReleaseService->updateStatus(intval($parts[1]), $status, $tokenData);
            Response::success(null, $message);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
