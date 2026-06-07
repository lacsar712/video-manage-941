<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\MediaService;

class MediaController extends Controller
{
    private MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'media') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 12));
            $keyword = $this->getQueryParam('keyword', '');
            $result = $this->mediaService->getList($page, $pageSize, $keyword);
            Response::success($result);
        } elseif ($method === 'DELETE' && count($parts) === 2) {
            validateInt($parts[1], '媒资ID');
            $this->mediaService->delete(intval($parts[1]), $tokenData);
            Response::success(null, '删除成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
