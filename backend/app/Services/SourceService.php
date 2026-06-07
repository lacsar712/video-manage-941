<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class SourceService extends Service
{
    public function getList(int $videoId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, title FROM video WHERE id = ?");
            $stmt->execute([$videoId]);
            $video = $stmt->fetch();

            if (!$video) {
                Response::error('影片不存在', 404);
            }

            $stmt = $this->db->prepare("
                SELECT id, video_id, source_name, m3u8_url, created_at
                FROM video_source
                WHERE video_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$videoId]);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
            }

            return [
                'video' => $video,
                'list' => $list
            ];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(int $videoId, string $sourceName, string $m3u8Url): int
    {
        validateRequired([
            'video_id' => '影片ID',
            'source_name' => '线路名称',
            'm3u8_url' => 'M3U8地址'
        ], ['video_id' => $videoId, 'source_name' => $sourceName, 'm3u8_url' => $m3u8Url]);

        validateInt($videoId, '影片ID');
        validateLength($sourceName, 1, 50, '线路名称');
        validateUrl($m3u8Url, 'M3U8地址');

        if (!preg_match('/\.m3u8$/i', $m3u8Url)) {
            Response::error('M3U8地址必须以.m3u8结尾');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$videoId]);
            if (!$stmt->fetch()) {
                Response::error('影片不存在', 404);
            }

            $stmt = $this->db->prepare("
                INSERT INTO video_source (video_id, source_name, m3u8_url, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$videoId, $sourceName, $m3u8Url]);
            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, string $sourceName, string $m3u8Url): void
    {
        validateRequired([
            'source_name' => '线路名称',
            'm3u8_url' => 'M3U8地址'
        ], ['source_name' => $sourceName, 'm3u8_url' => $m3u8Url]);

        validateLength($sourceName, 1, 50, '线路名称');
        validateUrl($m3u8Url, 'M3U8地址');

        if (!preg_match('/\.m3u8$/i', $m3u8Url)) {
            Response::error('M3U8地址必须以.m3u8结尾');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video_source WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('播放源不存在', 404);
            }

            $stmt = $this->db->prepare("
                UPDATE video_source
                SET source_name = ?, m3u8_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$sourceName, $m3u8Url, $id]);
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM video_source WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('播放源不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM video_source WHERE id = ?");
            $stmt->execute([$id]);
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }
}
