<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\VideoService;

class VideoController extends Controller
{
    private VideoService $videoService;

    public function __construct()
    {
        $this->videoService = new VideoService();
    }

    public function handle(Request $request): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'videos') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $status = $this->getQueryParam('status', '');
            $keyword = $this->getQueryParam('keyword', '');
            $contentRatingCode = $this->getQueryParam('content_rating_code', '');
            $onlyUnrated = $this->getQueryParam('only_unrated', '');

            $result = $this->videoService->getList($page, $pageSize, $status, $keyword, $contentRatingCode, $onlyUnrated);
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2) {
            validateInt($parts[1], '影片ID');
            $result = $this->videoService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'videos') {
            $title = $_POST['title'] ?? '';
            $coverUrl = $_POST['cover_url'] ?? '';
            $description = $_POST['description'] ?? '';
            $contentRatingCode = $_POST['content_rating_code'] ?? '';
            $status = $_POST['status'] ?? 1;

            $videoId = $this->videoService->create($title, $coverUrl, $description, $contentRatingCode, $status);
            Response::success(['id' => $videoId], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '影片ID');
            $title = $_POST['title'] ?? '';
            $coverUrl = $_POST['cover_url'] ?? '';
            $description = $_POST['description'] ?? '';
            $contentRatingCode = $_POST['content_rating_code'] ?? '';
            $status = $_POST['status'] ?? '';

            $this->videoService->update(intval($parts[1]), $title, $coverUrl, $description, $contentRatingCode, $status);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '影片ID');
            $this->videoService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
            validateInt($parts[1], '影片ID');
            $status = $_POST['status'] ?? '';
            $message = $this->videoService->updateStatus(intval($parts[1]), $status);
            Response::success(null, $message);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
