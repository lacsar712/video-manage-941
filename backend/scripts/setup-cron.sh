#!/bin/bash

# Cron 定时任务配置脚本
# 用于在 Docker 容器中设置图片清理定时任务

# 设置工作目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(dirname "$SCRIPT_DIR")"

# 定时任务配置
# 格式：分 时 日 月 周 命令
# 默认：每天凌晨 2 点执行清理任务
CRON_SCHEDULE="0 2 * * *"

# 清理脚本路径
CLEANUP_SCRIPT="$BASE_DIR/scripts/cleanup-unused-images.php"

# 日志文件
CRON_LOG="$BASE_DIR/logs/cron.log"

# 确保日志目录存在
mkdir -p "$BASE_DIR/logs"

# 生成 cron 任务
CRON_JOB="$CRON_SCHEDULE cd $BASE_DIR && /usr/local/bin/php $CLEANUP_SCRIPT --days=30 --keep-test >> $CRON_LOG 2>&1"

echo "=========================================="
echo "图片清理定时任务配置"
echo "=========================================="
echo "脚本路径: $CLEANUP_SCRIPT"
echo "执行时间: 每天凌晨 2:00"
echo "清理策略: 删除 30 天前的未使用图片"
echo "保留测试图片: 是"
echo "日志文件: $CRON_LOG"
echo "=========================================="

# 检查是否在 Docker 容器中
if [ -f /.dockerenv ]; then
    echo "检测到 Docker 环境"

    # 安装 cron（如果未安装）
    if ! command -v cron &> /dev/null; then
        echo "安装 cron..."
        apt-get update && apt-get install -y cron
    fi

    # 添加 cron 任务
    echo "添加 cron 任务..."
    (crontab -l 2>/dev/null | grep -v "$CLEANUP_SCRIPT"; echo "$CRON_JOB") | crontab -

    # 启动 cron 服务
    echo "启动 cron 服务..."
    service cron start

    echo "✓ 定时任务配置完成"
    echo ""
    echo "查看当前 cron 任务："
    crontab -l

else
    echo "非 Docker 环境"
    echo ""
    echo "请手动添加以下 cron 任务："
    echo "$CRON_JOB"
    echo ""
    echo "或者运行以下命令："
    echo "(crontab -l 2>/dev/null; echo \"$CRON_JOB\") | crontab -"
fi

echo ""
echo "=========================================="
echo "手动测试清理脚本："
echo "=========================================="
echo "# 预览模式（不实际删除）"
echo "php $CLEANUP_SCRIPT --dry-run"
echo ""
echo "# 立即执行清理"
echo "php $CLEANUP_SCRIPT --days=30 --keep-test"
echo ""
echo "# 查看日志"
echo "tail -f $BASE_DIR/logs/cleanup-images.log"
echo "=========================================="
