<template>
  <div class="dashboard">
    <div class="welcome-banner">
      <div class="welcome-text">
        <h2>欢迎回来</h2>
        <p>影视管理后台 — 高效管理您的影片资源</p>
      </div>
    </div>

    <div class="quick-actions">
      <div class="action-card" @click="goToVideos">
        <div class="action-icon" style="background: rgba(99,102,241,0.1);">
          <el-icon :size="28" color="#6366f1"><Film /></el-icon>
        </div>
        <div class="action-info">
          <h4>影片管理</h4>
          <p>新增、编辑、上下架影片</p>
        </div>
        <el-icon class="action-arrow"><ArrowRight /></el-icon>
      </div>

      <div class="action-card" @click="goToAddVideo">
        <div class="action-icon" style="background: rgba(16,185,129,0.1);">
          <el-icon :size="28" color="#10b981"><Plus /></el-icon>
        </div>
        <div class="action-info">
          <h4>新增影片</h4>
          <p>快速添加新的影片资源</p>
        </div>
        <el-icon class="action-arrow"><ArrowRight /></el-icon>
      </div>

      <div class="action-card" @click="goToScheduledTasks">
        <div class="action-icon" style="background: rgba(245,158,11,0.1);">
          <el-icon :size="28" color="#f59e0b"><Clock /></el-icon>
        </div>
        <div class="action-info">
          <h4>定时任务</h4>
          <p>预约影片上下架</p>
        </div>
        <el-icon class="action-arrow"><ArrowRight /></el-icon>
      </div>
    </div>

    <el-card class="feature-card" shadow="never" v-if="upcomingTasks.length > 0">
      <div class="card-title-row">
        <h3 class="section-title">即将执行的定时任务</h3>
        <el-button link type="primary" size="small" @click="goToScheduledTasks">查看全部</el-button>
      </div>
      <div class="task-list">
        <div class="task-item" v-for="task in upcomingTasks" :key="task.id">
          <div class="task-info">
            <el-tag :type="task.action === 'publish' ? 'success' : 'warning'" size="small">
              {{ task.action === 'publish' ? '上架' : '下架' }}
            </el-tag>
            <span class="task-video">{{ task.video_title }}</span>
          </div>
          <div class="task-countdown">
            <span class="countdown-label">执行时间：{{ task.execute_at }}</span>
            <span class="countdown-value">{{ getCountdown(task) }}</span>
          </div>
        </div>
      </div>
    </el-card>

    <el-card class="feature-card" shadow="never">
      <h3 class="section-title">系统功能</h3>
      <div class="feature-list">
        <div class="feature-item">
          <el-icon :size="20" color="#6366f1"><VideoCamera /></el-icon>
          <span>管理影片信息（新增、编辑、删除、上下架）</span>
        </div>
        <div class="feature-item">
          <el-icon :size="20" color="#f59e0b"><Link /></el-icon>
          <span>管理播放源（m3u8链接）</span>
        </div>
        <div class="feature-item">
          <el-icon :size="20" color="#10b981"><Connection /></el-icon>
          <span>为APP提供影片数据接口</span>
        </div>
        <div class="feature-item">
          <el-icon :size="20" color="#8b5cf6"><Clock /></el-icon>
          <span>定时任务：预约影片上下架</span>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Film, Plus, ArrowRight, VideoCamera, Link, Connection, Clock } from '@element-plus/icons-vue'
import { getUpcomingScheduledTasks } from '../api'

const router = useRouter()
const upcomingTasks = ref([])
let countdownTimer = null

const getCountdown = (task) => {
  const target = new Date(task.execute_at.replace(/-/g, '/')).getTime()
  const now = Date.now()
  const diff = target - now
  if (diff <= 0) return '即将执行'
  const days = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  const minutes = Math.floor((diff % 3600000) / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  if (days > 0) return `${days}天${hours}时${minutes}分${seconds}秒`
  if (hours > 0) return `${hours}时${minutes}分${seconds}秒`
  if (minutes > 0) return `${minutes}分${seconds}秒`
  return `${seconds}秒`
}

const fetchUpcomingTasks = async () => {
  try {
    const res = await getUpcomingScheduledTasks({ limit: 5 })
    upcomingTasks.value = res.data
  } catch (error) {
    console.error('获取即将执行的任务失败：', error)
  }
}

const goToVideos = () => {
  router.push('/videos')
}

const goToAddVideo = () => {
  router.push('/videos/new')
}

const goToScheduledTasks = () => {
  router.push('/scheduled-tasks')
}

onMounted(() => {
  fetchUpcomingTasks()
  countdownTimer = setInterval(() => {
    upcomingTasks.value = [...upcomingTasks.value]
  }, 1000)
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>

<style scoped>
.dashboard {
  max-width: 960px;
}

.welcome-banner {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  border-radius: 12px;
  padding: 36px 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}

.welcome-banner::after {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.15);
  top: -60px;
  right: -20px;
}

.welcome-text h2 {
  color: #fff;
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px;
}

.welcome-text p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  margin: 0;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.action-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #f0f0f0;
}

.action-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.action-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-info {
  flex: 1;
}

.action-info h4 {
  margin: 0 0 4px;
  font-size: 16px;
  color: #1e293b;
  font-weight: 600;
}

.action-info p {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.action-arrow {
  color: #cbd5e1;
  font-size: 16px;
}

.card-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-title-row .section-title {
  margin: 0;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.task-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: #f8fafc;
  border-radius: 8px;
}

.task-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.task-video {
  font-size: 14px;
  color: #1e293b;
  font-weight: 500;
}

.task-countdown {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.countdown-label {
  font-size: 12px;
  color: #94a3b8;
}

.countdown-value {
  font-size: 16px;
  color: #f59e0b;
  font-weight: 600;
}

.feature-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  margin-bottom: 24px;
}

.section-title {
  margin: 0 0 20px;
  font-size: 16px;
  color: #1e293b;
  font-weight: 600;
}

.feature-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  color: #475569;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
}
</style>
