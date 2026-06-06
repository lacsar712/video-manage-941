-- 设置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS video_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE video_app;

-- 表1：admin_user（管理员用户）
CREATE TABLE IF NOT EXISTS admin_user (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表2：admin_token（管理员令牌）
CREATE TABLE IF NOT EXISTS admin_token (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expire_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表3：video（影片）
CREATE TABLE IF NOT EXISTS video (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    cover_url VARCHAR(255) NOT NULL,
    description TEXT,
    content_rating_code VARCHAR(20) DEFAULT NULL COMMENT '内容分级编码',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_content_rating (content_rating_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表4：video_source（播放源）
CREATE TABLE IF NOT EXISTS video_source (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    video_id BIGINT NOT NULL,
    source_name VARCHAR(50) NOT NULL COMMENT '线路1/线路2',
    m3u8_url VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 插入种子数据

-- 管理员账号：admin / admin123（密码使用 password_hash）
INSERT INTO admin_user (username, password_hash, created_at) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- 插入测试影片数据（虚构影片，避免版权问题）
INSERT INTO video (title, cover_url, description, status, created_at, updated_at) VALUES
('星际迷航：时空裂痕', '/uploads/covers/test-cover-1.jpg', '一支探险队在深空发现了神秘的时空裂痕，他们必须在宇宙崩塌前找到回家的路。', 1, NOW(), NOW()),
('暗影猎人', '/uploads/covers/test-cover-2.jpg', '一位神秘的赏金猎人在黑暗的城市中追踪危险的超自然生物，揭开隐藏的阴谋。', 1, NOW(), NOW()),
('记忆碎片', '/uploads/covers/test-cover-3.jpg', '一个失忆的男子醒来后发现自己卷入了一场危险的游戏，必须拼凑记忆找出真相。', 1, NOW(), NOW()),
('未来都市2099', '/uploads/covers/test-cover-4.jpg', '在2099年的未来都市，一名黑客发现了改变世界的秘密，引发了一场革命。', 1, NOW(), NOW()),
('深海探秘', '/uploads/covers/test-cover-5.jpg', '科学家团队潜入深海最深处，发现了一个未知的文明和令人震惊的秘密。', 1, NOW(), NOW()),
('魔法学院：觉醒', '/uploads/covers/test-cover-6.jpg', '一个普通学生进入神秘的魔法学院，发现自己拥有改变世界的强大力量。', 1, NOW(), NOW()),
('末日余生', '/uploads/covers/test-cover-7.jpg', '在末日后的废土世界，幸存者们为了生存和希望展开艰难的旅程。', 0, NOW(), NOW()),
('机械战警：重生', '/uploads/covers/test-cover-8.jpg', '一名被改造的机械战警在执行任务时发现了自己的人性，面临艰难的选择。', 1, NOW(), NOW()),
('平行世界', '/uploads/covers/test-cover-9.jpg', '物理学家意外打开了通往平行世界的大门，遇见了另一个自己。', 1, NOW(), NOW()),
('时间旅行者', '/uploads/covers/test-cover-10.jpg', '一位时间旅行者试图改变过去的悲剧，却发现每次改变都会带来意想不到的后果。', 1, NOW(), NOW());

-- 插入播放源数据（m3u8链接）
INSERT INTO video_source (video_id, source_name, m3u8_url, created_at) VALUES
(1, '线路1', 'https://cdn1.example.com/video1/index.m3u8', NOW()),
(1, '线路2', 'https://cdn2.example.com/video1/index.m3u8', NOW()),
(2, '线路1', 'https://cdn1.example.com/video2/index.m3u8', NOW()),
(2, '线路2', 'https://cdn2.example.com/video2/index.m3u8', NOW()),
(3, '线路1', 'https://cdn1.example.com/video3/index.m3u8', NOW()),
(4, '线路1', 'https://cdn1.example.com/video4/index.m3u8', NOW()),
(4, '线路2', 'https://cdn2.example.com/video4/index.m3u8', NOW()),
(5, '线路1', 'https://cdn1.example.com/video5/index.m3u8', NOW()),
(6, '线路1', 'https://cdn1.example.com/video6/index.m3u8', NOW()),
(6, '线路2', 'https://cdn2.example.com/video6/index.m3u8', NOW()),
(7, '线路1', 'https://cdn1.example.com/video7/index.m3u8', NOW()),
(8, '线路1', 'https://cdn1.example.com/video8/index.m3u8', NOW()),
(8, '线路2', 'https://cdn2.example.com/video8/index.m3u8', NOW()),
(9, '线路1', 'https://cdn1.example.com/video9/index.m3u8', NOW()),
(10, '线路1', 'https://cdn1.example.com/video10/index.m3u8', NOW());

-- 表5：scheduled_task（定时任务）
CREATE TABLE IF NOT EXISTS scheduled_task (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    video_id BIGINT NOT NULL,
    action VARCHAR(20) NOT NULL COMMENT 'publish上架 unpublish下架',
    execute_at DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending待执行 executed已执行 cancelled已取消',
    created_by BIGINT NOT NULL,
    result_message VARCHAR(500),
    executed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_video_id (video_id),
    INDEX idx_status (status),
    INDEX idx_execute_at (execute_at),
    INDEX idx_status_execute (status, execute_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表6：operation_log（操作日志）
CREATE TABLE IF NOT EXISTS operation_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    target_type VARCHAR(50),
    target_id BIGINT,
    content TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'success' COMMENT 'success成功 failed失败',
    error_message VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表7：media_asset（媒资库）
CREATE TABLE IF NOT EXISTS media_asset (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_path VARCHAR(255) NOT NULL COMMENT '文件存储路径',
    original_name VARCHAR(255) NOT NULL COMMENT '原始文件名',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIME类型',
    size_bytes BIGINT NOT NULL COMMENT '文件大小（字节）',
    uploaded_by BIGINT NOT NULL COMMENT '上传人ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_file_path (file_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表8：client_release（客户端版本发布）
CREATE TABLE IF NOT EXISTS client_release (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    platform VARCHAR(20) NOT NULL COMMENT '平台：android / ios',
    version_name VARCHAR(50) NOT NULL COMMENT '版本名称，如 1.0.0',
    version_code INT UNSIGNED NOT NULL COMMENT '版本号，正整数，同平台唯一',
    download_url VARCHAR(500) NOT NULL COMMENT '下载地址',
    force_update TINYINT NOT NULL DEFAULT 0 COMMENT '是否强制更新：1是 0否',
    changelog TEXT COMMENT '更新日志',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态：1发布 0下线',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_version_code (platform, version_code),
    INDEX idx_platform (platform),
    INDEX idx_status (status),
    INDEX idx_platform_status (platform, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表9：video_collection（专题合集）
CREATE TABLE IF NOT EXISTS video_collection (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL COMMENT '合集标题',
    cover_url VARCHAR(255) NOT NULL COMMENT '合集封面',
    description TEXT COMMENT '合集描述',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表10：collection_video（合集-影片关联）
CREATE TABLE IF NOT EXISTS collection_video (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    collection_id BIGINT NOT NULL COMMENT '合集ID',
    video_id BIGINT NOT NULL COMMENT '影片ID',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_collection_video (collection_id, video_id),
    INDEX idx_collection_id (collection_id),
    INDEX idx_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表11：video_subtitle（字幕轨道）
CREATE TABLE IF NOT EXISTS video_subtitle (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    video_id BIGINT NOT NULL COMMENT '影片ID',
    language VARCHAR(10) NOT NULL COMMENT '语言：zh/en/ja',
    format VARCHAR(10) NOT NULL COMMENT '格式：vtt/srt',
    file_url VARCHAR(500) NOT NULL COMMENT '字幕文件URL',
    file_name VARCHAR(255) COMMENT '原始文件名',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_video_id (video_id),
    INDEX idx_language (language),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表12：content_rating（内容分级标准）
CREATE TABLE IF NOT EXISTS content_rating (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL COMMENT '分级编码，如 PG-13',
    label VARCHAR(50) NOT NULL COMMENT '分级标签显示名称',
    description VARCHAR(500) COMMENT '分级描述说明',
    min_age INT DEFAULT NULL COMMENT '最低年龄限制',
    color_hex VARCHAR(7) NOT NULL DEFAULT '#6366f1' COMMENT '标签颜色（十六进制）',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 插入内容分级标准种子数据
INSERT INTO content_rating (code, label, description, min_age, color_hex, status, sort_order) VALUES
('G', '通用级', '所有年龄均可观看，适合全年龄段观众', NULL, '#22c55e', 1, 10),
('PG', '辅导级', '建议在家长指导下观看，部分内容可能不适合儿童', NULL, '#3b82f6', 1, 20),
('PG-13', '特别辅导级', '13岁以下儿童需在家长陪同下观看，可能含有暴力、粗口等内容', 13, '#f59e0b', 1, 30),
('R', '限制级', '17岁以下青少年需在家长或成年监护人陪同下观看，含有成人内容', 17, '#ef4444', 1, 40),
('NC-17', '成人级', '仅限18岁以上成人观看，含有明确的成人内容', 18, '#7f1d1d', 1, 50);

-- 表13：recommend_slot（推荐位槽位）
CREATE TABLE IF NOT EXISTS recommend_slot (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slot_key VARCHAR(50) UNIQUE NOT NULL COMMENT '槽位标识，如 home_hot',
    title VARCHAR(100) NOT NULL COMMENT '槽位显示标题',
    max_items INT NOT NULL DEFAULT 10 COMMENT '最大条目数',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slot_key (slot_key),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表14：recommend_item（推荐位条目）
CREATE TABLE IF NOT EXISTS recommend_item (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slot_id BIGINT NOT NULL COMMENT '槽位ID',
    video_id BIGINT NOT NULL COMMENT '影片ID',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slot_video (slot_id, video_id),
    INDEX idx_slot_id (slot_id),
    INDEX idx_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 插入推荐位种子数据
INSERT INTO recommend_slot (slot_key, title, max_items, status, sort_order, created_at, updated_at) VALUES
('home_hot', '热门推荐', 6, 1, 100, NOW(), NOW()),
('home_new', '最新上线', 8, 1, 90, NOW(), NOW()),
('home_classic', '经典回顾', 6, 1, 80, NOW(), NOW());

-- 表15：daily_stats_snapshot（每日运营数据快照）
CREATE TABLE IF NOT EXISTS daily_stats_snapshot (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL COMMENT '统计日期',
    video_total INT NOT NULL DEFAULT 0 COMMENT '影片总量',
    video_published INT NOT NULL DEFAULT 0 COMMENT '已上架影片数',
    source_total INT NOT NULL DEFAULT 0 COMMENT '播放源总量',
    new_videos INT NOT NULL DEFAULT 0 COMMENT '当日新增影片数',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_stat_date (stat_date),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 表16：announcement（系统公告）
CREATE TABLE IF NOT EXISTS announcement (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL COMMENT '公告标题',
    content TEXT NOT NULL COMMENT '公告内容（支持富文本或纯文本）',
    type VARCHAR(20) NOT NULL DEFAULT 'update' COMMENT '公告类型：maintenance维护 / update更新',
    start_at DATETIME NOT NULL COMMENT '生效开始时间',
    end_at DATETIME NOT NULL COMMENT '生效结束时间',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
    created_by BIGINT NOT NULL COMMENT '创建人ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_end (start_at, end_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
