<?php

function getRecommendSlotList() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
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

        success($list);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function getRecommendSlotDetail($id) {
    validateInt($id, '槽位ID');

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM recommend_slot WHERE id = ?");
        $stmt->execute([$id]);
        $slot = $stmt->fetch();

        if (!$slot) {
            error('槽位不存在', 404);
        }

        $slot['id'] = intval($slot['id']);
        $slot['max_items'] = intval($slot['max_items']);
        $slot['status'] = intval($slot['status']);
        $slot['sort_order'] = intval($slot['sort_order']);
        $slot['created_at'] = formatDateTime($slot['created_at']);
        $slot['updated_at'] = formatDateTime($slot['updated_at']);

        $stmt = $db->prepare("
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

        success($slot);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createRecommendSlot() {
    $slotKey = $_POST['slot_key'] ?? '';
    $title = $_POST['title'] ?? '';
    $maxItems = intval($_POST['max_items'] ?? 10);
    $status = $_POST['status'] ?? 1;
    $sortOrder = intval($_POST['sort_order'] ?? 0);

    validateRequired([
        'slot_key' => '槽位标识',
        'title' => '槽位标题',
        'max_items' => '最大条目数'
    ], ['slot_key' => $slotKey, 'title' => $title, 'max_items' => $maxItems]);

    validateLength($slotKey, 1, 50, '槽位标识');
    validateLength($title, 1, 100, '槽位标题');

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);
    if ($maxItems < 1 || $maxItems > 100) {
        error('最大条目数必须在 1-100 之间');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE slot_key = ?");
        $stmt->execute([$slotKey]);
        if ($stmt->fetch()) {
            error('槽位标识已存在');
        }

        $stmt = $db->prepare("
            INSERT INTO recommend_slot (slot_key, title, max_items, status, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$slotKey, $title, $maxItems, $status, $sortOrder]);
        $slotId = $db->lastInsertId();

        success(['id' => intval($slotId)], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

function updateRecommendSlot($id) {
    validateInt($id, '槽位ID');

    $slotKey = $_POST['slot_key'] ?? '';
    $title = $_POST['title'] ?? '';
    $maxItems = $_POST['max_items'] ?? '';
    $status = $_POST['status'] ?? '';
    $sortOrder = $_POST['sort_order'] ?? '';

    validateRequired([
        'slot_key' => '槽位标识',
        'title' => '槽位标题',
        'max_items' => '最大条目数',
        'status' => '状态'
    ], ['slot_key' => $slotKey, 'title' => $title, 'max_items' => $maxItems, 'status' => $status]);

    validateLength($slotKey, 1, 50, '槽位标识');
    validateLength($title, 1, 100, '槽位标题');

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);
    $maxItems = intval($maxItems);
    $sortOrder = intval($sortOrder);

    if ($maxItems < 1 || $maxItems > 100) {
        error('最大条目数必须在 1-100 之间');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('槽位不存在', 404);
        }

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE slot_key = ? AND id != ?");
        $stmt->execute([$slotKey, $id]);
        if ($stmt->fetch()) {
            error('槽位标识已存在');
        }

        $stmt = $db->prepare("
            UPDATE recommend_slot
            SET slot_key = ?, title = ?, max_items = ?, status = ?, sort_order = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$slotKey, $title, $maxItems, $status, $sortOrder, $id]);

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

function deleteRecommendSlot($id) {
    validateInt($id, '槽位ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('槽位不存在', 404);
        }

        $db->beginTransaction();

        $stmt = $db->prepare("DELETE FROM recommend_item WHERE slot_id = ?");
        $stmt->execute([$id]);

        $stmt = $db->prepare("DELETE FROM recommend_slot WHERE id = ?");
        $stmt->execute([$id]);

        $db->commit();
        success(null, '删除成功');

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error('删除失败：' . $e->getMessage());
    }
}

function addVideosToRecommendSlot($slotId) {
    validateInt($slotId, '槽位ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $videoIds = $input['video_ids'] ?? ($_POST['video_ids'] ?? '');

    if (empty($videoIds)) {
        error('请选择要添加的影片');
    }

    if (is_array($videoIds)) {
        $videoIdArr = $videoIds;
    } else {
        $videoIdArr = explode(',', $videoIds);
    }
    $videoIdArr = array_filter(array_map('intval', $videoIdArr));

    if (empty($videoIdArr)) {
        error('请选择要添加的影片');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM recommend_slot WHERE id = ?");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();
        if (!$slot) {
            error('槽位不存在', 404);
        }

        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM recommend_item WHERE slot_id = ?");
        $stmt->execute([$slotId]);
        $currentCount = intval($stmt->fetch()['cnt']);

        if ($currentCount + count($videoIdArr) > intval($slot['max_items'])) {
            error('超出最大条目数限制（最多 ' . $slot['max_items'] . ' 条）');
        }

        $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM recommend_item WHERE slot_id = ?");
        $stmt->execute([$slotId]);
        $maxOrder = intval($stmt->fetch()['max_order']);

        $db->beginTransaction();
        foreach ($videoIdArr as $vid) {
            $stmt = $db->prepare("SELECT id, status FROM video WHERE id = ?");
            $stmt->execute([$vid]);
            $video = $stmt->fetch();
            if (!$video) {
                continue;
            }
            if (intval($video['status']) != 1) {
                continue;
            }
            $maxOrder++;
            $stmt = $db->prepare("
                INSERT IGNORE INTO recommend_item (slot_id, video_id, sort_order, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$slotId, $vid, $maxOrder]);
        }
        $db->commit();

        success(null, '添加成功');

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error('添加失败：' . $e->getMessage());
    }
}

function removeVideoFromRecommendSlot($slotId, $videoId) {
    validateInt($slotId, '槽位ID');
    validateInt($videoId, '影片ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
        $stmt->execute([$slotId]);
        if (!$stmt->fetch()) {
            error('槽位不存在', 404);
        }

        $stmt = $db->prepare("DELETE FROM recommend_item WHERE slot_id = ? AND video_id = ?");
        $stmt->execute([$slotId, $videoId]);

        success(null, '移除成功');

    } catch (Exception $e) {
        error('移除失败：' . $e->getMessage());
    }
}

function updateRecommendItemSort($slotId) {
    validateInt($slotId, '槽位ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $videoOrders = $input['video_orders'] ?? [];

    if (empty($videoOrders) || !is_array($videoOrders)) {
        error('排序数据不能为空');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM recommend_slot WHERE id = ?");
        $stmt->execute([$slotId]);
        if (!$stmt->fetch()) {
            error('槽位不存在', 404);
        }

        $db->beginTransaction();
        foreach ($videoOrders as $item) {
            $videoId = intval($item['video_id'] ?? 0);
            $sortOrder = intval($item['sort_order'] ?? 0);
            if ($videoId > 0) {
                $stmt = $db->prepare("
                    UPDATE recommend_item SET sort_order = ?
                    WHERE slot_id = ? AND video_id = ?
                ");
                $stmt->execute([$sortOrder, $slotId, $videoId]);
            }
        }
        $db->commit();

        success(null, '排序更新成功');

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error('排序更新失败：' . $e->getMessage());
    }
}

function getRecommendSlotsPreview() {
    try {
        $db = getDB();

        $stmt = $db->prepare("
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
            $stmt = $db->prepare("
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

        success($result);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function handleRecommendSlotRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'recommend_slots') {
        getRecommendSlotList();
    } elseif ($method === 'GET' && $path === 'recommend_slots/preview') {
        getRecommendSlotsPreview();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getRecommendSlotDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'recommend_slots') {
        createRecommendSlot();
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateRecommendSlot($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteRecommendSlot($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'videos') {
        addVideosToRecommendSlot($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 4 && $parts[2] === 'videos') {
        removeVideoFromRecommendSlot($parts[1], $parts[3]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'sort') {
        updateRecommendItemSort($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
