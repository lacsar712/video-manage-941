<?php

function getScheduledTaskList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $status = $_GET['status'] ?? '';
    $action = $_GET['action'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = "st.status = ?";
            $params[] = $status;
        }

        if ($action !== '') {
            $where[] = "st.action = ?";
            $params[] = $action;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM scheduled_task st {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
            SELECT st.*, v.title as video_title, v.status as video_status, au.username as creator_name
            FROM scheduled_task st
            LEFT JOIN video v ON st.video_id = v.id
            LEFT JOIN admin_user au ON st.created_by = au.id
            {$whereClause}
            ORDER BY st.execute_at DESC
            LIMIT {$offset}, {$pageSize}
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['created_at'] = formatDateTime($item['created_at']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['execute_at'] = formatDateTime($item['execute_at']);
            if (!empty($item['executed_at'])) {
                $item['executed_at'] = formatDateTime($item['executed_at']);
            }
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

function getUpcomingScheduledTasks() {
    $limit = intval($_GET['limit'] ?? 5);
    $limit = min(20, max(1, $limit));

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT st.*, v.title as video_title
            FROM scheduled_task st
            LEFT JOIN video v ON st.video_id = v.id
            WHERE st.status = 'pending' AND st.execute_at > NOW()
            ORDER BY st.execute_at ASC
            LIMIT {$limit}
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['execute_at'] = formatDateTime($item['execute_at']);
            $item['created_at'] = formatDateTime($item['created_at']);
        }

        success($list);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createScheduledTask($tokenData) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $videoId = $input['video_id'] ?? $_POST['video_id'] ?? '';
    $action = $input['action'] ?? $_POST['action'] ?? '';
    $executeAt = $input['execute_at'] ?? $_POST['execute_at'] ?? '';

    validateRequired([
        'video_id' => '影片ID',
        'action' => '动作类型',
        'execute_at' => '执行时间'
    ], ['video_id' => $videoId, 'action' => $action, 'execute_at' => $executeAt]);

    validateInt($videoId, '影片ID');

    if (!in_array($action, ['publish', 'unpublish'])) {
        error('动作类型必须为 publish 或 unpublish');
    }

    $executeTimestamp = strtotime($executeAt);
    if ($executeTimestamp === false) {
        error('执行时间格式不正确');
    }

    $minExecuteTime = time() + 5 * 60;
    if ($executeTimestamp < $minExecuteTime) {
        error('执行时间必须晚于当前时间 5 分钟');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, title, status FROM video WHERE id = ?");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        if (!$video) {
            error('影片不存在', 404);
        }

        $targetStatus = $action === 'publish' ? 1 : 0;
        if ($video['status'] == $targetStatus) {
            $actionText = $action === 'publish' ? '上架' : '下架';
            error("影片当前已是{$actionText}状态");
        }

        $stmt = $db->prepare("
            SELECT id FROM scheduled_task
            WHERE video_id = ? AND status = 'pending' AND action = ?
        ");
        $stmt->execute([$videoId, $action]);
        if ($stmt->fetch()) {
            $actionText = $action === 'publish' ? '上架' : '下架';
            error("该影片已存在待执行的{$actionText}任务");
        }

        $executeAtFormatted = date('Y-m-d H:i:s', $executeTimestamp);

        $stmt = $db->prepare("
            INSERT INTO scheduled_task (video_id, action, execute_at, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, 'pending', ?, NOW(), NOW())
        ");
        $stmt->execute([$videoId, $action, $executeAtFormatted, $tokenData['admin_id']]);

        $taskId = $db->lastInsertId();

        $actionText = $action === 'publish' ? '上架' : '下架';
        writeOperationLog(
            $tokenData['admin_id'],
            'scheduled_task',
            'create',
            'scheduled_task',
            $taskId,
            "创建定时{$actionText}任务：影片【{$video['title']}】，执行时间：{$executeAtFormatted}"
        );

        success(['id' => $taskId], '创建成功');

    } catch (Exception $e) {
        error('创建失败：' . $e->getMessage());
    }
}

function cancelScheduledTask($id, $tokenData) {
    validateInt($id, '任务ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT st.*, v.title as video_title
            FROM scheduled_task st
            LEFT JOIN video v ON st.video_id = v.id
            WHERE st.id = ?
        ");
        $stmt->execute([$id]);
        $task = $stmt->fetch();

        if (!$task) {
            error('任务不存在', 404);
        }

        if ($task['status'] !== 'pending') {
            error('只有待执行的任务才能取消');
        }

        $stmt = $db->prepare("
            UPDATE scheduled_task
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$id]);

        $actionText = $task['action'] === 'publish' ? '上架' : '下架';
        writeOperationLog(
            $tokenData['admin_id'],
            'scheduled_task',
            'cancel',
            'scheduled_task',
            $id,
            "取消定时{$actionText}任务：影片【{$task['video_title']}】，原执行时间：{$task['execute_at']}"
        );

        success(null, '取消成功');

    } catch (Exception $e) {
        error('取消失败：' . $e->getMessage());
    }
}

function handleScheduledTaskRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'scheduled_tasks') {
        getScheduledTaskList();
    } elseif ($method === 'GET' && $path === 'scheduled_tasks/upcoming') {
        getUpcomingScheduledTasks();
    } elseif ($method === 'POST' && $path === 'scheduled_tasks') {
        createScheduledTask($tokenData);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'cancel') {
        cancelScheduledTask($parts[1], $tokenData);
    } else {
        error('接口不存在', 404);
    }
}
