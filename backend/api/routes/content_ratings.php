<?php
// 获取内容分级列表
function getContentRatingList() {
    $status = $_GET['status'] ?? '';
    $keyword = $_GET['keyword'] ?? '';

    try {
        $db = getDB();

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

        $stmt = $db->prepare("
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

        success(['list' => $list]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 获取启用的内容分级列表（用于影片选择）
function getActiveContentRatings() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT code, label, description, min_age, color_hex
            FROM content_rating
            WHERE status = 1
            ORDER BY sort_order DESC, id ASC
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();

        success(['list' => $list]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 获取内容分级详情
function getContentRatingDetail($id) {
    validateInt($id, '分级ID');

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM content_rating WHERE id = ?");
        $stmt->execute([$id]);
        $rating = $stmt->fetch();

        if (!$rating) {
            error('分级标准不存在', 404);
        }

        $rating['created_at'] = formatDateTime($rating['created_at']);
        $rating['updated_at'] = formatDateTime($rating['updated_at']);

        success($rating);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 新增内容分级
function createContentRating() {
    $code = $_POST['code'] ?? '';
    $label = $_POST['label'] ?? '';
    $description = $_POST['description'] ?? '';
    $minAge = $_POST['min_age'] ?? '';
    $colorHex = $_POST['color_hex'] ?? '#6366f1';
    $status = $_POST['status'] ?? 1;
    $sortOrder = $_POST['sort_order'] ?? 0;

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
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);
    $sortOrder = intval($sortOrder);

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM content_rating WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            error('分级编码已存在');
        }

        $stmt = $db->prepare("
            INSERT INTO content_rating (code, label, description, min_age, color_hex, status, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$code, $label, $description, $minAge, $colorHex, $status, $sortOrder]);

        $id = $db->lastInsertId();

        success(['id' => $id], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

// 更新内容分级
function updateContentRating($id) {
    validateInt($id, '分级ID');

    $code = $_POST['code'] ?? '';
    $label = $_POST['label'] ?? '';
    $description = $_POST['description'] ?? '';
    $minAge = $_POST['min_age'] ?? '';
    $colorHex = $_POST['color_hex'] ?? '';
    $status = $_POST['status'] ?? '';
    $sortOrder = $_POST['sort_order'] ?? '';

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
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);
    $sortOrder = intval($sortOrder);

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM content_rating WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分级标准不存在', 404);
        }

        $stmt = $db->prepare("SELECT id FROM content_rating WHERE code = ? AND id != ?");
        $stmt->execute([$code, $id]);
        if ($stmt->fetch()) {
            error('分级编码已存在');
        }

        $stmt = $db->prepare("
            UPDATE content_rating
            SET code = ?, label = ?, description = ?, min_age = ?, color_hex = ?, status = ?, sort_order = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$code, $label, $description, $minAge, $colorHex, $status, $sortOrder, $id]);

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

// 删除内容分级
function deleteContentRating($id) {
    validateInt($id, '分级ID');

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM content_rating WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分级标准不存在', 404);
        }

        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE content_rating_code IN (SELECT code FROM content_rating WHERE id = ?)");
        $stmt->execute([$id]);
        if ($stmt->fetch()['cnt'] > 0) {
            error('该分级已被影片使用，无法删除');
        }

        $stmt = $db->prepare("DELETE FROM content_rating WHERE id = ?");
        $stmt->execute([$id]);

        success(null, '删除成功');

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

// 更新内容分级状态
function updateContentRatingStatus($id) {
    validateInt($id, '分级ID');

    $status = $_POST['status'] ?? '';

    if ($status === '') {
        error('状态不能为空');
    }

    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM content_rating WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分级标准不存在', 404);
        }

        $stmt = $db->prepare("UPDATE content_rating SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '已启用' : '已禁用');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

// 处理内容分级请求
function handleContentRatingRequest($path, $method, $tokenData) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'content_ratings') {
        getContentRatingList();
    } elseif ($method === 'GET' && $path === 'content_ratings/active') {
        getActiveContentRatings();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getContentRatingDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'content_ratings') {
        createContentRating();
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateContentRating($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteContentRating($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateContentRatingStatus($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
