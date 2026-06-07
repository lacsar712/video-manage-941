<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\CollectionService;

class CollectionController extends Controller
{
    private CollectionService $collectionService;

    public function __construct()
    {
        $this->collectionService = new CollectionService();
    }

    public function handle(Request $request): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'collections') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $status = $this->getQueryParam('status', '');
            $keyword = $this->getQueryParam('keyword', '');
            $result = $this->collectionService->getList($page, $pageSize, $status, $keyword);
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2) {
            validateInt($parts[1], '合集ID');
            $result = $this->collectionService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'collections') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'cover_url' => $_POST['cover_url'] ?? '',
                'description' => $_POST['description'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? 0,
                'status' => $_POST['status'] ?? 1,
                'video_ids' => $_POST['video_ids'] ?? '',
            ];
            $collectionId = $this->collectionService->create($data);
            Response::success(['id' => $collectionId], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '合集ID');
            $data = [
                'title' => $_POST['title'] ?? '',
                'cover_url' => $_POST['cover_url'] ?? '',
                'description' => $_POST['description'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? '',
                'status' => $_POST['status'] ?? '',
                'video_ids' => $_POST['video_ids'] ?? null,
            ];
            $this->collectionService->update(intval($parts[1]), $data);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '合集ID');
            $this->collectionService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
            validateInt($parts[1], '合集ID');
            $status = $_POST['status'] ?? '';
            $message = $this->collectionService->updateStatus(intval($parts[1]), $status);
            Response::success(null, $message);
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'videos') {
            validateInt($parts[1], '合集ID');
            $input = $this->getJsonInput();
            $videoIds = $input['video_ids'] ?? ($_POST['video_ids'] ?? '');
            $this->collectionService->addVideos(intval($parts[1]), $videoIds);
            Response::success(null, '添加成功');
        } elseif ($method === 'DELETE' && count($parts) === 4 && $parts[2] === 'videos') {
            validateInt($parts[1], '合集ID');
            validateInt($parts[3], '影片ID');
            $this->collectionService->removeVideo(intval($parts[1]), intval($parts[3]));
            Response::success(null, '移除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'sort') {
            validateInt($parts[1], '合集ID');
            $input = $this->getJsonInput();
            $videoOrders = $input['video_orders'] ?? [];
            $this->collectionService->updateVideoSort(intval($parts[1]), $videoOrders);
            Response::success(null, '排序更新成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
