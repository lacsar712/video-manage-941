<?php
// 获取媒资列表
function getMediaList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 12);
    $keyword = $_GET['keyword'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = "original_name LIKE ?";
            $params[] = "%{$keyword}%";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM media_asset {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
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
            $item['is_referenced'] = checkMediaReferenced($db, $item['file_path']);
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

// 检查媒资是否被引用
function checkMediaReferenced($db, $filePath) {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE cover_url = ?");
    $stmt->execute([$filePath]);
    return intval($stmt->fetch()['cnt']) > 0;
}

// 删除媒资
function deleteMedia($id, $tokenData) {
    validateInt($id, '媒资ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM media_asset WHERE id = ?");
        $stmt->execute([$id]);
        $asset = $stmt->fetch();

        if (!$asset) {
            error('媒资不存在', 404);
        }

        if (checkMediaReferenced($db, $asset['file_path'])) {
            error('该文件已被影片引用，无法删除。请先解除引用后再操作。');
        }

        $fullPath = __DIR__ . '/../../' . ltrim($asset['file_path'], '/');
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $stmt = $db->prepare("DELETE FROM media_asset WHERE id = ?");
        $stmt->execute([$id]);

        writeOperationLog($tokenData['admin_id'], 'media', 'delete', 'media_asset', $id, "删除媒资：{$asset['original_name']}");

        success(null, '删除成功');

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

// 处理媒资请求
function handleMediaRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'media') {
        getMediaList();
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteMedia($parts[1], $tokenData);
    } else {
        error('接口不存在', 404);
    }
}
