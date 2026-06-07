<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AppService;

class AppController extends Controller
{
    private AppService $appService;

    public function __construct()
    {
        $this->appService = new AppService();
    }

    public function handle(Request $request): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $parts = $request->getPathParts();

        if ($method === 'GET' && $path === 'app/videos') {
            $page = intval($this->getQueryParam('page', 1));
            $pageSize = intval($this->getQueryParam('page_size', 10));
            $result = $this->appService->getVideoList($page, $pageSize);
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 3 && $parts[1] === 'videos') {
            validateInt($parts[2], '影片ID');
            $result = $this->appService->getVideoDetail(intval($parts[2]));
            Response::success($result);
        } elseif ($method === 'GET' && count($parts) === 4 && $parts[1] === 'videos' && $parts[3] === 'sources') {
            validateInt($parts[2], '影片ID');
            $result = $this->appService->getVideoSources(intval($parts[2]));
            Response::success($result);
        } elseif ($method === 'GET' && $path === 'app/version/check') {
            $platform = $this->getQueryParam('platform', '');
            $versionCode = intval($this->getQueryParam('version_code', 0));
            $result = $this->appService->checkVersion($platform, $versionCode);
            Response::success($result);
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
