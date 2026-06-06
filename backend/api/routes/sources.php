<?php
// 获取播放源列表
function getSourceList() {
    $videoId = $_GET['video_id'] ?? '';

    if (empty($videoId)) {
        error('影片ID不能为空');
    }

    validateInt($videoId, '影片ID');

    try {
        $db = getDB();

        // 检查影片是否存在
        $stmt = $db->prepare("SELECT id, title FROM video WHERE id = ?");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在', 404);
        }

        // 查询播放源列表
        $stmt = $db->prepare("
            SELECT id, video_id, source_name, m3u8_url, created_at
            FROM video_source
            WHERE video_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$videoId]);
        $list = $stmt->fetchAll();

        // 格式化日期
        foreach ($list as &$item) {
            $item['created_at'] = formatDateTime($item['created_at']);
        }

        success([
            'video' => $video,
            'list' => $list
        ]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 新增播放源
function createSource() {
    $videoId = $_POST['video_id'] ?? '';
    $sourceName = $_POST['source_name'] ?? '';
    $m3u8Url = $_POST['m3u8_url'] ?? '';

    // 验证必填
    validateRequired([
        'video_id' => '影片ID',
        'source_name' => '线路名称',
        'm3u8_url' => 'M3U8地址'
    ], ['video_id' => $videoId, 'source_name' => $sourceName, 'm3u8_url' => $m3u8Url]);

    validateInt($videoId, '影片ID');

    // 验证长度
    validateLength($sourceName, 1, 50, '线路名称');

    // 验证URL格式
    validateUrl($m3u8Url, 'M3U8地址');

    // 验证m3u8后缀
    if (!preg_match('/\.m3u8$/i', $m3u8Url)) {
        error('M3U8地址必须以.m3u8结尾');
    }

    try {
        $db = getDB();

        // 检查影片是否存在
        $stmt = $db->prepare("SELECT id FROM video WHERE id = ?");
        $stmt->execute([$videoId]);
        if (!$stmt->fetch()) {
            error('影片不存在', 404);
        }

        // 插入播放源
        $stmt = $db->prepare("
            INSERT INTO video_source (video_id, source_name, m3u8_url, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$videoId, $sourceName, $m3u8Url]);

        $sourceId = $db->lastInsertId();

        success(['id' => $sourceId], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

// 更新播放源
function updateSource($id) {
    validateInt($id, '播放源ID');

    $sourceName = $_POST['source_name'] ?? '';
    $m3u8Url = $_POST['m3u8_url'] ?? '';

    // 验证必填
    validateRequired([
        'source_name' => '线路名称',
        'm3u8_url' => 'M3U8地址'
    ], ['source_name' => $sourceName, 'm3u8_url' => $m3u8Url]);

    // 验证长度
    validateLength($sourceName, 1, 50, '线路名称');

    // 验证URL格式
    validateUrl($m3u8Url, 'M3U8地址');

    // 验证m3u8后缀
    if (!preg_match('/\.m3u8$/i', $m3u8Url)) {
        error('M3U8地址必须以.m3u8结尾');
    }

    try {
        $db = getDB();

        // 检查播放源是否存在
        $stmt = $db->prepare("SELECT id FROM video_source WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('播放源不存在', 404);
        }

        // 更新播放源
        $stmt = $db->prepare("
            UPDATE video_source
            SET source_name = ?, m3u8_url = ?
            WHERE id = ?
        ");
        $stmt->execute([$sourceName, $m3u8Url, $id]);

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

// 删除播放源
function deleteSource($id) {
    validateInt($id, '播放源ID');

    try {
        $db = getDB();

        // 检查播放源是否存在
        $stmt = $db->prepare("SELECT id FROM video_source WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('播放源不存在', 404);
        }

        // 删除播放源
        $stmt = $db->prepare("DELETE FROM video_source WHERE id = ?");
        $stmt->execute([$id]);

        success(null, '删除成功');

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

// 处理播放源请求
function handleSourceRequest($path, $method) {
    // 解析路径
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'sources') {
        // 获取列表
        getSourceList();
    } elseif ($method === 'POST' && $path === 'sources') {
        // 新增
        createSource();
    } elseif ($method === 'POST' && count($parts) === 2) {
        // 更新
        updateSource($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        // 删除
        deleteSource($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
