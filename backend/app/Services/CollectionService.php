<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class CollectionService extends Service
{
    public function getList(int $page, int $pageSize, string $status, string $keyword): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $where = [];
            $params = [];

            if ($status !== '') {
                $where[] = "vc.status = ?";
                $params[] = $status;
            }

            if ($keyword !== '') {
                $where[] = "vc.title LIKE ?";
                $params[] = "%{$keyword}%";
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM video_collection vc {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT vc.id, vc.title, vc.cover_url, vc.description, vc.sort_order, vc.status,
                       vc.created_at, vc.updated_at,
                       COUNT(cv.id) as video_count
                FROM video_collection vc
                LEFT JOIN collection_video cv ON vc.id = cv.collection_id
                {$whereClause}
                GROUP BY vc.id
                ORDER BY vc.sort_order DESC, vc.id DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
                $item['video_count'] = intval($item['video_count']);
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
            $stmt = $this->db->prepare("SELECT * FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            $collection = $stmt->fetch();

            if (!$collection) {
                Response::error('合集不存在', 404);
            }

            $collection['created_at'] = formatDateTime($collection['created_at']);
            $collection['updated_at'] = formatDateTime($collection['updated_at']);

            $stmt = $this->db->prepare("
                SELECT v.id, v.title, v.cover_url, v.description, v.status,
                       cv.sort_order as cv_sort_order
                FROM collection_video cv
                JOIN video v ON cv.video_id = v.id
                WHERE cv.collection_id = ?
                ORDER BY cv.sort_order DESC, cv.id ASC
            ");
            $stmt->execute([$id]);
            $videos = $stmt->fetchAll();

            $collection['videos'] = $videos;
            $collection['video_count'] = count($videos);

            return $collection;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    private function parseVideoIds(mixed $videoIds): array
    {
        if ($videoIds === null || $videoIds === '') {
            return [];
        }
        if (is_array($videoIds)) {
            return array_filter(array_map('intval', $videoIds));
        }
        return array_filter(array_map('intval', explode(',', $videoIds)));
    }

    public function create(array $data): int
    {
        $title = $data['title'];
        $coverUrl = $data['cover_url'];
        $description = $data['description'];
        $sortOrder = intval($data['sort_order'] ?? 0);
        $status = $data['status'];
        $videoIds = $this->parseVideoIds($data['video_ids'] ?? '');

        validateRequired([
            'title' => '合集标题',
            'cover_url' => '合集封面'
        ], ['title' => $title, 'cover_url' => $coverUrl]);

        validateLength($title, 1, 200, '合集标题');
        if (!empty($description)) {
            validateLength($description, 0, 1000, '合集描述');
        }
        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO video_collection (title, cover_url, description, sort_order, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$title, $coverUrl, $description, $sortOrder, $status]);
            $collectionId = (int)$this->db->lastInsertId();

            if (!empty($videoIds)) {
                $orderIdx = count($videoIds);
                foreach ($videoIds as $vid) {
                    $stmt = $this->db->prepare("
                        INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$collectionId, $vid, $orderIdx]);
                    $orderIdx--;
                }
            }

            $this->db->commit();
            return $collectionId;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): void
    {
        $title = $data['title'];
        $coverUrl = $data['cover_url'];
        $description = $data['description'];
        $sortOrder = $data['sort_order'];
        $status = $data['status'];
        $videoIds = $data['video_ids'] ?? null;

        validateRequired([
            'title' => '合集标题',
            'cover_url' => '合集封面',
            'status' => '状态'
        ], ['title' => $title, 'cover_url' => $coverUrl, 'status' => $status]);

        validateLength($title, 1, 200, '合集标题');
        if (!empty($description)) {
            validateLength($description, 0, 1000, '合集描述');
        }
        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);
        $sortOrder = intval($sortOrder);

        $videoIdArr = null;
        if ($videoIds !== null) {
            $videoIdArr = $this->parseVideoIds($videoIds);
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE video_collection
                SET title = ?, cover_url = ?, description = ?, sort_order = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $coverUrl, $description, $sortOrder, $status, $id]);

            if ($videoIdArr !== null) {
                $stmt = $this->db->prepare("DELETE FROM collection_video WHERE collection_id = ?");
                $stmt->execute([$id]);

                if (!empty($videoIdArr)) {
                    $orderIdx = count($videoIdArr);
                    foreach ($videoIdArr as $vid) {
                        $stmt = $this->db->prepare("
                            INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$id, $vid, $orderIdx]);
                        $orderIdx--;
                    }
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM collection_video WHERE collection_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE video_collection SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            return $status == 1 ? '上架成功' : '下架成功';
        } catch (\Exception $e) {
            Response::error('操作失败：' . $e->getMessage());
        }
    }

    public function addVideos(int $id, mixed $videoIdsParam): void
    {
        if (empty($videoIdsParam)) {
            Response::error('请选择要添加的影片');
        }

        $videoIdArr = $this->parseVideoIds($videoIdsParam);

        if (empty($videoIdArr)) {
            Response::error('请选择要添加的影片');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM collection_video WHERE collection_id = ?");
            $stmt->execute([$id]);
            $maxOrder = intval($stmt->fetch()['max_order']);

            $this->db->beginTransaction();
            foreach ($videoIdArr as $vid) {
                $maxOrder++;
                $stmt = $this->db->prepare("
                    INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$id, $vid, $maxOrder]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function removeVideo(int $id, int $videoId): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM collection_video WHERE collection_id = ? AND video_id = ?");
            $stmt->execute([$id, $videoId]);
        } catch (\Exception $e) {
            Response::error('移除失败：' . $e->getMessage());
        }
    }

    public function updateVideoSort(int $id, array $videoOrders): void
    {
        if (empty($videoOrders) || !is_array($videoOrders)) {
            Response::error('排序数据不能为空');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM video_collection WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('合集不存在', 404);
            }

            $this->db->beginTransaction();
            foreach ($videoOrders as $item) {
                $videoId = intval($item['video_id'] ?? 0);
                $sortOrder = intval($item['sort_order'] ?? 0);
                if ($videoId > 0) {
                    $stmt = $this->db->prepare("
                        UPDATE collection_video SET sort_order = ?
                        WHERE collection_id = ? AND video_id = ?
                    ");
                    $stmt->execute([$sortOrder, $id, $videoId]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('排序更新失败：' . $e->getMessage());
        }
    }
}
