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
    </div>

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
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { Film, Plus, ArrowRight, VideoCamera, Link, Connection } from '@element-plus/icons-vue'

const router = useRouter()

const goToVideos = () => {
  router.push('/videos')
}

const goToAddVideo = () => {
  router.push('/videos/new')
}
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
  grid-template-columns: repeat(2, 1fr);
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

.feature-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
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
