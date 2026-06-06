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
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
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
