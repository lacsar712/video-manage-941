#!/bin/bash

# 下载测试图片脚本
# 将 Unsplash 的测试图片下载到本地

# 设置目标目录
TARGET_DIR="$(dirname "$0")/../uploads/covers"
mkdir -p "$TARGET_DIR"

# 图片 URL 列表（从数据库初始化脚本中提取）
declare -a IMAGES=(
    "https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1594908900066-3f47337549d8?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1485846234645-a62644f84728?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1574267432644-f610a5510fdd?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1524985069026-dd778a71c7b4?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?w=300&h=450&fit=crop"
    "https://images.unsplash.com/photo-1616530940355-351fabd9524b?w=300&h=450&fit=crop"
)

echo "开始下载测试图片到: $TARGET_DIR"
echo "----------------------------------------"

# 下载每张图片
for i in "${!IMAGES[@]}"; do
    INDEX=$((i + 1))
    URL="${IMAGES[$i]}"
    FILENAME="test-cover-${INDEX}.jpg"
    FILEPATH="${TARGET_DIR}/${FILENAME}"

    echo "[$INDEX/10] 下载: $FILENAME"

    # 使用 curl 下载图片
    if curl -L -o "$FILEPATH" "$URL" --silent --show-error --fail; then
        echo "  ✓ 下载成功"
    else
        echo "  ✗ 下载失败"
    fi
done

echo "----------------------------------------"
echo "下载完成！"
echo ""
echo "图片保存位置: $TARGET_DIR"
ls -lh "$TARGET_DIR"/test-cover-*.jpg 2>/dev/null | wc -l | xargs echo "成功下载图片数量:"
