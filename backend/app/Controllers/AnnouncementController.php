<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;

    public function __construct()
    {
        $this->announcementService = new AnnouncementService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'announcements') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $status = $this->getQueryParam('status', '');
            $type = $this->getQueryParam('type', '');
            $keyword = $this->getQueryParam('keyword', '');
            $result = $this->announcementService->getList($page, $pageSize, $status, $type, $keyword);
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'announcements/active') {
            $result = $this->announcementService->getActive();
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2 && $parts[0] === 'announcements') {
            validateInt($parts[1], '公告ID');
            $result = $this->announcementService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'announcements') {
            $input = $this->getJsonInput();
            $data = [
                'title' => $input['title'] ?? '',
                'content' => $input['content'] ?? '',
                'type' => $input['type'] ?? 'update',
                'start_at' => $input['start_at'] ?? '',
                'end_at' => $input['end_at'] ?? '',
                'status' => $input['status'] ?? 1,
            ];
            $announcementId = $this->announcementService->create($data, $tokenData);
            Response::success(['id' => $announcementId], '创建成功');
        } elseif ($method === 'POST' && count($parts) === 2 && $parts[0] === 'announcements') {
            validateInt($parts[1], '公告ID');
            $input = $this->getJsonInput();
            $data = [
                'title' => $input['title'] ?? '',
                'content' => $input['content'] ?? '',
                'type' => $input['type'] ?? 'update',
                'start_at' => $input['start_at'] ?? '',
                'end_at' => $input['end_at'] ?? '',
                'status' => $input['status'] ?? 1,
            ];
            $this->announcementService->update(intval($parts[1]), $data, $tokenData);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2 && $parts[0] === 'announcements') {
            validateInt($parts[1], '公告ID');
            $this->announcementService->delete(intval($parts[1]), $tokenData);
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[0] === 'announcements' && $parts[2] === 'status') {
            validateInt($parts[1], '公告ID');
            $input = $this->getJsonInput();
            $status = $input['status'] ?? null;
            $this->announcementService->updateStatus(intval($parts[1]), $status, $tokenData);
            Response::success(null, '状态更新成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
