<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\UploadService;

class UploadController extends Controller
{
    private UploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = new UploadService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();

        if ($method === 'POST' && $path === 'upload/cover') {
            $url = $this->uploadService->uploadCover($tokenData);
            Response::success(['url' => $url], '上传成功');
        } elseif ($method === 'POST' && $path === 'upload/media') {
            $url = $this->uploadService->uploadMedia($tokenData);
            Response::success(['url' => $url], '上传成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
