<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\SubtitleService;

class SubtitleController extends Controller
{
    private SubtitleService $subtitleService;

    public function __construct()
    {
        $this->subtitleService = new SubtitleService();
    }

    public function handle(Request $request): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'subtitles') {
            $videoId = $this->getQueryParam('video_id', '');
            if (empty($videoId)) {
                Response::error('影片ID不能为空');
            }
            validateInt($videoId, '影片ID');
            $result = $this->subtitleService->getList(intval($videoId));
            Response::success($result);
        } elseif ($method === 'POST' && $path === 'subtitles') {
            $videoId = $_POST['video_id'] ?? '';
            $language = $_POST['language'] ?? '';
            if (empty($videoId)) {
                Response::error('影片ID不能为空');
            }
            validateInt($videoId, '影片ID');
            $result = $this->subtitleService->upload(intval($videoId), $language);
            Response::success($result, '上传成功');
        } elseif ($method === 'GET' && count($parts) === 3 && $parts[2] === 'preview') {
            validateInt($parts[1], '字幕ID');
            $result = $this->subtitleService->getPreview(intval($parts[1]));
            Response::success($result);
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '字幕ID');
            $this->subtitleService->delete(intval($parts[1]));
            Response::success(null, '删除成功');
        } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
            validateInt($parts[1], '字幕ID');
            $status = $_POST['status'] ?? '';
            $message = $this->subtitleService->updateStatus(intval($parts[1]), $status);
            Response::success(null, $message);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
