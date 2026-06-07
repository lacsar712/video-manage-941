<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class RecommendSlotService extends Service
{
    public function getList(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT rs.id, rs.slot_key, rs.title, rs.max_items, rs.status, rs.sort_order,
                       rs.created_at, rs.updated_at,
                       COUNT(ri.id) as item_count
                FROM recommend_slot rs
                LEFT JOIN recommend_item ri ON rs.id = ri.slot_id
                GROUP BY rs.id
                ORDER BY rs.sort_order DESC, rs.id ASC
            ");
            $stmt->execute();
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['id'] = intval($item['id']);
                $item['max_items'] = intval($item['max_items']);
                $item['status'] = intval($item['status']);
                $item['sort_order'] = intval($item['sort_order']);
                $item['item_count'] = intval($item['item_count']);
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
            }

            return $list;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM recommend_slot WHERE id = ?");
            $stmt->execute([$id]);
            $slot = $stmt->fetch();

            if (!$slot) {
                Response::error('槽位不存在', 404);
            }

            $slot['id'] = intval($slot['id']);
            $slot['max_items'] = intval($slot['max_items']);
            $slot['status'] = intval($slot['status']);
            $slot['sort_order'] = intval($slot['sort_order']);
            $slot['created_at'] = formatDateTime($slot['created_at']);
            $slot['updated_at'] = formatDateTime($slot['updated_at']);

            $stmt = $this->db->prepare("
                SELECT ri.id as item_id, ri.sort_order as ri_sort_order,
                       v.id, v.title, v.cover_url, v.description, v.status
                FROM recommend_item ri
                JOIN video v ON ri.video_id = v.id
                WHERE ri.slot_id = ?
                ORDER BY ri.sort_order DESC, ri.id ASC
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            foreach ($items as &$item) {
                $item['item_id'] = intval($item['item_id']);
                $item['id'] = intval($item['id']);
                $item['ri_sort_order'] = intval($item['ri_sort_order']);
                $item['status'] = intval($item['status']);
            }

            $slot['items'] = $items;
            $slot['item_count'] = count($items);

            return $slot;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        $slotKey = $data['slot_key'];
        $title = $data['title'];
        $maxItems = intval($data['max_items']);
        $status = $data['status'];
        $sortOrder = intval($data['sort_order'] ?? 0);

        validateRequired([
            'slot_key' => '槽位标识',
            'title' => '槽位标题',
            'max_items' => '最大条目数'
        ], ['slot_key' => $slotKey, 'title' => $title, 'max_items' => $maxItems]);

        validateLength($slotKey, 1, 50, '槽位标识');
        validateLength($title, 1, 100, '槽位标题');

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);
        if ($maxItems < 1 || $maxItems > 100) {
            Response::error('最大条目数必须在 1-100 之间');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE slot_key = ?");
            $stmt->execute([$slotKey]);
            if ($stmt->fetch()) {
                Response::error('槽位标识已存在');
            }

            $stmt = $this->db->prepare("
                INSERT INTO recommend_slot (slot_key, title, max_items, status, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$slotKey, $title, $maxItems, $status, $sortOrder]);
            return intval($this->db->lastInsertId());
        } catch (\Exception $e) {
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): void
    {
        $slotKey = $data['slot_key'];
        $title = $data['title'];
        $maxItems = $data['max_items'];
        $status = $data['status'];
        $sortOrder = $data['sort_order'];

        validateRequired([
            'slot_key' => '槽位标识',
            'title' => '槽位标题',
            'max_items' => '最大条目数',
            'status' => '状态'
        ], ['slot_key' => $slotKey, 'title' => $title, 'max_items' => $maxItems, 'status' => $status]);

        validateLength($slotKey, 1, 50, '槽位标识');
        validateLength($title, 1, 100, '槽位标题');

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);
        $maxItems = intval($maxItems);
        $sortOrder = intval($sortOrder);

        if ($maxItems < 1 || $maxItems > 100) {
            Response::error('最大条目数必须在 1-100 之间');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('槽位不存在', 404);
            }

            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE slot_key = ? AND id != ?");
            $stmt->execute([$slotKey, $id]);
            if ($stmt->fetch()) {
                Response::error('槽位标识已存在');
            }

            $stmt = $this->db->prepare("
                UPDATE recommend_slot
                SET slot_key = ?, title = ?, max_items = ?, status = ?, sort_order = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$slotKey, $title, $maxItems, $status, $sortOrder, $id]);
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('槽位不存在', 404);
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM recommend_item WHERE slot_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM recommend_slot WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('删除失败：' . $e->getMessage());
        }
    }

    private function parseVideoIds(mixed $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }
        if (is_array($videoIds)) {
            return array_filter(array_map('intval', $videoIds));
        }
        return array_filter(array_map('intval', explode(',', $videoIds)));
    }

    public function addVideos(int $slotId, mixed $videoIdsParam): void
    {
        $videoIdArr = $this->parseVideoIds($videoIdsParam);

        if (empty($videoIdArr)) {
            Response::error('请选择要添加的影片');
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM recommend_slot WHERE id = ?");
            $stmt->execute([$slotId]);
            $slot = $stmt->fetch();
            if (!$slot) {
                Response::error('槽位不存在', 404);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM recommend_item WHERE slot_id = ?");
            $stmt->execute([$slotId]);
            $currentCount = intval($stmt->fetch()['cnt']);

            if ($currentCount + count($videoIdArr) > intval($slot['max_items'])) {
                Response::error('超出最大条目数限制（最多 ' . $slot['max_items'] . ' 条）');
            }

            $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM recommend_item WHERE slot_id = ?");
            $stmt->execute([$slotId]);
            $maxOrder = intval($stmt->fetch()['max_order']);

            $this->db->beginTransaction();
            foreach ($videoIdArr as $vid) {
                $stmt = $this->db->prepare("SELECT id, status FROM video WHERE id = ?");
                $stmt->execute([$vid]);
                $video = $stmt->fetch();
                if (!$video) {
                    continue;
                }
                if (intval($video['status']) != 1) {
                    continue;
                }
                $maxOrder++;
                $stmt = $this->db->prepare("
                    INSERT IGNORE INTO recommend_item (slot_id, video_id, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$slotId, $vid, $maxOrder]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function removeVideo(int $slotId, int $videoId): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
            $stmt->execute([$slotId]);
            if (!$stmt->fetch()) {
                Response::error('槽位不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM recommend_item WHERE slot_id = ? AND video_id = ?");
            $stmt->execute([$slotId, $videoId]);
        } catch (\Exception $e) {
            Response::error('移除失败：' . $e->getMessage());
        }
    }

    public function updateItemSort(int $slotId, array $videoOrders): void
    {
        if (empty($videoOrders) || !is_array($videoOrders)) {
            Response::error('排序数据不能为空');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
            $stmt->execute([$slotId]);
            if (!$stmt->fetch()) {
                Response::error('槽位不存在', 404);
            }

            $this->db->beginTransaction();
            foreach ($videoOrders as $item) {
                $videoId = intval($item['video_id'] ?? 0);
                $sortOrder = intval($item['sort_order'] ?? 0);
                if ($videoId > 0) {
                    $stmt = $this->db->prepare("
                        UPDATE recommend_item SET sort_order = ?
                        WHERE slot_id = ? AND video_id = ?
                    ");
                    $stmt->execute([$sortOrder, $slotId, $videoId]);
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

    public function getPreview(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, slot_key, title, max_items, status, sort_order
                FROM recommend_slot
                WHERE status = 1
                ORDER BY sort_order DESC, id ASC
            ");
            $stmt->execute();
            $slots = $stmt->fetchAll();

            $result = [];
            foreach ($slots as $slot) {
                $slotId = intval($slot['id']);
                $stmt = $this->db->prepare("
                    SELECT v.id, v.title, v.cover_url, v.description
                    FROM recommend_item ri
                    JOIN video v ON ri.video_id = v.id
                    WHERE ri.slot_id = ? AND v.status = 1
                    ORDER BY ri.sort_order DESC, ri.id ASC
                    LIMIT ?
                ");
                $stmt->execute([$slotId, intval($slot['max_items'])]);
                $items = $stmt->fetchAll();

                foreach ($items as &$item) {
                    $item['id'] = intval($item['id']);
                }

                $result[] = [
                    'slot_key' => $slot['slot_key'],
                    'title' => $slot['title'],
                    'max_items' => intval($slot['max_items']),
                    'sort_order' => intval($slot['sort_order']),
                    'items' => $items,
                    'item_count' => count($items)
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }
}
