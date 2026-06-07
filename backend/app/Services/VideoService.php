<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class VideoService extends Service
{
    public function getList(int $page, int $pageSize, string $status, string $keyword, string $contentRatingCode, string $onlyUnrated): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $where = [];
            $params = [];

            if ($status !== '') {
                $where[] = "v.status = ?";
                $params[] = $status;
            }

            if ($keyword !== '') {
                $where[] = "v.title LIKE ?";
                $params[] = "%{$keyword}%";
            }

            if ($contentRatingCode !== '') {
                $where[] = "v.content_rating_code = ?";
                $params[] = $contentRatingCode;
            }

            if ($onlyUnrated === '1' || $onlyUnrated === 'true') {
                $where[] = "v.content_rating_code IS NULL OR v.content_rating_code = ''";
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM video v {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT v.id, v.title, v.cover_url, v.description, v.content_rating_code,
                       v.status, v.created_at, v.updated_at,
                       cr.label as content_rating_label, cr.color_hex as content_rating_color
                FROM video v
                LEFT JOIN content_rating cr ON v.content_rating_code = cr.code
                {$whereClause}
                ORDER BY v.id DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
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

    public function getDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT v.*, cr.label as content_rating_label, cr.color_hex as content_rating_color
                FROM video v
                LEFT JOIN content_rating cr ON v.content_rating_code = cr.code
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            $video = $stmt->fetch();

            if (!$video) {
                Response::error('影片不存在', 404);
            }

            $video['created_at'] = formatDateTime($video['created_at']);
            $video['updated_at'] = formatDateTime($video['updated_at']);

            return $video;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(string $title, string $coverUrl, string $description, string $contentRatingCode, mixed $status): int
    {
        validateRequired(['title' => '影片标题'], ['title' => $title]);
        validateLength($title, 1, 200, '影片标题');

        if (!empty($description)) {
            validateLength($description, 0, 1000, '影片描述');
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);

        if ($contentRatingCode === '') {
            $contentRatingCode = null;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO video (title, cover_url, description, content_rating_code, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$title, $coverUrl, $description, $contentRatingCode, $status]);
            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, string $title, string $coverUrl, string $description, string $contentRatingCode, mixed $status): void
    {
        validateRequired([
            'title' => '影片标题',
            'status' => '状态'
        ], ['title' => $title, 'status' => $status]);

        validateLength($title, 1, 200, '影片标题');

        if (!empty($description)) {
            validateLength($description, 0, 1000, '影片描述');
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);

        if ($contentRatingCode === '') {
            $contentRatingCode = null;
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('影片不存在', 404);
            }

            $stmt = $this->db->prepare("
                UPDATE video
                SET title = ?, cover_url = ?, description = ?, content_rating_code = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $coverUrl, $description, $contentRatingCode, $status, $id]);
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->db->beginTransaction();

            try {
                $stmt = $this->db->prepare("SELECT id FROM video WHERE id = ?");
                $stmt->execute([$id]);
                if (!$stmt->fetch()) {
                    Response::error('影片不存在', 404);
                }

                $stmt = $this->db->prepare("DELETE FROM video_source WHERE video_id = ?");
                $stmt->execute([$id]);

                $stmt = $this->db->prepare("DELETE FROM video WHERE id = ?");
                $stmt->execute([$id]);

                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, mixed $status): string
    {
        if ($status === '') {
            Response::error('状态不能为空');
        }

        if (!in_array($status, ['0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('影片不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            return $status == 1 ? '上架成功' : '下架成功';
        } catch (\Exception $e) {
            Response::error('操作失败：' . $e->getMessage());
        }
    }
}
