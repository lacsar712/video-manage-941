<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\RecommendSlotService;

class RecommendSlotController extends Controller
{
    private RecommendSlotService $recommendSlotService;

    public function __construct()
    {
        $this->recommendSlotService = new RecommendSlotService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'recommend_slots') {
            $result = $this->recommendSlotService->getList();
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'recommend_slots/preview') {
            $result = $this->recommendSlotService->getPreview();
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 2) {
            validateInt($parts[1], '槽位ID');
            $result = $this->recommendSlotService->getDetail(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'recommend_slots') {
            $data = [
                'slot_key' => $_POST['slot_key'] ?? '',
                'title' => $_POST['title'] ?? '',
                'max_items' => intval($_POST['max_items'] ?? 10),
                'status' => $_POST['status'] ?? 1,
                'sort_order' => intval($_POST['sort_order'] ?? 0),
            ];
            $slotId = $this->recommendSlotService->create($data);
            Response::success(['id' => $slotId], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '槽位ID');
            $data = [
                'slot_key' => $_POST['slot_key'] ?? '',
                'title' => $_POST['title'] ?? '',
                'max_items' => $_POST['max_items'] ?? '',
                'status' => $_POST['status'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? '',
            ];
            $this->recommendSlotService->update(intval($parts[1]), $data);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '槽位ID');
            $this->recommendSlotService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'videos') {
            validateInt($parts[1], '槽位ID');
            $input = $this->getJsonInput();
            $videoIds = $input['video_ids'] ?? ($_POST['video_ids'] ?? '');
            $this->recommendSlotService->addVideos(intval($parts[1]), $videoIds);
            Response::success(null, '添加成功');
        } elseif ($method === 'DELETE' && count($parts) === 4 && $parts[2] === 'videos') {
            validateInt($parts[1], '槽位ID');
            validateInt($parts[3], '影片ID');
            $this->recommendSlotService->removeVideo(intval($parts[1]), intval($parts[3]));
            Response::success(null, '移除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'sort') {
            validateInt($parts[1], '槽位ID');
            $input = $this->getJsonInput();
            $videoOrders = $input['video_orders'] ?? [];
            $this->recommendSlotService->updateItemSort(intval($parts[1]), $videoOrders);
            Response::success(null, '排序更新成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
