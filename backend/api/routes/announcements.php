<?php

function getAnnouncementList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    $keyword = $_GET['keyword'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

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

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM announcement a {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
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

function getActiveAnnouncements() {
    try {
        $db = getDB();

        $stmt = $db->prepare("
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

        success($list);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function getAnnouncementDetail($id) {
    validateInt($id, '公告ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT a.*, au.username as creator_name
            FROM announcement a
            LEFT JOIN admin_user au ON a.created_by = au.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            error('公告不存在', 404);
        }

        $item['created_at'] = formatDateTime($item['created_at']);
        $item['updated_at'] = formatDateTime($item['updated_at']);
        $item['start_at'] = formatDateTime($item['start_at']);
        $item['end_at'] = formatDateTime($item['end_at']);

        success($item);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createAnnouncement($tokenData) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    $type = $input['type'] ?? 'update';
    $startAt = $input['start_at'] ?? '';
    $endAt = $input['end_at'] ?? '';
    $status = $input['status'] ?? 1;

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
        error('公告类型必须为 maintenance 或 update');
    }

    $startTimestamp = strtotime($startAt);
    $endTimestamp = strtotime($endAt);
    if ($startTimestamp === false) {
        error('生效开始时间格式不正确');
    }
    if ($endTimestamp === false) {
        error('生效结束时间格式不正确');
    }
    if ($endTimestamp <= $startTimestamp) {
        error('生效结束时间必须晚于开始时间');
    }

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $startAtFormatted = date('Y-m-d H:i:s', $startTimestamp);
        $endAtFormatted = date('Y-m-d H:i:s', $endTimestamp);

        $stmt = $db->prepare("
            INSERT INTO announcement (title, content, type, start_at, end_at, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $content, $type, $startAtFormatted, $endAtFormatted, $status, $tokenData['admin_id']]);

        $announcementId = $db->lastInsertId();

        $typeText = $type === 'maintenance' ? '维护' : '更新';
        writeOperationLog(
            $tokenData['admin_id'],
            'announcement',
            'create',
            'announcement',
            $announcementId,
            "创建{$typeText}公告：【{$title}】，生效时间：{$startAtFormatted} 至 {$endAtFormatted}"
        );

        success(['id' => $announcementId], '创建成功');

    } catch (Exception $e) {
        error('创建失败：' . $e->getMessage());
    }
}

function updateAnnouncement($id, $tokenData) {
    validateInt($id, '公告ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    $type = $input['type'] ?? 'update';
    $startAt = $input['start_at'] ?? '';
    $endAt = $input['end_at'] ?? '';
    $status = $input['status'] ?? 1;

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
        error('公告类型必须为 maintenance 或 update');
    }

    $startTimestamp = strtotime($startAt);
    $endTimestamp = strtotime($endAt);
    if ($startTimestamp === false) {
        error('生效开始时间格式不正确');
    }
    if ($endTimestamp === false) {
        error('生效结束时间格式不正确');
    }
    if ($endTimestamp <= $startTimestamp) {
        error('生效结束时间必须晚于开始时间');
    }

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM announcement WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('公告不存在', 404);
        }

        $startAtFormatted = date('Y-m-d H:i:s', $startTimestamp);
        $endAtFormatted = date('Y-m-d H:i:s', $endTimestamp);

        $stmt = $db->prepare("
            UPDATE announcement
            SET title = ?, content = ?, type = ?, start_at = ?, end_at = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $content, $type, $startAtFormatted, $endAtFormatted, $status, $id]);

        $typeText = $type === 'maintenance' ? '维护' : '更新';
        writeOperationLog(
            $tokenData['admin_id'],
            'announcement',
            'update',
            'announcement',
            $id,
            "更新{$typeText}公告：【{$title}】，生效时间：{$startAtFormatted} 至 {$endAtFormatted}"
        );

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

function deleteAnnouncement($id, $tokenData) {
    validateInt($id, '公告ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM announcement WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('公告不存在', 404);
        }

        $stmt = $db->prepare("DELETE FROM announcement WHERE id = ?");
        $stmt->execute([$id]);

        writeOperationLog(
            $tokenData['admin_id'],
            'announcement',
            'delete',
            'announcement',
            $id,
            "删除公告：【{$existing['title']}】"
        );

        success(null, '删除成功');

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

function updateAnnouncementStatus($id, $tokenData) {
    validateInt($id, '公告ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $status = $input['status'] ?? null;

    if ($status === null || !in_array($status, [0, 1, '0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM announcement WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('公告不存在', 404);
        }

        $stmt = $db->prepare("UPDATE announcement SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        $statusText = $status == 1 ? '启用' : '禁用';
        writeOperationLog(
            $tokenData['admin_id'],
            'announcement',
            'update_status',
            'announcement',
            $id,
            "{$statusText}公告：【{$existing['title']}】"
        );

        success(null, '状态更新成功');

    } catch (Exception $e) {
        error('状态更新失败：' . $e->getMessage());
    }
}

function handleAnnouncementRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'announcements') {
        getAnnouncementList();
    } elseif ($method === 'GET' && $path === 'announcements/active') {
        getActiveAnnouncements();
    } elseif ($method === 'GET' && count($parts) === 2 && $parts[0] === 'announcements') {
        getAnnouncementDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'announcements') {
        createAnnouncement($tokenData);
    } elseif ($method === 'POST' && count($parts) === 2 && $parts[0] === 'announcements') {
        updateAnnouncement($parts[1], $tokenData);
    } elseif ($method === 'DELETE' && count($parts) === 2 && $parts[0] === 'announcements') {
        deleteAnnouncement($parts[1], $tokenData);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[0] === 'announcements' && $parts[2] === 'status') {
        updateAnnouncementStatus($parts[1], $tokenData);
    } else {
        error('接口不存在', 404);
    }
}
