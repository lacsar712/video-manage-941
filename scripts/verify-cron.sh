#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$BASE_DIR/backend"
CRON_DIR="$BACKEND_DIR/cron"
LOG_FILE="$BACKEND_DIR/logs/cron.log"
PHP_BIN="${PHP_BIN:-php}"

PASSED=0
FAILED=0
VIDEO_ID=""
TASK_ID=""
STAT_DATE=""

cleanup() {
    if [ -n "$TASK_ID" ] || [ -n "$VIDEO_ID" ]; then
        echo ""
        echo "=== Cleaning up test data (trap) ==="
        $PHP_BIN -r '
require_once "'"$BACKEND_DIR"'/config/database.php";
$db = getDB();
$taskId = '"${TASK_ID:-0}"';
$videoId = '"${VIDEO_ID:-0}"';
if ($taskId > 0) {
    $stmt = $db->prepare("DELETE FROM scheduled_task WHERE id = ?");
    $stmt->execute([$taskId]);
    $stmt = $db->prepare("DELETE FROM operation_log WHERE target_type = \"scheduled_task\" AND target_id = ?");
    $stmt->execute([$taskId]);
}
if ($videoId > 0) {
    $stmt = $db->prepare("DELETE FROM video WHERE id = ?");
    $stmt->execute([$videoId]);
    $stmt = $db->prepare("DELETE FROM operation_log WHERE target_type = \"video\" AND target_id = ?");
    $stmt->execute([$videoId]);
}
' 2>/dev/null || true
    fi
}
trap cleanup EXIT

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() {
    PASSED=$((PASSED + 1))
    echo -e "  ${GREEN}✓${NC} $1"
}

fail() {
    FAILED=$((FAILED + 1))
    echo -e "  ${RED}✗${NC} $1"
}

section() {
    echo ""
    echo -e "${YELLOW}=== $1 ===${NC}"
}

echo "=========================================="
echo " Cron Job Verification Script"
echo "=========================================="
echo "Backend dir : $BACKEND_DIR"
echo "Cron dir    : $CRON_DIR"
echo "Log file    : $LOG_FILE"
echo "PHP binary  : $PHP_BIN"
echo "=========================================="

mkdir -p "$BACKEND_DIR/logs"
touch "$LOG_FILE"

TEST_VIDEO_TITLE="Cron Test Video $(date +%s)"
TEST_VIDEO_COVER="/uploads/covers/test-cover-1.jpg"
TEST_VIDEO_DESC="Video created by verify-cron.sh for testing cron jobs"

section "Preparing test data"

$PHP_BIN -r '
require_once "'"$BACKEND_DIR"'/config/database.php";
$db = getDB();

$title = "'"$TEST_VIDEO_TITLE"'";
$cover = "'"$TEST_VIDEO_COVER"'";
$desc  = "'"$TEST_VIDEO_DESC"'";

$stmt = $db->prepare("INSERT INTO video (title, cover_url, description, status, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())");
$stmt->execute([$title, $cover, $desc]);
$videoId = (int)$db->lastInsertId();
echo "VIDEO_ID=$videoId\n";

$executeAt = date("Y-m-d H:i:s", time() - 60);
$stmt = $db->prepare("INSERT INTO scheduled_task (video_id, action, execute_at, status, created_by, created_at, updated_at) VALUES (?, \"publish\", ?, \"pending\", 1, NOW(), NOW())");
$stmt->execute([$videoId, $executeAt]);
$taskId = (int)$db->lastInsertId();
echo "TASK_ID=$taskId\n";
' > /tmp/cron_test_ids.txt

VIDEO_ID=$(grep '^VIDEO_ID=' /tmp/cron_test_ids.txt | cut -d'=' -f2)
TASK_ID=$(grep '^TASK_ID=' /tmp/cron_test_ids.txt | cut -d'=' -f2)
rm -f /tmp/cron_test_ids.txt

if [ -z "$VIDEO_ID" ] || [ -z "$TASK_ID" ]; then
    echo "Failed to create test data"
    exit 1
fi

pass "Created test video #$VIDEO_ID"
pass "Created pending scheduled task #$TASK_ID (publish action)"

INITIAL_LOG_SIZE=$(wc -c < "$LOG_FILE" 2>/dev/null || echo 0)

section "Running check_scheduled_tasks.php"

if [ ! -f "$CRON_DIR/check_scheduled_tasks.php" ]; then
    fail "Script not found: $CRON_DIR/check_scheduled_tasks.php"
else
    OUTPUT=$($PHP_BIN "$CRON_DIR/check_scheduled_tasks.php" 2>&1)
    EXIT_CODE=$?

    if [ $EXIT_CODE -eq 0 ]; then
        pass "check_scheduled_tasks.php exited with code 0"
    else
        fail "check_scheduled_tasks.php exited with code $EXIT_CODE"
    fi

    if echo "$OUTPUT" | grep -q "定时任务轮询开始"; then
        pass "Output contains '定时任务轮询开始'"
    else
        fail "Output missing '定时任务轮询开始'"
        echo "    Output was: $OUTPUT"
    fi

    if echo "$OUTPUT" | grep -q "找到"; then
        pass "Output contains task count message"
    else
        fail "Output missing task count message"
    fi

    if echo "$OUTPUT" | grep -q "任务 #$TASK_ID"; then
        pass "Output references our test task #$TASK_ID"
    else
        fail "Output does not reference test task #$TASK_ID"
    fi

    if echo "$OUTPUT" | grep -q "任务 #$TASK_ID 执行成功"; then
        pass "Output indicates task #$TASK_ID executed successfully"
    else
        fail "Output does not indicate success for task #$TASK_ID"
    fi
fi

section "Verifying log file (backend/logs/cron.log)"

LOG_SIZE=$(wc -c < "$LOG_FILE" 2>/dev/null || echo 0)
if [ "$LOG_SIZE" -gt "$INITIAL_LOG_SIZE" ]; then
    pass "Log file grew (from $INITIAL_LOG_SIZE to $LOG_SIZE bytes)"
else
    fail "Log file did not grow (size: $LOG_SIZE bytes)"
fi

LOG_TAIL=$(tail -n 50 "$LOG_FILE")

if echo "$LOG_TAIL" | grep -q "定时任务轮询开始"; then
    pass "Log file contains '定时任务轮询开始'"
else
    fail "Log file missing '定时任务轮询开始'"
fi

if echo "$LOG_TAIL" | grep -q "任务 #$TASK_ID"; then
    pass "Log file references test task #$TASK_ID"
else
    fail "Log file does not reference test task #$TASK_ID"
fi

section "Verifying database state after check_scheduled_tasks.php"

$PHP_BIN -r '
require_once "'"$BACKEND_DIR"'/config/database.php";
$db = getDB();

$taskId = '"$TASK_ID"';
$videoId = '"$VIDEO_ID"';

$stmt = $db->prepare("SELECT status, result_message FROM scheduled_task WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();
echo "TASK_STATUS=" . ($task["status"] ?? "NOT_FOUND") . "\n";
echo "TASK_RESULT=" . ($task["result_message"] ?? "") . "\n";

$stmt = $db->prepare("SELECT status FROM video WHERE id = ?");
$stmt->execute([$videoId]);
$video = $stmt->fetch();
echo "VIDEO_STATUS=" . ($video["status"] ?? "NOT_FOUND") . "\n";
' > /tmp/cron_test_state.txt

TASK_STATUS=$(grep '^TASK_STATUS=' /tmp/cron_test_state.txt | cut -d'=' -f2)
TASK_RESULT=$(grep '^TASK_RESULT=' /tmp/cron_test_state.txt | cut -d'=' -f2-)
VIDEO_STATUS=$(grep '^VIDEO_STATUS=' /tmp/cron_test_state.txt | cut -d'=' -f2)
rm -f /tmp/cron_test_state.txt

if [ "$TASK_STATUS" = "executed" ]; then
    pass "Task #$TASK_ID status is 'executed' (got: $TASK_STATUS)"
else
    fail "Task #$TASK_ID status is not 'executed' (got: $TASK_STATUS)"
fi

if [ "$VIDEO_STATUS" = "1" ]; then
    pass "Video #$VIDEO_ID status is 1 (published) (got: $VIDEO_STATUS)"
else
    fail "Video #$VIDEO_ID status is not 1 (published) (got: $VIDEO_STATUS)"
fi

if echo "$TASK_RESULT" | grep -q "成功"; then
    pass "Task result message indicates success"
else
    fail "Task result message does not indicate success (got: $TASK_RESULT)"
fi

section "Running generate_daily_snapshot.php"

INITIAL_LOG_SIZE_2=$(wc -c < "$LOG_FILE" 2>/dev/null || echo 0)
STAT_DATE=$(date -d "yesterday" +%Y-%m-%d 2>/dev/null || date -v-1d +%Y-%m-%d 2>/dev/null)

if [ ! -f "$CRON_DIR/generate_daily_snapshot.php" ]; then
    fail "Script not found: $CRON_DIR/generate_daily_snapshot.php"
else
    OUTPUT_2=$($PHP_BIN "$CRON_DIR/generate_daily_snapshot.php" 2>&1)
    EXIT_CODE_2=$?

    if [ $EXIT_CODE_2 -eq 0 ]; then
        pass "generate_daily_snapshot.php exited with code 0"
    else
        fail "generate_daily_snapshot.php exited with code $EXIT_CODE_2"
    fi

    if echo "$OUTPUT_2" | grep -q "每日数据快照生成开始"; then
        pass "Output contains '每日数据快照生成开始'"
    else
        fail "Output missing '每日数据快照生成开始'"
        echo "    Output was: $OUTPUT_2"
    fi

    if echo "$OUTPUT_2" | grep -q "快照生成成功"; then
        pass "Output contains '快照生成成功'"
    else
        fail "Output missing '快照生成成功'"
    fi

    if echo "$OUTPUT_2" | grep -q "每日数据快照生成完成"; then
        pass "Output contains '每日数据快照生成完成'"
    else
        fail "Output missing '每日数据快照生成完成'"
    fi
fi

section "Verifying log file after generate_daily_snapshot.php"

LOG_SIZE_2=$(wc -c < "$LOG_FILE" 2>/dev/null || echo 0)
if [ "$LOG_SIZE_2" -gt "$INITIAL_LOG_SIZE_2" ]; then
    pass "Log file grew after snapshot script"
else
    fail "Log file did not grow after snapshot script"
fi

LOG_TAIL_2=$(tail -n 50 "$LOG_FILE")

if echo "$LOG_TAIL_2" | grep -q "每日数据快照生成开始"; then
    pass "Log file contains '每日数据快照生成开始'"
else
    fail "Log file missing '每日数据快照生成开始'"
fi

if echo "$LOG_TAIL_2" | grep -q "快照生成成功"; then
    pass "Log file contains '快照生成成功'"
else
    fail "Log file missing '快照生成成功'"
fi

section "Verifying database state after generate_daily_snapshot.php"

$PHP_BIN -r '
require_once "'"$BACKEND_DIR"'/config/database.php";
$db = getDB();

$statDate = "'"$STAT_DATE"'";
$stmt = $db->prepare("SELECT id, video_total, video_published, source_total, new_videos FROM daily_stats_snapshot WHERE stat_date = ?");
$stmt->execute([$statDate]);
$snap = $stmt->fetch();

if ($snap) {
    echo "SNAPSHOT_FOUND=1\n";
    echo "SNAPSHOT_ID=" . $snap["id"] . "\n";
    echo "VIDEO_TOTAL=" . $snap["video_total"] . "\n";
    echo "VIDEO_PUBLISHED=" . $snap["video_published"] . "\n";
    echo "SOURCE_TOTAL=" . $snap["source_total"] . "\n";
} else {
    echo "SNAPSHOT_FOUND=0\n";
}
' > /tmp/cron_test_snap.txt

SNAPSHOT_FOUND=$(grep '^SNAPSHOT_FOUND=' /tmp/cron_test_snap.txt | cut -d'=' -f2)
SNAPSHOT_ID=$(grep '^SNAPSHOT_ID=' /tmp/cron_test_snap.txt | cut -d'=' -f2)
VIDEO_TOTAL=$(grep '^VIDEO_TOTAL=' /tmp/cron_test_snap.txt | cut -d'=' -f2)
rm -f /tmp/cron_test_snap.txt

if [ "$SNAPSHOT_FOUND" = "1" ]; then
    pass "Daily snapshot record found for $STAT_DATE (id: #$SNAPSHOT_ID)"
else
    fail "Daily snapshot record NOT found for $STAT_DATE"
fi

if [ -n "$VIDEO_TOTAL" ] && [ "$VIDEO_TOTAL" -ge 0 ] 2>/dev/null; then
    pass "Snapshot has valid video_total ($VIDEO_TOTAL)"
else
    fail "Snapshot missing or invalid video_total"
fi

section "Cleaning up test data"

$PHP_BIN -r '
require_once "'"$BACKEND_DIR"'/config/database.php";
$db = getDB();

$taskId = '"$TASK_ID"';
$videoId = '"$VIDEO_ID"';
$statDate = "'"$STAT_DATE"'";

$stmt = $db->prepare("DELETE FROM scheduled_task WHERE id = ?");
$stmt->execute([$taskId]);

$stmt = $db->prepare("DELETE FROM operation_log WHERE target_type = \"scheduled_task\" AND target_id = ?");
$stmt->execute([$taskId]);

$stmt = $db->prepare("DELETE FROM video WHERE id = ?");
$stmt->execute([$videoId]);

$stmt = $db->prepare("DELETE FROM operation_log WHERE target_type = \"video\" AND target_id = ?");
$stmt->execute([$videoId]);
'
pass "Cleaned up test data (task #$TASK_ID, video #$VIDEO_ID)"

section "Results"

echo ""
echo "  Passed: $PASSED"
echo "  Failed: $FAILED"
echo ""

if [ "$FAILED" -gt 0 ]; then
    echo -e "${RED}FAILED${NC}: $FAILED assertion(s) failed"
    exit 1
else
    echo -e "${GREEN}SUCCESS${NC}: All $PASSED assertions passed"
    exit 0
fi
