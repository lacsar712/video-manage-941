<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ContentRatingService;

class ContentRatingController extends Controller
{
    private ContentRatingService $contentRatingService;

    public function __construct()
    {
        $this->contentRatingService = new ContentRatingService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'content_ratings') {
            $status = $this->getQueryParam('status', '');
            $keyword = $this->getQueryParam('keyword', '');
            $result = $this->contentRatingService->getList($status, $keyword);
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'content_ratings/active') {
            $result = $this->contentRatingService->getActive();
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2) {
            validateInt($parts[1], '分级ID');
            $result = $this->contentRatingService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'content_ratings') {
            $data = [
                'code' => $_POST['code'] ?? '',
                'label' => $_POST['label'] ?? '',
                'description' => $_POST['description'] ?? '',
                'min_age' => $_POST['min_age'] ?? '',
                'color_hex' => $_POST['color_hex'] ?? '#6366f1',
                'status' => $_POST['status'] ?? 1,
                'sort_order' => $_POST['sort_order'] ?? 0,
            ];
            $id = $this->contentRatingService->create($data);
            Response::success(['id' => $id], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '分级ID');
            $data = [
                'code' => $_POST['code'] ?? '',
                'label' => $_POST['label'] ?? '',
                'description' => $_POST['description'] ?? '',
                'min_age' => $_POST['min_age'] ?? '',
                'color_hex' => $_POST['color_hex'] ?? '',
                'status' => $_POST['status'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? '',
            ];
            $this->contentRatingService->update(intval($parts[1]), $data);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '分级ID');
            $this->contentRatingService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
            validateInt($parts[1], '分级ID');
            $status = $_POST['status'] ?? '';
            $message = $this->contentRatingService->updateStatus(intval($parts[1]), $status);
            Response::success(null, $message);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
