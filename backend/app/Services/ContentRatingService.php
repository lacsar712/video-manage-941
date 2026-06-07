<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;

class ContentRatingService extends Service
{
    public function getList(string $status, string $keyword): array
    {
        try {
            $where = [];
            $params = [];

            if ($status !== '') {
                $where[] = "status = ?";
                $params[] = $status;
            }

            if ($keyword !== '') {
                $where[] = "(code LIKE ? OR label LIKE ? OR description LIKE ?)";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("
                SELECT * FROM content_rating
                {$whereClause}
                ORDER BY sort_order DESC, id ASC
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['updated_at'] = formatDateTime($item['updated_at']);
            }

            return ['list' => $list];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getActive(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT code, label, description, min_age, color_hex
                FROM content_rating
                WHERE status = 1
                ORDER BY sort_order DESC, id ASC
            ");
            $stmt->execute();
            $list = $stmt->fetchAll();
            return ['list' => $list];
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM content_rating WHERE id = ?");
            $stmt->execute([$id]);
            $rating = $stmt->fetch();

            if (!$rating) {
                Response::error('分级标准不存在', 404);
            }

            $rating['created_at'] = formatDateTime($rating['created_at']);
            $rating['updated_at'] = formatDateTime($rating['updated_at']);

            return $rating;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        $code = $data['code'];
        $label = $data['label'];
        $description = $data['description'];
        $minAge = $data['min_age'];
        $colorHex = $data['color_hex'];
        $status = $data['status'];
        $sortOrder = intval($data['sort_order'] ?? 0);

        validateRequired([
            'code' => '分级编码',
            'label' => '分级标签',
            'color_hex' => '标签颜色'
        ], ['code' => $code, 'label' => $label, 'color_hex' => $colorHex]);

        validateLength($code, 1, 20, '分级编码');
        validateLength($label, 1, 50, '分级标签');

        if (!empty($description)) {
            validateLength($description, 0, 500, '分级描述');
        }

        if ($minAge !== '' && $minAge !== null) {
            validateInt($minAge, '最低年龄');
            $minAge = intval($minAge);
        } else {
            $minAge = null;
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);

        try {
            $stmt = $this->db->prepare("SELECT id FROM content_rating WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                Response::error('分级编码已存在');
            }

            $stmt = $this->db->prepare("
                INSERT INTO content_rating (code, label, description, min_age, color_hex, status, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$code, $label, $description, $minAge, $colorHex, $status, $sortOrder]);
            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): void
    {
        $code = $data['code'];
        $label = $data['label'];
        $description = $data['description'];
        $minAge = $data['min_age'];
        $colorHex = $data['color_hex'];
        $status = $data['status'];
        $sortOrder = intval($data['sort_order'] ?? 0);

        validateRequired([
            'code' => '分级编码',
            'label' => '分级标签',
            'color_hex' => '标签颜色',
            'status' => '状态'
        ], ['code' => $code, 'label' => $label, 'color_hex' => $colorHex, 'status' => $status]);

        validateLength($code, 1, 20, '分级编码');
        validateLength($label, 1, 50, '分级标签');

        if (!empty($description)) {
            validateLength($description, 0, 500, '分级描述');
        }

        if ($minAge !== '' && $minAge !== null) {
            validateInt($minAge, '最低年龄');
            $minAge = intval($minAge);
        } else {
            $minAge = null;
        }

        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值必须为 0 或 1');
        }
        $status = intval($status);

        try {
            $stmt = $this->db->prepare("SELECT id FROM content_rating WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('分级标准不存在', 404);
            }

            $stmt = $this->db->prepare("SELECT id FROM content_rating WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                Response::error('分级编码已存在');
            }

            $stmt = $this->db->prepare("
                UPDATE content_rating
                SET code = ?, label = ?, description = ?, min_age = ?, color_hex = ?, status = ?, sort_order = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$code, $label, $description, $minAge, $colorHex, $status, $sortOrder, $id]);
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM content_rating WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('分级标准不存在', 404);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM video WHERE content_rating_code IN (SELECT code FROM content_rating WHERE id = ?)");
            $stmt->execute([$id]);
            if ($stmt->fetch()['cnt'] > 0) {
                Response::error('该分级已被影片使用，无法删除');
            }

            $stmt = $this->db->prepare("DELETE FROM content_rating WHERE id = ?");
            $stmt->execute([$id]);
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
            $stmt = $this->db->prepare("SELECT id FROM content_rating WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::error('分级标准不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE content_rating SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            return $status == 1 ? '已启用' : '已禁用';
        } catch (\Exception $e) {
            Response::error('操作失败：' . $e->getMessage());
        }
    }
}
