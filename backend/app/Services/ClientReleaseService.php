<?php

namespace App\Services;

use App\Core\Service;
use App\Core\Response;
use App\Helpers\LogHelper;

class ClientReleaseService extends Service
{
    private function validatePlatform(string $platform): void
    {
        if (!in_array($platform, ['android', 'ios'])) {
            Response::error('平台必须为 android 或 ios');
        }
    }

    private function validateVersionCode(mixed $versionCode): void
    {
        if (!is_numeric($versionCode) || intval($versionCode) <= 0 || intval($versionCode) != $versionCode) {
            Response::error('version_code 必须为正整数');
        }
    }

    private function checkDuplicateVersionCode(\PDO $db, string $platform, mixed $versionCode, ?int $excludeId = null): void
    {
        $sql = "SELECT id FROM client_release WHERE platform = ? AND version_code = ?";
        $params = [$platform, $versionCode];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            Response::error('该平台下 version_code 已存在，不可重复');
        }
    }

    public function getList(int $page, int $pageSize, string $platform, string $status): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        try {
            $where = [];
            $params = [];

            if ($platform !== '') {
                $this->validatePlatform($platform);
                $where[] = "platform = ?";
                $params[] = $platform;
            }

            if ($status !== '') {
                if (!in_array($status, ['0', '1'])) {
                    Response::error('状态值不正确');
                }
                $where[] = "status = ?";
                $params[] = $status;
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM client_release {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $stmt = $this->db->prepare("
                SELECT id, platform, version_name, version_code, download_url,
                       force_update, changelog, status, created_at
                FROM client_release
                {$whereClause}
                ORDER BY version_code DESC, id DESC
                LIMIT {$offset}, {$pageSize}
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                $item['version_code'] = intval($item['version_code']);
                $item['force_update'] = intval($item['force_update']);
                $item['status'] = intval($item['status']);
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

    public function getLatest(): array
    {
        try {
            $platforms = ['android', 'ios'];
            $result = [];

            foreach ($platforms as $platform) {
                $stmt = $this->db->prepare("
                    SELECT id, platform, version_name, version_code, download_url,
                           force_update, changelog, status, created_at
                    FROM client_release
                    WHERE platform = ? AND status = 1
                    ORDER BY version_code DESC
                    LIMIT 1
                ");
                $stmt->execute([$platform]);
                $item = $stmt->fetch();
                if ($item) {
                    $item['version_code'] = intval($item['version_code']);
                    $item['force_update'] = intval($item['force_update']);
                    $item['status'] = intval($item['status']);
                    $item['created_at'] = formatDateTime($item['created_at']);
                }
                $result[$platform] = $item ?: null;
            }

            return $result;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function getDetail(int $id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM client_release WHERE id = ?");
            $stmt->execute([$id]);
            $release = $stmt->fetch();

            if (!$release) {
                Response::error('版本不存在', 404);
            }

            $release['version_code'] = intval($release['version_code']);
            $release['force_update'] = intval($release['force_update']);
            $release['status'] = intval($release['status']);
            $release['created_at'] = formatDateTime($release['created_at']);

            return $release;
        } catch (\Exception $e) {
            Response::error('查询失败：' . $e->getMessage());
        }
    }

    public function create(array $data, array $tokenData): int
    {
        $platform = $data['platform'];
        $versionName = $data['version_name'];
        $versionCode = $data['version_code'];
        $downloadUrl = $data['download_url'];
        $forceUpdate = $data['force_update'];
        $changelog = $data['changelog'];
        $status = $data['status'];

        validateRequired([
            'platform' => '平台',
            'version_name' => '版本名称',
            'version_code' => '版本号',
            'download_url' => '下载地址'
        ], [
            'platform' => $platform,
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'download_url' => $downloadUrl
        ]);

        $this->validatePlatform($platform);
        $this->validateVersionCode($versionCode);
        validateUrl($downloadUrl, '下载地址');
        validateLength($versionName, 1, 50, '版本名称');

        if (!in_array($forceUpdate, [0, 1, '0', '1'])) {
            Response::error('force_update 必须为 0 或 1');
        }
        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('status 必须为 0 或 1');
        }

        try {
            $this->checkDuplicateVersionCode($this->db, $platform, $versionCode);

            $stmt = $this->db->prepare("
                INSERT INTO client_release (platform, version_name, version_code, download_url, force_update, changelog, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $platform,
                $versionName,
                intval($versionCode),
                $downloadUrl,
                intval($forceUpdate),
                $changelog,
                intval($status)
            ]);

            $releaseId = (int)$this->db->lastInsertId();

            LogHelper::writeOperationLog(
                $tokenData['admin_id'] ?? null,
                'client_release',
                'create',
                'client_release',
                $releaseId,
                "创建版本：{$platform} {$versionName} ({$versionCode})"
            );

            return $releaseId;
        } catch (\Exception $e) {
            Response::error('添加失败：' . $e->getMessage());
        }
    }

    public function update(int $id, array $data, array $tokenData): void
    {
        $platform = $data['platform'];
        $versionName = $data['version_name'];
        $versionCode = $data['version_code'];
        $downloadUrl = $data['download_url'];
        $forceUpdate = $data['force_update'];
        $changelog = $data['changelog'];
        $status = $data['status'];

        validateRequired([
            'platform' => '平台',
            'version_name' => '版本名称',
            'version_code' => '版本号',
            'download_url' => '下载地址',
            'force_update' => '是否强制更新',
            'status' => '状态'
        ], [
            'platform' => $platform,
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'download_url' => $downloadUrl,
            'force_update' => $forceUpdate,
            'status' => $status
        ]);

        $this->validatePlatform($platform);
        $this->validateVersionCode($versionCode);
        validateUrl($downloadUrl, '下载地址');
        validateLength($versionName, 1, 50, '版本名称');

        if (!in_array($forceUpdate, [0, 1, '0', '1'])) {
            Response::error('force_update 必须为 0 或 1');
        }
        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('status 必须为 0 或 1');
        }

        try {
            $stmt = $this->db->prepare("SELECT id, platform, version_code FROM client_release WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('版本不存在', 404);
            }

            $this->checkDuplicateVersionCode($this->db, $platform, $versionCode, $id);

            $stmt = $this->db->prepare("
                UPDATE client_release
                SET platform = ?, version_name = ?, version_code = ?, download_url = ?,
                    force_update = ?, changelog = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $platform,
                $versionName,
                intval($versionCode),
                $downloadUrl,
                intval($forceUpdate),
                $changelog,
                intval($status),
                $id
            ]);

            LogHelper::writeOperationLog(
                $tokenData['admin_id'] ?? null,
                'client_release',
                'update',
                'client_release',
                $id,
                "更新版本：{$platform} {$versionName} ({$versionCode})"
            );
        } catch (\Exception $e) {
            Response::error('更新失败：' . $e->getMessage());
        }
    }

    public function delete(int $id, array $tokenData): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id, platform, version_name, version_code FROM client_release WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('版本不存在', 404);
            }

            $stmt = $this->db->prepare("DELETE FROM client_release WHERE id = ?");
            $stmt->execute([$id]);

            LogHelper::writeOperationLog(
                $tokenData['admin_id'] ?? null,
                'client_release',
                'delete',
                'client_release',
                $id,
                "删除版本：{$existing['platform']} {$existing['version_name']} ({$existing['version_code']})"
            );
        } catch (\Exception $e) {
            Response::error('删除失败：' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, mixed $status, array $tokenData): string
    {
        if ($status === '' || $status === null) {
            Response::error('状态不能为空');
        }
        if (!in_array($status, [0, 1, '0', '1'])) {
            Response::error('状态值不正确');
        }

        try {
            $stmt = $this->db->prepare("SELECT id, platform, version_name, version_code FROM client_release WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                Response::error('版本不存在', 404);
            }

            $stmt = $this->db->prepare("UPDATE client_release SET status = ? WHERE id = ?");
            $stmt->execute([intval($status), $id]);

            $actionText = intval($status) == 1 ? '发布' : '下线';
            LogHelper::writeOperationLog(
                $tokenData['admin_id'] ?? null,
                'client_release',
                $actionText,
                'client_release',
                $id,
                "{$actionText}版本：{$existing['platform']} {$existing['version_name']} ({$existing['version_code']})"
            );

            return $actionText . '成功';
        } catch (\Exception $e) {
            Response::error('操作失败：' . $e->getMessage());
        }
    }
}
