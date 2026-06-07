<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ReportService;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function handle(Request $request, array $tokenData): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();

        if ($path === 'reports/snapshot' && $method === 'GET') {
            $result = $this->reportService->getDailyStatsSnapshot();
            Response::success($result);
        } elseif ($path === 'reports/snapshot' && $method === 'POST') {
            $result = $this->reportService->createSnapshotToday($tokenData);
            Response::success($result, '快照生成成功');
        } else {
            Response::error('接口不存在', 404);
        }
    }
}
