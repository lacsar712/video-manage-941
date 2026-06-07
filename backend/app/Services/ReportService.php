<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;
use App\Helpers\LogHelper;

class ReportService extends Service
{
    public function generateSnapshotForDate(string $statDate): array
    {
        try {
            $videoTotalStmt = $this->db->prepare("SELECT COUNT(*) FROM video");
            $videoTotalStmt->execute();
            $videoTotal = (int)$videoTotalStmt->fetchColumn();

            $videoPublishedStmt = $this->db->prepare("SELECT COUNT(*) FROM video WHERE status = 1");
            $videoPublishedStmt->execute();
            $videoPublished = (int)$videoPublishedStmt->fetchColumn();

            $sourceTotalStmt = $this->db->prepare("SELECT COUNT(*) FROM video_source");
            $sourceTotalStmt->execute();
            $sourceTotal = (int)$sourceTotalStmt->fetchColumn();

            $newVideosStmt = $this->db->prepare("SELECT COUNT(*) FROM video WHERE DATE(created_at) = ?");
            $newVideosStmt->execute([$statDate]);
            $newVideos = (int)$newVideosStmt->fetchColumn();

            $stmt = $this->db->prepare("
                INSERT INTO daily_stats_snapshot (stat_date, video_total, video_published, source_total, new_videos, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    video_total = VALUES(video_total),
                    video_published = VALUES(video_published),
                    source_total = VALUES(source_total),
                    new_videos = VALUES(new_videos),
                    created_at = NOW()
            ");
            $stmt->execute([$statDate, $videoTotal, $videoPublished, $sourceTotal, $newVideos]);

            return [
                'stat_date' => $statDate,
                'video_total' => $videoTotal,
                'video_published' => $videoPublished,
                'source_total' => $sourceTotal,
                'new_videos' => $newVideos
            ];
        } catch (\Exception $e) {
            throw new \Exception('生成快照失败: ' . $e->getMessage());
        }
    }

    public function getDailyStatsSnapshot(): array
    {
        try {
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            if ($days < 1) $days = 30;
            if ($days > 365) $days = 365;

            $startDate = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));
            $endDate = date('Y-m-d');

            $stmt = $this->db->prepare("
                SELECT stat_date, video_total, video_published, source_total, new_videos, created_at
                FROM daily_stats_snapshot
                WHERE stat_date BETWEEN ? AND ?
                ORDER BY stat_date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            $rows = $stmt->fetchAll();

            $dataMap = [];
            foreach ($rows as $row) {
                $dataMap[$row['stat_date']] = $row;
            }

            $result = [];
            $current = strtotime($startDate);
            $end = strtotime($endDate);
            $prevVideoTotal = 0;
            $prevVideoPublished = 0;
            $prevSourceTotal = 0;

            foreach ($rows as $row) {
                $prevVideoTotal = $row['video_total'];
                $prevVideoPublished = $row['video_published'];
                $prevSourceTotal = $row['source_total'];
            }

            while ($current <= $end) {
                $dateStr = date('Y-m-d', $current);
                if (isset($dataMap[$dateStr])) {
                    $row = $dataMap[$dateStr];
                    $prevVideoTotal = $row['video_total'];
                    $prevVideoPublished = $row['video_published'];
                    $prevSourceTotal = $row['source_total'];
                    $result[] = [
                        'stat_date' => $row['stat_date'],
                        'video_total' => (int)$row['video_total'],
                        'video_published' => (int)$row['video_published'],
                        'source_total' => (int)$row['source_total'],
                        'new_videos' => (int)$row['new_videos'],
                        'source_increment' => 0,
                        'has_data' => true
                    ];
                } else {
                    $result[] = [
                        'stat_date' => $dateStr,
                        'video_total' => (int)$prevVideoTotal,
                        'video_published' => (int)$prevVideoPublished,
                        'source_total' => (int)$prevSourceTotal,
                        'new_videos' => 0,
                        'source_increment' => 0,
                        'has_data' => false
                    ];
                }
                $current = strtotime('+1 day', $current);
            }

            for ($i = 0; $i < count($result); $i++) {
                if ($i === 0) {
                    $result[$i]['source_increment'] = 0;
                } else {
                    $result[$i]['source_increment'] = $result[$i]['source_total'] - $result[$i - 1]['source_total'];
                }
            }

            return $result;
        } catch (\Exception $e) {
            Response::error('获取快照数据失败: ' . $e->getMessage());
        }
    }

    public function createSnapshotToday(array $tokenData): array
    {
        try {
            $statDate = date('Y-m-d');
            $snapshot = $this->generateSnapshotForDate($statDate);

            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'reports',
                'generate_snapshot',
                'daily_stats_snapshot',
                null,
                "手动生成 {$statDate} 数据快照",
                'success'
            );

            return $snapshot;
        } catch (\Exception $e) {
            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'reports',
                'generate_snapshot',
                'daily_stats_snapshot',
                null,
                "手动生成数据快照失败",
                'failed',
                $e->getMessage()
            );
            Response::error($e->getMessage());
        }
    }
}
