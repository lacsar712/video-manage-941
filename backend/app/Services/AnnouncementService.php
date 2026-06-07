<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;
use App\Helpers\LogHelper;

class AnnouncementService extends Service
{
    public function getList(int $page, int $pageSize, string $status, string $type, string $keyword): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $where = [];
            $params = [];

            if ($status !== '') {
                $where[] = "a.status = ?";
                $params[] = $status;
            }

            if ($type !== '') {
                $where[] = "a.type = ?";
                $params[] = $type;
            }

            if ($keyword !== '') {
                $where[] = "(a.title LIKE ? OR a.content LIKE ?)";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM announcement a {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT a.*, au.username as creator_name
                FROM announcement a
                LEFT JOIN admin_user au ON a.created_by = au.id
                {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
                $item['start_at'] = formatDateTime($item['start_at']);
                $item['end_at'] = formatDateTime($item['end_at']);
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

    public function getActive(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM announcement
                WHERE status = 1 AND start_at <= NOW() AND end_at >= NOW()
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
                $item['start_at'] = formatDateTime($item['start_at']);
                $item['end_at'] = formatDateTime($item['end_at']);
            }

            return $list;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, au.username as creator_name
                FROM announcement a
                LEFT JOIN admin_user au ON a.created_by = au.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) {
                Response::error('公告不存在', 404);
            }

            $item['created_at'] = formatDateTime($item['created_at']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['start_at'] = formatDateTime($item['start_at']);
            $item['end_at'] = formatDateTime($item['end_at']);

            return $item;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(array $data, array $tokenData): int
    {
        $title = $data['title'];
        $content = $data['content'];
        $type = $data['type'];
        $startAt = $data['start_at'];
        $endAt = $data['end_at'];
        $status = $data['status'];

        validateRequired([
            'title' => '公告标题',
            'content' => '公告内容',
            'start_at' => '生效开始时间',
            'end_at' => '生效结束时间'
        ], [
            'title' => $title,
            'content' => $content,
            'start_at' => $startAt,
            'end_at' => $endAt
        ]);

        validateLength($title, 1, 200, '公告标题');

        if (!in_array($type, ['maintenance', 'update'])) {
            Response::error('公告类型必须为 maintenance 或 update');
        }

        $startTimestamp = strtotime($startAt);
        $endTimestamp = strtotime($endAt);
        if ($startTimestamp === false) {
            Response::error('生效开始时间格式不正确');
        }
        if ($endTimestamp === false) {
            Response::error('生效结束时间格式不正确');
        }
        if ($endTimestamp <= $startTimestamp) {
            Response::error('生效结束时间必须晚于开始时间');
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $startAtFormatted = date('Y-m-d H:i:s', $startTimestamp);
            $endAtFormatted = date('Y-m-d H:i:s', $endTimestamp);

            $stmt = $this->db->prepare("
                INSERT INTO announcement (title, content, type, start_at, end_at, status, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$title, $content, $type, $startAtFormatted, $endAtFormatted, $status, $tokenData['admin_id']]);

            $announcementId = (int)$this->db->lastInsertId();

            $typeText = $type === 'maintenance' ? '维护' : '更新';
            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'announcement',
                'create',
                'announcement',
                $announcementId,
                "创建{$typeText}公告：【{$title}】，生效时间：{$startAtFormatted} 至 {$endAtFormatted}"
            );

            return $announcementId;
        } catch (\Exception $e) {
            Response::error('创建失败：' . $e->getMessage());
        }
    }

    public function update(int $id, array $data, array $tokenData): void
    {
        $title = $data['title'];
        $content = $data['content'];
        $type = $data['type'];
        $startAt = $data['start_at'];
        $endAt = $data['end_at'];
        $status = $data['status'];

        validateRequired([
            'title' => '公告标题',
            'content' => '公告内容',
            'start_at' => '生效开始时间',
            'end_at' => '生效结束时间'
        ], [
            'title' => $title,
            'content' => $content,
            'start_at' => $startAt,
            'end_at' => $endAt
        ]);

        validateLength($title, 1, 200, '公告标题');

        if (!in_array($type, ['maintenance', 'update'])) {
            Response::error('公告类型必须为 maintenance 或 update');
        }

        $startTimestamp = strtotime($startAt);
        $endTimestamp = strtotime($endAt);
        if ($startTimestamp === false) {
            Response::error('生效开始时间格式不正确');
        }
        if ($endTimestamp === false) {
            Response::error('生效结束时间格式不正确');
        }
        if ($endTimestamp <= $startTimestamp) {
            Response::error('生效结束时间必须晚于开始时间');
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM announcement WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('公告不存在', 404);
            }

            $startAtFormatted = date('Y-m-d H:i:s', $startTimestamp);
            $endAtFormatted = date('Y-m-d H:i:s', $endTimestamp);

            $stmt = $this->db->prepare("
                UPDATE announcement
                SET title = ?, content = ?, type = ?, start_at = ?, end_at = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $content, $type, $startAtFormatted, $endAtFormatted, $status, $id]);

            $typeText = $type === 'maintenance' ? '维护' : '更新';
            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'announcement',
                'update',
                'announcement',
                $id,
                "更新{$typeText}公告：【{$title}】，生效时间：{$startAtFormatted} 至 {$endAtFormatted}"
            );
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id, array $tokenData): void
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM announcement WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('公告不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM announcement WHERE id = ?");
            $stmt->execute([$id]);

            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'announcement',
                'delete',
                'announcement',
                $id,
                "删除公告：【{$existing['title']}】"
            );
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, mixed $status, array $tokenData): void
    {
        if ($status === null || !in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM announcement WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('公告不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE announcement SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            $statusText = $status == 1 ? '启用' : '禁用';
            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'announcement',
                'update_status',
                'announcement',
                $id,
                "{$statusText}公告：【{$existing['title']}】"
            );
        } catch (\Exception $e) {
            Response::error('状态更新失败：' . $e->getMessage());
        }
    }
}
