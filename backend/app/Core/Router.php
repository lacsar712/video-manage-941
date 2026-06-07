<?php

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AppController;
use App\Controllers\VideoController;
use App\Controllers\SourceController;
use App\Controllers\ScheduledTaskController;
use App\Controllers\UploadController;
use App\Controllers\MediaController;
use App\Controllers\ClientReleaseController;
use App\Controllers\CollectionController;
use App\Controllers\SubtitleController;
use App\Controllers\ContentRatingController;
use App\Controllers\RecommendSlotController;
use App\Controllers\AnnouncementController;
use App\Controllers\ReportController;
use App\Helpers\TokenHelper;

class Router
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function dispatch(): void
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        if ($this->isPublicRoute($path, $method)) {
            $this->dispatchPublic($path, $method);
            return;
        }

        $tokenData = TokenHelper::validate();
        $this->dispatchProtected($path, $method, $tokenData);
    }

    private function isPublicRoute(string $path, string $method): bool
    {
        if (($path === 'login' || $path === 'admin/login') && $method === 'POST') {
            return true;
        }
        if (strpos($path, 'app/') === 0) {
            return true;
        }
        return false;
    }

    private function dispatchPublic(string $path, string $method): void
    {
        if (($path === 'login' || $path === 'admin/login') && $method === 'POST') {
            (new AdminController())->login();
            return;
        }

        if (strpos($path, 'app/') === 0) {
            (new AppController())->handle($this->request);
            return;
        }

        Response::error('接口不存在', 404);
    }

    private function dispatchProtected(string $path, string $method, array $tokenData): void
    {
        $parts = $this->request->getPathParts();
        $prefix = $parts[0] ?? '';

        switch ($prefix) {
            case 'admin':
                (new AdminController())->handle($this->request, $tokenData);
                break;
            case 'videos':
                (new VideoController())->handle($this->request);
                break;
            case 'sources':
                (new SourceController())->handle($this->request);
                break;
            case 'scheduled_tasks':
                (new ScheduledTaskController())->handle($this->request, $tokenData);
                break;
            case 'upload':
                (new UploadController())->handle($this->request, $tokenData);
                break;
            case 'media':
                (new MediaController())->handle($this->request, $tokenData);
                break;
            case 'client_releases':
                (new ClientReleaseController())->handle($this->request, $tokenData);
                break;
            case 'collections':
                (new CollectionController())->handle($this->request);
                break;
            case 'subtitles':
                (new SubtitleController())->handle($this->request);
                break;
            case 'content_ratings':
                (new ContentRatingController())->handle($this->request, $tokenData);
                break;
            case 'recommend_slots':
                (new RecommendSlotController())->handle($this->request, $tokenData);
                break;
            case 'announcements':
                (new AnnouncementController())->handle($this->request, $tokenData);
                break;
            case 'reports':
                (new ReportController())->handle($this->request, $tokenData);
                break;
            default:
                Response::error('接口不存在', 404);
        }
    }
}
