<?php

function validatePlatform($platform) {
    if (!in_array($platform, ['android', 'ios'])) {
        error('平台必须为 android 或 ios');
    }
}

function validateVersionCode($versionCode) {
    if (!is_numeric($versionCode) || intval($versionCode) <= 0 || intval($versionCode) != $versionCode) {
        error('version_code 必须为正整数');
    }
}

function checkDuplicateVersionCode($db, $platform, $versionCode, $excludeId = null) {
    $sql = "SELECT id FROM client_release WHERE platform = ? AND version_code = ?";
    $params = [$platform, $versionCode];
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        error('该平台下 version_code 已存在，不可重复');
    }
}

function getClientReleaseList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $platform = $_GET['platform'] ?? '';
    $status = $_GET['status'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        $where = [];
        $params = [];

        if ($platform !== '') {
            validatePlatform($platform);
            $where[] = "platform = ?";
            $params[] = $platform;
        }

        if ($status !== '') {
            if (!in_array($status, ['0', '1'])) {
                error('状态值不正确');
            }
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM client_release {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
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

        success([
            'list' => $list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $pageSize
        ]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function getClientReleaseLatest() {
    try {
        $db = getDB();
        $platforms = ['android', 'ios'];
        $result = [];

        foreach ($platforms as $platform) {
            $stmt = $db->prepare("
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

        success($result);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function getClientReleaseDetail($id) {
    validateInt($id, '版本ID');

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM client_release WHERE id = ?");
        $stmt->execute([$id]);
        $release = $stmt->fetch();

        if (!$release) {
            error('版本不存在', 404);
        }

        $release['version_code'] = intval($release['version_code']);
        $release['force_update'] = intval($release['force_update']);
        $release['status'] = intval($release['status']);
        $release['created_at'] = formatDateTime($release['created_at']);

        success($release);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createClientRelease($tokenData) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $platform = $input['platform'] ?? '';
    $versionName = $input['version_name'] ?? '';
    $versionCode = $input['version_code'] ?? '';
    $downloadUrl = $input['download_url'] ?? '';
    $forceUpdate = $input['force_update'] ?? 0;
    $changelog = $input['changelog'] ?? '';
    $status = $input['status'] ?? 0;

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

    validatePlatform($platform);
    validateVersionCode($versionCode);
    validateUrl($downloadUrl, '下载地址');
    validateLength($versionName, 1, 50, '版本名称');

    if (!in_array($forceUpdate, [0, 1, '0', '1'])) {
        error('force_update 必须为 0 或 1');
    }
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('status 必须为 0 或 1');
    }

    try {
        $db = getDB();
        checkDuplicateVersionCode($db, $platform, $versionCode);

        $stmt = $db->prepare("
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

        $releaseId = $db->lastInsertId();

        writeOperationLog(
            $tokenData['admin_id'] ?? null,
            'client_release',
            'create',
            'client_release',
            $releaseId,
            "创建版本：{$platform} {$versionName} ({$versionCode})"
        );

        success(['id' => intval($releaseId)], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

function updateClientRelease($id, $tokenData) {
    validateInt($id, '版本ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $platform = $input['platform'] ?? '';
    $versionName = $input['version_name'] ?? '';
    $versionCode = $input['version_code'] ?? '';
    $downloadUrl = $input['download_url'] ?? '';
    $forceUpdate = $input['force_update'] ?? '';
    $changelog = $input['changelog'] ?? '';
    $status = $input['status'] ?? '';

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

    validatePlatform($platform);
    validateVersionCode($versionCode);
    validateUrl($downloadUrl, '下载地址');
    validateLength($versionName, 1, 50, '版本名称');

    if (!in_array($forceUpdate, [0, 1, '0', '1'])) {
        error('force_update 必须为 0 或 1');
    }
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('status 必须为 0 或 1');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, platform, version_code FROM client_release WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('版本不存在', 404);
        }

        checkDuplicateVersionCode($db, $platform, $versionCode, $id);

        $stmt = $db->prepare("
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

        writeOperationLog(
            $tokenData['admin_id'] ?? null,
            'client_release',
            'update',
            'client_release',
            $id,
            "更新版本：{$platform} {$versionName} ({$versionCode})"
        );

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

function deleteClientRelease($id, $tokenData) {
    validateInt($id, '版本ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, platform, version_name, version_code FROM client_release WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('版本不存在', 404);
        }

        $stmt = $db->prepare("DELETE FROM client_release WHERE id = ?");
        $stmt->execute([$id]);

        writeOperationLog(
            $tokenData['admin_id'] ?? null,
            'client_release',
            'delete',
            'client_release',
            $id,
            "删除版本：{$existing['platform']} {$existing['version_name']} ({$existing['version_code']})"
        );

        success(null, '删除成功');

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

function updateClientReleaseStatus($id, $tokenData) {
    validateInt($id, '版本ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $status = $input['status'] ?? '';

    if ($status === '') {
        error('状态不能为空');
    }
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, platform, version_name, version_code FROM client_release WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('版本不存在', 404);
        }

        $stmt = $db->prepare("UPDATE client_release SET status = ? WHERE id = ?");
        $stmt->execute([intval($status), $id]);

        $actionText = intval($status) == 1 ? '发布' : '下线';
        writeOperationLog(
            $tokenData['admin_id'] ?? null,
            'client_release',
            $actionText,
            'client_release',
            $id,
            "{$actionText}版本：{$existing['platform']} {$existing['version_name']} ({$existing['version_code']})"
        );

        success(null, $actionText . '成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

function handleClientReleaseRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'client_releases') {
        getClientReleaseList();
    } elseif ($method === 'GET' && $path === 'client_releases/latest') {
        getClientReleaseLatest();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getClientReleaseDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'client_releases') {
        createClientRelease($tokenData);
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateClientRelease($parts[1], $tokenData);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteClientRelease($parts[1], $tokenData);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateClientReleaseStatus($parts[1], $tokenData);
    } else {
        error('接口不存在', 404);
    }
}
