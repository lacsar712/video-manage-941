#!/bin/bash

# 启动PHP后端服务器
# 使用方法: ./start-server.sh

cd "$(dirname "$0")"

# 设置数据库环境变量
export DB_HOST=127.0.0.1:3308
export DB_NAME=video_app
export DB_USER=root
export DB_PASS=root123

# 检查端口是否被占用
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "端口 8000 已被占用，正在停止旧进程..."
    pkill -f "php -S localhost:8000"
    sleep 1
fi

# 启动服务器
echo "正在启动PHP服务器 (http://localhost:8000)..."
php -S localhost:8000 index.php > php-server.log 2>&1 &

sleep 1

# 检查是否启动成功
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "✓ PHP服务器已启动"
    echo "  访问地址: http://localhost:8000"
    echo "  日志文件: php-server.log"
    echo ""
    echo "停止服务器: pkill -f 'php -S localhost:8000'"
else
    echo "✗ 启动失败，请查看 php-server.log"
    exit 1
fi
