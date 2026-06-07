<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class AppService extends Service
{
    public function getVideoList(int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM video WHERE status = 1");
            $stmt->execute();
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT id, title, cover_url, description, created_at
                FROM video
                WHERE status = 1
                ORDER BY id DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute();
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
            }

            return [
                'list' => $list,
                'total' => intval($total),
                'page' => $page,
                'page_size' => $pageSize
            ];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getVideoDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, title, cover_url, description, created_at
                FROM video
                WHERE id = ? AND status = 1
            ");
            $stmt->execute([$id]);
            $video = $stmt->fetch();

            if (!$video) {
                Response::error('影片不存在或已下架', 404);
            }

            $video['created_at'] = formatDateTime($video['created_at']);
            return $video;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getVideoSources(int $id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, title FROM video WHERE id = ? AND status = 1");
            $stmt->execute([$id]);
            $video = $stmt->fetch();

            if (!$video) {
                Response::error('影片不存在或已下架', 404);
            }

            $stmt = $this->db->prepare("
                SELECT id, source_name, m3u8_url
                FROM video_source
                WHERE video_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$id]);
            $sources = $stmt->fetchAll();

            return [
                'video_id' => $video['id'],
                'video_title' => $video['title'],
                'sources' => $sources
            ];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function checkVersion(string $platform, int $versionCode): array
    {
        if (empty($platform)) {
            Response::error('平台参数不能为空');
        }
        if (!in_array($platform, ['android', 'ios'])) {
            Response::error('平台必须为 android 或 ios');
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, platform, version_name, version_code, download_url,
                       force_update, changelog, status, created_at
                FROM client_release
                WHERE platform = ? AND status = 1
                ORDER BY version_code DESC
                LIMIT 1
            ");
            $stmt->execute([$platform]);
            $latest = $stmt->fetch();

            if (!$latest) {
                return [
                    'has_update' => false,
                    'latest' => null
                ];
            }

            $latest['version_code'] = intval($latest['version_code']);
            $latest['force_update'] = intval($latest['force_update']);
            $latest['status'] = intval($latest['status']);
            $latest['created_at'] = formatDateTime($latest['created_at']);

            $hasUpdate = $versionCode > 0 && $latest['version_code'] > $versionCode;

            return [
                'has_update' => $hasUpdate,
                'latest' => $latest
            ];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }
}
