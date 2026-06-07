<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ScheduledTaskService;

class ScheduledTaskController extends Controller
{
    private ScheduledTaskService $scheduledTaskService;

    public function __construct()
    {
        $this->scheduledTaskService = new ScheduledTaskService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'scheduled_tasks') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $status = $this->getQueryParam('status', '');
            $action = $this->getQueryParam('action', '');
            $result = $this->scheduledTaskService->getList($page, $pageSize, $status, $action);
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'scheduled_tasks/upcoming') {
            $limit = intval($this->getQueryParam('limit', 5));
            $result = $this->scheduledTaskService->getUpcoming($limit);
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'scheduled_tasks') {
            $input = $this->getJsonInput();
            $videoId = $input['video_id'] ?? $_POST['video_id'] ?? '';
            $action = $input['action'] ?? $_POST['action'] ?? '';
            $executeAt = $input['execute_at'] ?? $_POST['execute_at'] ?? '';

            validateInt($videoId, '影片ID');
            $taskId = $this->scheduledTaskService->create(intval($videoId), $action, $executeAt, $tokenData);
            Response::success(['id' => $taskId], '创建成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'cancel') {
            validateInt($parts[1], '任务ID');
            $this->scheduledTaskService->cancel(intval($parts[1]), $tokenData);
            Response::success(null, '取消成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
