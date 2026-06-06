#!/bin/bash

# 图片清理定时任务快速配置脚本
# 用于在宿主机上快速配置 cron 定时任务

echo "=========================================="
echo "图片清理定时任务配置向导"
echo "=========================================="
echo ""

# 获取项目路径
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "项目路径: $PROJECT_DIR"
echo ""

# 配置选项
echo "请选择清理策略："
echo "1. 保守策略（60 天，保留测试图片）- 推荐用于生产环境"
echo "2. 标准策略（30 天，保留测试图片）- 推荐用于一般使用"
echo "3. 激进策略（7 天，不保留测试图片）- 仅用于开发环境"
echo "4. 自定义"
echo ""
read -p "请选择 [1-4]: " choice

case $choice in
    1)
        DAYS=60
        KEEP_TEST="--keep-test"
        STRATEGY="保守策略"
        ;;
    2)
        DAYS=30
        KEEP_TEST="--keep-test"
        STRATEGY="标准策略"
        ;;
    3)
        DAYS=7
        KEEP_TEST=""
        STRATEGY="激进策略"
        ;;
    4)
        read -p "请输入天数阈值: " DAYS
        read -p "是否保留测试图片? (y/n): " keep_test_input
        if [ "$keep_test_input" = "y" ]; then
            KEEP_TEST="--keep-test"
        else
            KEEP_TEST=""
        fi
        STRATEGY="自定义策略"
        ;;
    *)
        echo "无效选择，使用默认策略（30 天，保留测试图片）"
        DAYS=30
        KEEP_TEST="--keep-test"
        STRATEGY="标准策略"
        ;;
esac

echo ""
echo "=========================================="
echo "配置信息"
echo "=========================================="
echo "策略: $STRATEGY"
echo "天数阈值: $DAYS 天"
echo "保留测试图片: $([ -n "$KEEP_TEST" ] && echo "是" || echo "否")"
echo "执行时间: 每天凌晨 2:00"
echo "=========================================="
echo ""

# 生成 cron 任务
CRON_JOB="0 2 * * * cd $PROJECT_DIR && docker exec video_php php /var/www/html/scripts/cleanup-unused-images.php --days=$DAYS $KEEP_TEST >> $PROJECT_DIR/backend/logs/cron.log 2>&1"

echo "将添加以下 cron 任务："
echo "$CRON_JOB"
echo ""

read -p "确认添加? (y/n): " confirm

if [ "$confirm" != "y" ]; then
    echo "已取消"
    exit 0
fi

# 添加 cron 任务
(crontab -l 2>/dev/null | grep -v "cleanup-unused-images.php"; echo "$CRON_JOB") | crontab -

echo ""
echo "✓ Cron 任务已添加"
echo ""
echo "=========================================="
echo "验证配置"
echo "=========================================="
echo "当前的 cron 任务："
crontab -l | grep "cleanup-unused-images.php"
echo ""

echo "=========================================="
echo "测试清理脚本"
echo "=========================================="
echo "运行以下命令测试清理脚本（预览模式）："
echo "docker exec video_php php /var/www/html/scripts/cleanup-unused-images.php --dry-run $KEEP_TEST"
echo ""

read -p "是否立即测试? (y/n): " test_now

if [ "$test_now" = "y" ]; then
    echo ""
    echo "执行测试..."
    docker exec video_php php /var/www/html/scripts/cleanup-unused-images.php --dry-run $KEEP_TEST
fi

echo ""
echo "=========================================="
echo "配置完成！"
echo "=========================================="
echo "定时任务将在每天凌晨 2:00 自动执行"
echo ""
echo "查看日志："
echo "  tail -f $PROJECT_DIR/backend/logs/cron.log"
echo "  tail -f $PROJECT_DIR/backend/logs/cleanup-images.log"
echo ""
echo "手动执行清理："
echo "  docker exec video_php php /var/www/html/scripts/cleanup-unused-images.php --days=$DAYS $KEEP_TEST"
echo ""
echo "查看备份文件："
echo "  docker exec video_php ls -lh /var/www/html/uploads/backup/"
echo ""
echo "详细文档："
echo "  cat $PROJECT_DIR/IMAGE_CLEANUP_GUIDE.md"
echo "=========================================="
