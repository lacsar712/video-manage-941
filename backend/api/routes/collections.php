<?php

function getCollectionList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $status = $_GET['status'] ?? '';
    $keyword = $_GET['keyword'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = "vc.status = ?";
            $params[] = $status;
        }

        if ($keyword !== '') {
            $where[] = "vc.title LIKE ?";
            $params[] = "%{$keyword}%";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM video_collection vc {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
            SELECT vc.id, vc.title, vc.cover_url, vc.description, vc.sort_order, vc.status,
                   vc.created_at, vc.updated_at,
                   COUNT(cv.id) as video_count
            FROM video_collection vc
            LEFT JOIN collection_video cv ON vc.id = cv.collection_id
            {$whereClause}
            GROUP BY vc.id
            ORDER BY vc.sort_order DESC, vc.id DESC
            LIMIT {$offset}, {$pageSize}
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['created_at'] = formatDateTime($item['created_at']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['video_count'] = intval($item['video_count']);
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

function getCollectionDetail($id) {
    validateInt($id, '合集ID');

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        $collection = $stmt->fetch();

        if (!$collection) {
            error('合集不存在', 404);
        }

        $collection['created_at'] = formatDateTime($collection['created_at']);
        $collection['updated_at'] = formatDateTime($collection['updated_at']);

        $stmt = $db->prepare("
            SELECT v.id, v.title, v.cover_url, v.description, v.status,
                   cv.sort_order as cv_sort_order
            FROM collection_video cv
            JOIN video v ON cv.video_id = v.id
            WHERE cv.collection_id = ?
            ORDER BY cv.sort_order DESC, cv.id ASC
        ");
        $stmt->execute([$id]);
        $videos = $stmt->fetchAll();

        $collection['videos'] = $videos;
        $collection['video_count'] = count($videos);

        success($collection);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createCollection() {
    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 1;
    $videoIds = $_POST['video_ids'] ?? '';

    validateRequired([
        'title' => '合集标题',
        'cover_url' => '合集封面'
    ], ['title' => $title, 'cover_url' => $coverUrl]);

    validateLength($title, 1, 200, '合集标题');
    if (!empty($description)) {
        validateLength($description, 0, 1000, '合集描述');
    }
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);

    $videoIdArr = [];
    if (!empty($videoIds)) {
        if (is_array($videoIds)) {
            $videoIdArr = $videoIds;
        } else {
            $videoIdArr = explode(',', $videoIds);
        }
        $videoIdArr = array_filter(array_map('intval', $videoIdArr));
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO video_collection (title, cover_url, description, sort_order, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $coverUrl, $description, $sortOrder, $status]);
        $collectionId = $db->lastInsertId();

        if (!empty($videoIdArr)) {
            $orderIdx = count($videoIdArr);
            foreach ($videoIdArr as $vid) {
                $stmt = $db->prepare("
                    INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$collectionId, $vid, $orderIdx]);
                $orderIdx--;
            }
        }

        $db->commit();
        success(['id' => $collectionId], '添加成功');

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error('添加失败：' . $e->getMessage());
    }
}

function updateCollection($id) {
    validateInt($id, '合集ID');

    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $sortOrder = $_POST['sort_order'] ?? '';
    $status = $_POST['status'] ?? '';
    $videoIds = $_POST['video_ids'] ?? null;

    validateRequired([
        'title' => '合集标题',
        'cover_url' => '合集封面',
        'status' => '状态'
    ], ['title' => $title, 'cover_url' => $coverUrl, 'status' => $status]);

    validateLength($title, 1, 200, '合集标题');
    if (!empty($description)) {
        validateLength($description, 0, 1000, '合集描述');
    }
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);
    $sortOrder = intval($sortOrder);

    $videoIdArr = null;
    if ($videoIds !== null) {
        if (is_array($videoIds)) {
            $videoIdArr = $videoIds;
        } else {
            $videoIdArr = explode(',', $videoIds);
        }
        $videoIdArr = array_filter(array_map('intval', $videoIdArr));
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $db->beginTransaction();

        $stmt = $db->prepare("
            UPDATE video_collection
            SET title = ?, cover_url = ?, description = ?, sort_order = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $coverUrl, $description, $sortOrder, $status, $id]);

        if ($videoIdArr !== null) {
            $stmt = $db->prepare("DELETE FROM collection_video WHERE collection_id = ?");
            $stmt->execute([$id]);

            if (!empty($videoIdArr)) {
                $orderIdx = count($videoIdArr);
                foreach ($videoIdArr as $vid) {
                    $stmt = $db->prepare("
                        INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$id, $vid, $orderIdx]);
                    $orderIdx--;
                }
            }
        }

        $db->commit();
        success(null, '更新成功');

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error('更新失败：' . $e->getMessage());
    }
}

function deleteCollection($id) {
    validateInt($id, '合集ID');

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $stmt = $db->prepare("DELETE FROM collection_video WHERE collection_id = ?");
        $stmt->execute([$id]);

        $stmt = $db->prepare("DELETE FROM video_collection WHERE id = ?");
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

function updateCollectionStatus($id) {
    validateInt($id, '合集ID');

    $status = $_POST['status'] ?? '';
    if ($status === '') {
        error('状态不能为空');
    }
    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $stmt = $db->prepare("UPDATE video_collection SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '上架成功' : '下架成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

function addVideosToCollection($id) {
    validateInt($id, '合集ID');

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

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM collection_video WHERE collection_id = ?");
        $stmt->execute([$id]);
        $maxOrder = intval($stmt->fetch()['max_order']);

        $db->beginTransaction();
        foreach ($videoIdArr as $vid) {
            $maxOrder++;
            $stmt = $db->prepare("
                INSERT IGNORE INTO collection_video (collection_id, video_id, sort_order, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$id, $vid, $maxOrder]);
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

function removeVideoFromCollection($id, $videoId) {
    validateInt($id, '合集ID');
    validateInt($videoId, '影片ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $stmt = $db->prepare("DELETE FROM collection_video WHERE collection_id = ? AND video_id = ?");
        $stmt->execute([$id, $videoId]);

        success(null, '移除成功');

    } catch (Exception $e) {
        error('移除失败：' . $e->getMessage());
    }
}

function updateCollectionVideoSort($id) {
    validateInt($id, '合集ID');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $videoOrders = $input['video_orders'] ?? [];

    if (empty($videoOrders) || !is_array($videoOrders)) {
        error('排序数据不能为空');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM video_collection WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('合集不存在', 404);
        }

        $db->beginTransaction();
        foreach ($videoOrders as $item) {
            $videoId = intval($item['video_id'] ?? 0);
            $sortOrder = intval($item['sort_order'] ?? 0);
            if ($videoId > 0) {
                $stmt = $db->prepare("
                    UPDATE collection_video SET sort_order = ?
                    WHERE collection_id = ? AND video_id = ?
                ");
                $stmt->execute([$sortOrder, $id, $videoId]);
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

function handleCollectionRequest($path, $method) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'collections') {
        getCollectionList();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getCollectionDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'collections') {
        createCollection();
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateCollection($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteCollection($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateCollectionStatus($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'videos') {
        addVideosToCollection($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 4 && $parts[2] === 'videos') {
        removeVideoFromCollection($parts[1], $parts[3]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'sort') {
        updateCollectionVideoSort($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
