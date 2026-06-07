<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\SourceService;

class SourceController extends Controller
{
    private SourceService $sourceService;

    public function __construct()
    {
        $this->sourceService = new SourceService();
    }

    public function handle(Request $request): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'sources') {
            $videoId = $this->getQueryParam('video_id', '');
            if (empty($videoId)) {
                Response::error('影片ID不能为空');
            }
            validateInt($videoId, '影片ID');

            $result = $this->sourceService->getList(intval($videoId));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'sources') {
            $videoId = $_POST['video_id'] ?? '';
            $sourceName = $_POST['source_name'] ?? '';
            $m3u8Url = $_POST['m3u8_url'] ?? '';

            if (empty($videoId)) {
                Response::error('影片ID不能为空');
            }
            validateInt($videoId, '影片ID');

            $sourceId = $this->sourceService->create(intval($videoId), $sourceName, $m3u8Url);
            Response::success(['id' => $sourceId], '添加成功');
        } elseif ($method === 'POST' && count($parts) === 2) {
            validateInt($parts[1], '播放源ID');
            $sourceName = $_POST['source_name'] ?? '';
            $m3u8Url = $_POST['m3u8_url'] ?? '';

            $this->sourceService->update(intval($parts[1]), $sourceName, $m3u8Url);
            Response::success(null, '更新成功');
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '播放源ID');
            $this->sourceService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
