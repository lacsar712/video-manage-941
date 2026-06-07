<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;
use App\Helpers\LogHelper;

class MediaService extends Service
{
    private function isReferenced(\PDO $db, string $filePath): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE cover_url = ?");
        $stmt->execute([$filePath]);
        return intval($stmt->fetch()['cnt']) > 0;
    }

    public function getList(int $page, int $pageSize, string $keyword): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $where = [];
            $params = [];

            if ($keyword !== '') {
                $where[] = "original_name LIKE ?";
                $params[] = "%{$keyword}%";
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM media_asset {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT id, file_path, original_name, mime_type, size_bytes, uploaded_by, created_at
                FROM media_asset
                {$whereClause}
                ORDER BY id DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['created_at'] = formatDateTime($item['created_at']);
                $item['is_referenced'] = $this->isReferenced($this->db, $item['file_path']);
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

    public function delete(int $id, array $tokenData): void
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM media_asset WHERE id = ?");
            $stmt->execute([$id]);
            $asset = $stmt->fetch();

            if (!$asset) {
                Response::error('媒资不存在', 404);
            }

            if ($this->isReferenced($this->db, $asset['file_path'])) {
                Response::error('该文件已被影片引用，无法删除。请先解除引用后再操作。');
            }

            $fullPath = __DIR__ . '/../../' . ltrim($asset['file_path'], '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $stmt = $this->db->prepare("DELETE FROM media_asset WHERE id = ?");
            $stmt->execute([$id]);

            LogHelper::writeOperationLog(
                $tokenData['admin_id'],
                'media',
                'delete',
                'media_asset',
                $id,
                "删除媒资：{$asset['original_name']}"
            );
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }
}
