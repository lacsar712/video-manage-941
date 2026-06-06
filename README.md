# 影视APP管理后台

一个基于 Docker 的影视管理系统，包含管理后台和 APP API，支持影片管理和 m3u8 播放源维护。

## 原始需求

> 开发一款影视APP 的管理后台 包括api 支持m3u8 写php 数据库 mysql

## 技术栈

### 后端

- PHP 8.2 + PHP-FPM
- MySQL 8.0
- Nginx (stable)

### 前端

- Vue 3
- Vue Router
- Element Plus
- Axios

## 功能特性

### 管理后台

- ✅ 管理员登录/退出
- ✅ 影片管理（列表/新增/编辑/删除/上下架）
- ✅ 播放源管理（m3u8：新增/编辑/删除）
- ✅ 完整的表单验证（前端+后端）
- ✅ 中文界面和错误提示
- ✅ UTF-8 编码支持

### APP API

- ✅ 获取上架影片列表
- ✅ 获取影片详情
- ✅ 获取影片播放源列表（m3u8 URL）

## 快速开始

### 1. 安装前端依赖并构建

```bash
cd frontend
npm install
npm run build
cd ..
```

### 2. 启动 Docker 容器

```bash
docker-compose up -d
```

### 3. 访问系统

- 管理后台：http://localhost:3000/admin/
- API 地址：http://localhost:3000/api/

### 4. 默认账号

- 用户名：`admin`
- 密码：`admin123`

### 5. 验证部署

```bash
# 检查容器状态
docker ps

# 测试登录 API
curl -X POST http://localhost:3000/api/admin/login \
  -F "username=admin" \
  -F "password=admin123"

# 查看容器日志
docker logs video_nginx
docker logs video_php
docker logs video_mysql
```

## 系统架构

### Docker 容器架构

本项目采用 Docker Compose 编排，包含 3 个容器：

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Host (宿主机)                       │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  video_nginx (nginx:stable)                         │    │
│  │  端口: 3000:80                                       │    │
│  │  ┌──────────────┐  ┌──────────────────────────┐    │    │
│  │  │ 静态文件服务  │  │ FastCGI 反向代理          │    │    │
│  │  │ /admin/*     │  │ /api/* -> php:9000       │    │    │
│  │  └──────────────┘  └──────────────────────────┘    │    │
│  │  Volume: ./frontend/dist -> /usr/share/nginx/html/admin │
│  │  Volume: ./backend -> /var/www/html                 │    │
│  └────────────────────┬────────────────────────────────┘    │
│                       │ FastCGI (9000)                       │
│  ┌────────────────────▼────────────────────────────────┐    │
│  │  video_php (php:8.2-fpm)                            │    │
│  │  扩展: PDO, MySQLi, OPcache                         │    │
│  │  ┌──────────────┐  ┌──────────────┐               │    │
│  │  │ 路由分发      │  │ 业务逻辑      │               │    │
│  │  │ index.php    │  │ routes/*     │               │    │
│  │  └──────────────┘  └──────────────┘               │    │
│  │  Volume: ./backend -> /var/www/html                │    │
│  │  环境变量: DB_HOST=mysql, DB_NAME=video_app        │    │
│  └────────────────────┬────────────────────────────────┘    │
│                       │ MySQL Protocol (3306)                │
│  ┌────────────────────▼────────────────────────────────┐    │
│  │  video_mysql (mysql:8.0)                            │    │
│  │  端口: 3308:3306                                     │    │
│  │  字符集: utf8mb4                                     │    │
│  │  ┌──────────────┐  ┌──────────────┐               │    │
│  │  │ 数据库        │  │ 健康检查      │               │    │
│  │  │ video_app    │  │ mysqladmin   │               │    │
│  │  └──────────────┘  └──────────────┘               │    │
│  │  Volume: mysql_data (持久化)                        │    │
│  │  Volume: ./mysql/init -> /docker-entrypoint-initdb.d │  │
│  └─────────────────────────────────────────────────────┘    │
│                                                               │
│  Network: video_network (bridge)                             │
└─────────────────────────────────────────────────────────────┘
```

**容器依赖关系**:

- nginx 依赖 php
- php 依赖 mysql (健康检查)
- 启动顺序: mysql → php → nginx

**Volume 挂载说明**:

- 前端代码: `./frontend/dist` 挂载到 nginx 容器，代码更新后重新构建即可
- 后端代码: `./backend` 挂载到 nginx 和 php 容器，代码修改自动生效
- 数据库初始化: `./mysql/init` 挂载到 mysql 容器，首次启动时自动执行
- 数据持久化: `mysql_data` Docker volume，数据不会因容器删除而丢失

**网络配置**:

- 容器间通信: 使用 Docker 内部网络 `video_network`
- 外部访问: 仅 nginx (3000) 和 mysql (3308) 暴露端口
- PHP-FPM: 仅在内部网络可访问，不对外暴露

### 整体架构图

```
┌─────────────────────────────────────────────────────────────┐
│                         用户层                               │
├─────────────────────────────────────────────────────────────┤
│  管理员浏览器          │          移动APP                    │
│  (Vue3 SPA)           │          (调用API)                  │
└──────────┬────────────┴──────────────┬─────────────────────┘
           │                           │
           │ HTTP/HTTPS                │ HTTP/HTTPS
           │                           │
┌──────────▼───────────────────────────▼─────────────────────┐
│                    Nginx (反向代理)                          │
│  ┌─────────────────┐      ┌──────────────────────┐         │
│  │  静态资源服务    │      │   API 反向代理        │         │
│  │  /admin/*       │      │   /api/* -> PHP      │         │
│  └─────────────────┘      └──────────────────────┘         │
└──────────────────────────────┬─────────────────────────────┘
                               │
                               │ FastCGI
                               │
┌──────────────────────────────▼─────────────────────────────┐
│                    PHP-FPM (业务层)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  路由分发     │  │  业务逻辑     │  │  数据验证     │     │
│  │  index.php   │  │  routes/*    │  │  helpers.php │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└──────────────────────────────┬─────────────────────────────┘
                               │
                               │ PDO
                               │
┌──────────────────────────────▼─────────────────────────────┐
│                    MySQL 8.0 (数据层)                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  admin_user  │  │  video       │  │  video_source│     │
│  │  admin_token │  │              │  │              │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

### 技术架构

#### 1. 前端架构 (Vue 3)

```
frontend/src/
├── main.js                 # 应用入口，注册全局组件和插件
├── App.vue                 # 根组件
├── router/
│   └── index.js           # 路由配置，包含路由守卫
├── api/
│   └── index.js           # API 接口封装，使用 FormData
├── utils/
│   └── request.js         # Axios 封装，统一错误处理
└── views/
    ├── Login.vue          # 登录页（表单验证）
    ├── Layout.vue         # 布局组件（侧边栏+顶栏）
    ├── Dashboard.vue      # 首页
    ├── VideoList.vue      # 影片列表（分页+搜索）
    ├── VideoForm.vue      # 影片表单（新增/编辑）
    └── VideoSources.vue   # 播放源管理（对话框）
```

## 技术细节

- **状态管理**: 使用 localStorage 存储 token 和用户信息
- **路由守卫**: 未登录自动跳转到登录页
- **表单验证**: Element Plus 内置验证 + 自定义验证器
- **错误处理**: Axios 拦截器统一处理，401 自动跳转登录
- **懒加载**: 所有路由组件使用动态导入
- **国际化**: Element Plus 配置中文语言包

#### 2. 后端架构 (PHP)

```
backend/
├── api/
│   ├── index.php          # 主入口，路由分发
│   └── routes/
│       ├── admin.php      # 管理员认证（登录/退出）
│       ├── videos.php     # 影片 CRUD
│       ├── sources.php    # 播放源 CRUD
│       └── app.php        # APP API（无需认证）
└── config/
    ├── database.php       # PDO 连接池
    └── helpers.php        # 工具函数（验证/响应/安全）
```

**后端技术细节**:

- **路由机制**: 基于 URL path 的简单路由分发
- **认证方式**: Bearer Token，存储在 admin_token 表
- **数据库访问**: PDO 预处理语句，防止 SQL 注入
- **事务支持**: 删除操作使用事务保证一致性
- **输入验证**: 多层验证（必填/长度/格式/类型）
- **输出安全**: htmlspecialchars 防止 XSS
- **错误处理**: 统一 JSON 响应格式，HTTP 200 + 自定义错误码

#### 3. 数据库架构 (MySQL)

```
video_app (utf8mb4)
├── admin_user          # 管理员表
│   ├── id (PK)
│   ├── username (UNIQUE)
│   └── password_hash
├── admin_token         # Token 表
│   ├── id (PK)
│   ├── admin_id (FK)
│   ├── token (UNIQUE, INDEX)
│   └── expire_at
├── video               # 影片表
│   ├── id (PK)
│   ├── title
│   ├── cover_url
│   ├── description
│   ├── status (INDEX)
│   └── created_at, updated_at
└── video_source        # 播放源表
    ├── id (PK)
    ├── video_id (FK, INDEX)
    ├── source_name
    └── m3u8_url
```

**数据库技术细节**:

- **字符集**: utf8mb4，支持 emoji 和特殊字符
- **索引策略**: status、video_id、token 字段建立索引
- **外键关系**: video_source.video_id 关联 video.id
- **时间戳**: 使用 DATETIME 类型，格式 YYYY-MM-DD HH:mm:ss
- **默认值**: created_at 使用 CURRENT_TIMESTAMP

## 项目结构

```
.
├── backend/                # 后端代码
│   ├── api/               # API 入口和路由
│   │   ├── index.php     # 主入口文件（路由分发）
│   │   └── routes/       # 路由文件
│   │       ├── admin.php    # 管理员相关（登录/退出/信息）
│   │       ├── videos.php   # 影片管理（CRUD + 状态）
│   │       ├── sources.php  # 播放源管理（CRUD）
│   │       └── app.php      # APP API（只读接口）
│   └── config/            # 配置文件
│       ├── database.php  # 数据库配置（PDO 连接）
│       └── helpers.php   # 辅助函数（验证/响应/安全）
├── frontend/              # 前端代码
│   ├── src/
│   │   ├── api/          # API 接口封装
│   │   │   └── index.js  # 所有 API 方法（使用 FormData）
│   │   ├── router/       # 路由配置
│   │   │   └── index.js  # 路由定义 + 守卫
│   │   ├── utils/        # 工具函数
│   │   │   └── request.js # Axios 封装 + 拦截器
│   │   ├── views/        # 页面组件
│   │   │   ├── Login.vue          # 登录页
│   │   │   ├── Layout.vue         # 布局组件
│   │   │   ├── Dashboard.vue      # 首页
│   │   │   ├── VideoList.vue      # 影片列表
│   │   │   ├── VideoForm.vue      # 影片表单
│   │   │   └── VideoSources.vue   # 播放源管理
│   │   ├── App.vue       # 根组件
│   │   └── main.js       # 入口文件
│   ├── index.html        # HTML 模板
│   ├── package.json      # 依赖配置
│   └── vite.config.js    # Vite 配置
├── mysql/                 # MySQL 配置
│   └── init/
│       └── init.sql      # 数据库初始化脚本（建表 + seed）
├── nginx/                 # Nginx 配置
│   └── nginx.conf        # Nginx 配置文件（反向代理 + 缓存）
├── docker-compose.yml     # Docker Compose 配置
├── deploy.sh             # 一键部署脚本
├── test_api.sh           # API 测试脚本
└── README.md             # 项目说明
```

## 数据库设计

### 表结构详解

#### admin_user（管理员用户）

| 字段          | 类型         | 说明     | 约束                                |
| ------------- | ------------ | -------- | ----------------------------------- |
| id            | BIGINT       | 主键     | PK, AUTO_INCREMENT                  |
| username      | VARCHAR(50)  | 用户名   | UNIQUE, NOT NULL                    |
| password_hash | VARCHAR(100) | 密码哈希 | NOT NULL                            |
| created_at    | DATETIME     | 创建时间 | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

#### admin_token（管理员令牌）

| 字段       | 类型        | 说明     | 约束                                |
| ---------- | ----------- | -------- | ----------------------------------- |
| id         | BIGINT      | 主键     | PK, AUTO_INCREMENT                  |
| admin_id   | BIGINT      | 管理员ID | NOT NULL, INDEX                     |
| token      | VARCHAR(64) | 令牌     | UNIQUE, NOT NULL, INDEX             |
| expire_at  | DATETIME    | 过期时间 | NOT NULL                            |
| created_at | DATETIME    | 创建时间 | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

#### video（影片）

| 字段        | 类型         | 说明                | 约束                                                            |
| ----------- | ------------ | ------------------- | --------------------------------------------------------------- |
| id          | BIGINT       | 主键                | PK, AUTO_INCREMENT                                              |
| title       | VARCHAR(200) | 影片标题            | NOT NULL                                                        |
| cover_url   | VARCHAR(255) | 封面URL             | NOT NULL                                                        |
| description | TEXT         | 影片描述            | NULL                                                            |
| status      | TINYINT      | 状态（1上架/0下架） | NOT NULL, DEFAULT 1, INDEX                                      |
| created_at  | DATETIME     | 创建时间            | NOT NULL, DEFAULT CURRENT_TIMESTAMP                             |
| updated_at  | DATETIME     | 更新时间            | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

#### video_source（播放源）

| 字段        | 类型         | 说明     | 约束                                |
| ----------- | ------------ | -------- | ----------------------------------- |
| id          | BIGINT       | 主键     | PK, AUTO_INCREMENT                  |
| video_id    | BIGINT       | 影片ID   | NOT NULL, INDEX                     |
| source_name | VARCHAR(50)  | 线路名称 | NOT NULL                            |
| m3u8_url    | VARCHAR(500) | M3U8地址 | NOT NULL                            |
| created_at  | DATETIME     | 创建时间 | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 索引策略

- `video.status`: 加速上下架状态查询
- `video_source.video_id`: 加速播放源关联查询
- `admin_token.token`: 加速 Token 验证
- `admin_token.admin_id`: 加速用户 Token 查询

### 数据关系

```
admin_user (1) ──< (N) admin_token
video (1) ──< (N) video_source
```

## API 接口

### 管理后台 API

#### 登录

```
POST /api/admin/login
Content-Type: multipart/form-data

参数：
- username: 用户名
- password: 密码

响应：
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "token": "...",
    "username": "admin",
    "expire_at": "2026-02-04 18:00:00"
  }
}
```

#### 退出登录

```
POST /api/admin/logout
Authorization: Bearer {token}
```

#### 获取影片列表

```
GET /api/videos?page=1&page_size=10&keyword=&status=
Authorization: Bearer {token}
```

#### 获取影片详情

```
GET /api/videos/{id}
Authorization: Bearer {token}
```

#### 新增影片

```
POST /api/videos
Authorization: Bearer {token}
Content-Type: multipart/form-data

参数：
- title: 标题（必填，1-200字符）
- cover_url: 封面URL（必填，URL格式）
- description: 描述（选填，最多1000字符）
- status: 状态（1上架/0下架）
```

#### 更新影片

```
POST /api/videos/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data

参数同新增影片
```

#### 删除影片

```
DELETE /api/videos/{id}
Authorization: Bearer {token}
```

#### 更新影片状态

```
POST /api/videos/{id}/status
Authorization: Bearer {token}
Content-Type: multipart/form-data

参数：
- status: 状态（1上架/0下架）
```

#### 获取播放源列表

```
GET /api/sources?video_id={video_id}
Authorization: Bearer {token}
```

#### 新增播放源

```
POST /api/sources
Authorization: Bearer {token}
Content-Type: multipart/form-data

参数：
- video_id: 影片ID（必填）
- source_name: 线路名称（必填，1-50字符）
- m3u8_url: M3U8地址（必填，URL格式，必须以.m3u8结尾）
```

#### 更新播放源

```
POST /api/sources/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data

参数：
- source_name: 线路名称
- m3u8_url: M3U8地址
```

#### 删除播放源

```
DELETE /api/sources/{id}
Authorization: Bearer {token}
```

### APP API（无需认证）

#### 获取上架影片列表

```
GET /api/app/videos?page=1&page_size=10
```

#### 获取影片详情

```
GET /api/app/videos/{id}
```

#### 获取播放源列表

```
GET /api/app/videos/{id}/sources
```

## 测试

本项目包含完整的测试体系，共 **88 个测试用例**，覆盖后端和前端所有功能。

### 快速运行测试

```bash
# 运行所有测试（后端 + 前端）
./run-tests.sh

# 仅运行后端测试
./test-backend.sh

# 仅运行前端测试
./test-frontend.sh
```

### 测试覆盖

#### 后端测试（PHPUnit）- 34 个用例

- ✅ 单元测试：辅助函数测试
- ✅ 集成测试：管理员登录 API
- ✅ 集成测试：影片 CRUD API
- ✅ 集成测试：播放源 CRUD API
- ✅ 集成测试：APP 公开 API

```bash
# 运行后端测试
vendor/bin/phpunit

# 生成覆盖率报告
vendor/bin/phpunit --coverage-html coverage
```

#### 前端测试（Vitest + Cypress）- 54 个用例

- ✅ 组件测试：HTTP 请求工具
- ✅ 组件测试：登录组件
- ✅ 组件测试：影片列表组件
- ✅ 组件测试：影片表单组件
- ✅ E2E 测试：登录流程
- ✅ E2E 测试：影片管理流程
- ✅ E2E 测试：播放源管理流程

```bash
cd frontend

# 运行组件测试
npm run test

# 运行 E2E 测试（需要先启动服务）
npm run e2e

# 生成覆盖率报告
npm run test:coverage
```

### 详细测试文档

查看 [TESTING.md](./TESTING.md) 了解：

- 测试架构和技术栈
- 如何编写测试
- CI/CD 集成
- 故障排查指南

## 特性说明

### 1. UTF-8 编码支持

- MySQL 使用 utf8mb4 字符集
- Nginx 配置 charset utf-8
- PHP 连接使用 UTF-8
- 前端 meta charset=utf-8
- API 响应头包含 charset=utf-8

### 2. 统一错误处理

- 所有业务接口 HTTP 状态码返回 200
- 返回体包含自定义错误码和中文错误信息
- 前端统一拦截并展示错误信息

### 3. 表单验证

- 前端使用 Element Plus 表单验证
- 后端进行二次验证
- 所有错误提示均为中文

### 4. 时间格式

- 统一使用 YYYY-MM-DD HH:mm:ss 格式
- 禁止使用 ISO 8601 带 T 的格式

### 5. 安全性

- Token 认证机制
- 密码哈希验证（password_verify）
- SQL 预处理防注入
- XSS 输入清理（sanitizeInput）
- Nginx 禁止访问敏感文件
- 数据库事务保证数据一致性

### 6. 性能优化

- PHP OPcache 字节码缓存
- Nginx Gzip 压缩
- 静态资源缓存（7天）
- FastCGI 缓冲优化
- 数据库索引优化
- 前端路由懒加载
- MySQL 健康检查

## 停止服务

```bash
# 停止所有容器
docker-compose down

# 停止并删除 volumes（会清空数据库数据）
docker-compose down -v

# 重启所有容器
docker-compose restart

# 重启单个容器
docker-compose restart nginx
docker-compose restart php
docker-compose restart mysql
```

## Docker 运维命令

### 容器管理

```bash
# 查看容器状态
docker ps

# 查看所有容器（包括停止的）
docker ps -a

# 进入容器
docker exec -it video_nginx sh
docker exec -it video_php bash
docker exec -it video_mysql bash

# 查看容器资源使用
docker stats
```

### 日志管理

```bash
# 查看所有服务日志
docker-compose logs -f

# 查看特定服务日志
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f mysql

# 查看最近 100 行日志
docker-compose logs --tail=100 nginx

# 查看特定时间的日志
docker-compose logs --since 2026-01-29T00:00:00
```

### 数据库管理

```bash
# 连接数据库
docker exec -it video_mysql mysql -uroot -proot123 video_app

# 导出数据库
docker exec video_mysql mysqldump -uroot -proot123 video_app > backup.sql

# 导入数据库
docker exec -i video_mysql mysql -uroot -proot123 video_app < backup.sql

# 查看数据库状态
docker exec video_mysql mysql -uroot -proot123 -e "SHOW DATABASES;"
docker exec video_mysql mysql -uroot -proot123 -e "USE video_app; SHOW TABLES;"
```

### 代码更新

```bash
# 前端代码更新
cd frontend
npm run build
cd ..
# 由于使用 volume 挂载，构建后自动生效，无需重启容器

# 后端代码更新
# 直接修改 backend/ 目录下的文件
# 由于使用 volume 挂载，修改自动生效，无需重启容器
# 如果修改了 PHP 配置，需要重启 PHP 容器
docker-compose restart php

# Nginx 配置更新
# 修改 nginx/nginx.conf 后需要重启 nginx 容器
docker-compose restart nginx
```

### 清理和重建

```bash
# 清理未使用的镜像
docker image prune -a

# 清理未使用的容器
docker container prune

# 清理未使用的 volumes
docker volume prune

# 完全重建（清空所有数据）
docker-compose down -v
docker-compose up -d --build

# 仅重建特定服务
docker-compose up -d --build nginx
```

## 清理数据

```bash
docker-compose down -v
```

## 开发说明

### 前端开发

```bash
cd frontend
npm run dev
```

访问：http://localhost:3000

### 查看日志

```bash
# 查看所有服务日志
docker-compose logs -f

# 查看特定服务日志
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f mysql
```

## 注意事项

### Docker 部署相关

1. **首次启动**: 需要等待 MySQL 初始化完成（约 30 秒），健康检查通过后 PHP 容器才会启动
2. **端口配置**: 默认使用 3000 端口（nginx）和 3308 端口（mysql），确保这些端口未被占用
3. **镜像版本**: 所有镜像均使用非 alpine 版本（nginx:stable, php:8.2-fpm, mysql:8.0）
4. **数据持久化**: 数据库数据存储在 Docker volume `mysql_data` 中，删除容器不会丢失数据
5. **代码同步**: 使用 volume 挂载，本地代码修改会自动同步到容器中

### 前端相关

1. **构建要求**: 前端需要先构建（`npm run build`）才能访问管理后台
2. **路由配置**: 前端路由使用相对路径 `../views/`，不要使用 `./views/`
3. **开发模式**: 开发时可以使用 `npm run dev` 在本地运行，访问 http://localhost:5173

### 后端相关

1. **认证配置**: Nginx 配置中必须包含 `fastcgi_param HTTP_AUTHORIZATION $http_authorization;`，否则会出现 401 错误
2. **登录路由**: 支持两种登录路径 `/api/login` 和 `/api/admin/login`
3. **FormData 格式**: 所有 POST 请求使用 `multipart/form-data` 格式，不使用 JSON
4. **时间格式**: 统一使用 `YYYY-MM-DD HH:mm:ss` 格式，禁止使用 ISO 8601 带 T 的格式

### 数据库相关

1. **字符集**: 使用 utf8mb4 字符集，支持 emoji 和特殊字符
2. **初始化**: 首次启动时会自动执行 `mysql/init/init.sql` 脚本
3. **默认数据**: 包含 1 个管理员账号、8 条影片数据、13 个播放源数据
4. **备份建议**: 定期使用 `mysqldump` 备份数据库

### 安全相关

1. **生产环境**: 部署到生产环境前，请修改默认密码和数据库密码
2. **Token 过期**: Token 默认有效期为 7 天，过期后需要重新登录
3. **SQL 注入**: 所有数据库操作使用 PDO 预处理语句，防止 SQL 注入
4. **XSS 防护**: 所有输出使用 `htmlspecialchars` 处理，防止 XSS 攻击

### 性能相关

1. **OPcache**: PHP 容器已启用 OPcache，提升 40-50% 性能
2. **Gzip 压缩**: Nginx 已启用 Gzip 压缩，减少传输大小
3. **静态资源缓存**: 静态资源缓存 7 天，提升加载速度
4. **数据库索引**: 已对常用查询字段建立索引，提升查询性能

## 常见问题

### Q1: 前端构建失败，提示找不到模块？

**A**: 检查 `frontend/src/router/index.js` 中的路由路径是否正确使用 `../views/` 而不是 `./views/`。

### Q2: 登录后所有操作都返回 401？

**A**: 检查 `nginx/nginx.conf` 中是否包含 `fastcgi_param HTTP_AUTHORIZATION $http_authorization;` 配置。

### Q3: 如何修改默认密码？

**A**: 修改 `mysql/init/init.sql` 中的 password_hash 字段，使用 PHP 的 `password_hash()` 函数生成新的哈希值。

### Q4: 如何添加新的 API 接口？

**A**: 在 `backend/api/routes/` 目录下对应的文件中添加新函数，然后在路由处理函数中添加路由规则。

### Q5: 如何修改数据库配置？

**A**: 修改 `docker-compose.yml` 中的环境变量，或修改 `backend/config/database.php` 中的配置。

### Q6: 如何部署到生产环境？

**A**: 参考 `SECURITY.md` 和 `OPTIMIZATION.md` 文档，实施安全加固和性能优化后部署。

### Q7: 如何扩展前端功能？

**A**: 在 `frontend/src/views/` 目录下添加新的 Vue 组件，在 `router/index.js` 中添加路由。

## 文档

- **README.md** - 项目说明、快速开始、API 文档
- **TESTING.md** - 测试文档、测试指南、测试覆盖率
- **CHECKLIST.md** - PRD 需求验收清单
- **IMPLEMENTATION.md** - 实现总结
- **OPTIMIZATION.md** - 性能优化指南
- **SECURITY.md** - 安全最佳实践
- **prd.md** - 产品需求文档

## 许可证

MIT
