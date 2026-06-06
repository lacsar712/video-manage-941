<?php
// APP API - 获取上架影片列表
function getAppVideoList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        // 查询总数（只查上架的）
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM video WHERE status = 1");
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // 查询列表
        $stmt = $db->prepare("
            SELECT id, title, cover_url, description, created_at
            FROM video
            WHERE status = 1
            ORDER BY id DESC
            LIMIT {$offset}, {$pageSize}
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();

        // 格式化日期
        foreach ($list as &$item) {
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

// APP API - 获取影片详情
function getAppVideoDetail($id) {
    validateInt($id, '影片ID');

    try {
        $db = getDB();

        // 查询影片（只返回上架的）
        $stmt = $db->prepare("
            SELECT id, title, cover_url, description, created_at
            FROM video
            WHERE id = ? AND status = 1
        ");
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在或已下架', 404);
        }

        $video['created_at'] = formatDateTime($video['created_at']);

        success($video);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// APP API - 获取影片播放源列表
function getAppVideoSources($id) {
    validateInt($id, '影片ID');

    try {
        $db = getDB();

        // 检查影片是否存在且上架
        $stmt = $db->prepare("SELECT id, title FROM video WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在或已下架', 404);
        }

        // 查询播放源列表
        $stmt = $db->prepare("
            SELECT id, source_name, m3u8_url
            FROM video_source
            WHERE video_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$id]);
        $sources = $stmt->fetchAll();

        success([
            'video_id' => $video['id'],
            'video_title' => $video['title'],
            'sources' => $sources
        ]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 处理APP请求
function handleAppRequest($path, $method) {
    // 解析路径
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'app/videos') {
        // 获取影片列表
        getAppVideoList();
    } elseif ($method === 'GET' && count($parts) === 3 && $parts[1] === 'videos') {
        // 获取影片详情
        getAppVideoDetail($parts[2]);
    } elseif ($method === 'GET' && count($parts) === 4 && $parts[1] === 'videos' && $parts[3] === 'sources') {
        // 获取播放源列表
        getAppVideoSources($parts[2]);
    } else {
        error('接口不存在', 404);
    }
}
