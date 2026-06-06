<template>
  <el-container class="layout-container">
    <el-aside width="220px" class="sidebar">
      <div class="logo">
        <el-icon :size="24"><VideoCamera /></el-icon>
        <span class="logo-text">影视管理</span>
      </div>
      <el-menu
        :default-active="activeMenu"
        router
        background-color="transparent"
        text-color="rgba(255,255,255,0.65)"
        active-text-color="#fff"
      >
        <el-menu-item index="/dashboard">
          <el-icon><HomeFilled /></el-icon>
          <span>首页</span>
        </el-menu-item>
        <el-menu-item index="/videos">
          <el-icon><Film /></el-icon>
          <span>影片管理</span>
        </el-menu-item>
        <el-menu-item index="/scheduled-tasks">
          <el-icon><Clock /></el-icon>
          <span>定时任务</span>
        </el-menu-item>
        <el-menu-item index="/media">
          <el-icon><Picture /></el-icon>
          <span>媒资库</span>
        </el-menu-item>
        <el-menu-item index="/client-releases">
          <el-icon><Promotion /></el-icon>
          <span>版本档案</span>
        </el-menu-item>
        <el-menu-item index="/collections">
          <el-icon><List /></el-icon>
          <span>专题合集</span>
        </el-menu-item>
        <el-menu-item index="/content-ratings">
          <el-icon><Tickets /></el-icon>
          <span>内容分级</span>
        </el-menu-item>
        <el-menu-item index="/recommend-slots">
          <el-icon><MagicStick /></el-icon>
          <span>推荐位编排</span>
        </el-menu-item>
        <el-menu-item index="/announcements">
          <el-icon><Bell /></el-icon>
          <span>公告管理</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container class="main-area">
      <div v-if="showAnnouncementBar" class="announcement-bar">
        <div class="announcement-icon">
          <el-icon :size="16"><Bell /></el-icon>
        </div>
        <div class="announcement-marquee">
          <div class="marquee-track" :style="{ animationDuration: marqueeDuration + 's' }">
            <span
              v-for="(item, idx) in announcementList"
              :key="item.id"
              class="marquee-item"
            >
              <el-tag
                :type="item.type === 'maintenance' ? 'warning' : 'primary'"
                size="small"
                effect="dark"
                class="announcement-tag"
              >
                {{ item.type === 'maintenance' ? '维护' : '更新' }}
              </el-tag>
              <span class="announcement-title">{{ item.title }}</span>
              <span
                v-if="idx < announcementList.length - 1"
                class="announcement-sep"
              >&nbsp;&nbsp;|&nbsp;&nbsp;</span>
            </span>
          </div>
        </div>
        <el-button
          class="announcement-close"
          text
          type="info"
          size="small"
          @click.stop="handleCloseAnnouncement"
        >
          <el-icon><Close /></el-icon>
        </el-button>
      </div>

      <el-header class="top-header">
        <div class="header-content">
          <div class="breadcrumb">
            <el-breadcrumb separator="/">
              <el-breadcrumb-item :to="{ path: '/' }">首页</el-breadcrumb-item>
              <el-breadcrumb-item v-if="breadcrumbName">{{ breadcrumbName }}</el-breadcrumb-item>
            </el-breadcrumb>
          </div>
          <div class="user-info">
            <div class="user-avatar">
              <el-icon :size="16"><UserFilled /></el-icon>
            </div>
            <span class="user-name">{{ username }}</span>
            <el-button text type="danger" size="small" @click="handleLogout">
              <el-icon><SwitchButton /></el-icon>
              退出
            </el-button>
          </div>
        </div>
      </el-header>

      <el-main class="main-content">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { HomeFilled, VideoCamera, Film, UserFilled, SwitchButton, Clock, Picture, Promotion, List, Tickets, MagicStick, Bell, Close } from '@element-plus/icons-vue'
import { logout, getActiveAnnouncements } from '../api'

const router = useRouter()
const route = useRoute()

const username = ref(localStorage.getItem('username') || 'admin')
const announcementList = ref([])
const showAnnouncementBar = ref(false)
const marqueeDuration = ref(30)

const DISMISS_KEY = 'announcement_dismissed_ids'

const getDismissedIds = () => {
  try {
    const raw = sessionStorage.getItem(DISMISS_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

const setDismissedIds = (ids) => {
  sessionStorage.setItem(DISMISS_KEY, JSON.stringify(ids))
}

const shouldShowAnnouncement = (item) => {
  const dismissed = getDismissedIds()
  return !dismissed.includes(item.id)
}

const fetchActiveAnnouncements = async () => {
  try {
    const res = await getActiveAnnouncements()
    const all = res.data || []
    announcementList.value = all.filter(shouldShowAnnouncement)
    await nextTick()
    updateMarqueeDuration()
  } catch (error) {
    console.error('获取公告失败：', error)
  }
}

const updateMarqueeDuration = () => {
  const track = document.querySelector('.marquee-track')
  if (track) {
    const width = track.scrollWidth
    marqueeDuration.value = Math.max(20, Math.ceil(width / 50))
  }
}

const handleCloseAnnouncement = () => {
  const dismissed = getDismissedIds()
  const newIds = [...new Set([...dismissed, ...announcementList.value.map(a => a.id)])]
  setDismissedIds(newIds)
  showAnnouncementBar.value = false
}

const activeMenu = computed(() => {
  const path = route.path
  if (path.startsWith('/videos')) {
    return '/videos'
  }
  if (path.startsWith('/scheduled-tasks')) {
    return '/scheduled-tasks'
  }
  if (path.startsWith('/media')) {
    return '/media'
  }
  if (path.startsWith('/client-releases')) {
    return '/client-releases'
  }
  if (path.startsWith('/collections')) {
    return '/collections'
  }
  if (path.startsWith('/content-ratings')) {
    return '/content-ratings'
  }
  if (path.startsWith('/recommend-slots')) {
    return '/recommend-slots'
  }
  if (path.startsWith('/announcements')) {
    return '/announcements'
  }
  return path
})

const breadcrumbName = computed(() => {
  const path = route.path
  if (path === '/dashboard') return '首页'
  if (path === '/videos') return '影片管理'
  if (path === '/videos/new') return '新增影片'
  if (path === '/collections') return '专题合集'
  if (path === '/collections/new') return '新增合集'
  if (path === '/content-ratings') return '内容分级'
  if (path === '/recommend-slots') return '推荐位编排'
  if (path === '/announcements') return '公告管理'
  if (path.includes('/edit')) {
    if (path.startsWith('/collections')) return '编辑合集'
    return '编辑影片'
  }
  if (path.includes('/sources')) return '播放源管理'
  if (path.startsWith('/scheduled-tasks')) return '定时任务'
  if (path.startsWith('/media')) return '媒资库'
  if (path.startsWith('/client-releases')) return '版本档案'
  if (path.match(/^\/collections\/\d+$/)) return '合集详情'
  return ''
})

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await logout()
    localStorage.removeItem('token')
    localStorage.removeItem('username')
    sessionStorage.removeItem(DISMISS_KEY)
    ElMessage.success('退出成功')
    router.push('/login')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('退出失败：', error)
    }
  }
}

watch(announcementList, (list) => {
  showAnnouncementBar.value = list && list.length > 0
}, { immediate: true })

onMounted(() => {
  fetchActiveAnnouncements()
})
</script>

<style scoped>
.layout-container {
  height: 100vh;
}

.sidebar {
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-right: none;
  overflow-y: auto;
}

.logo {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.logo .el-icon {
  color: #818cf8;
}

.logo-text {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 2px;
}

.sidebar :deep(.el-menu) {
  border-right: none;
  padding: 8px;
}

.sidebar :deep(.el-menu-item) {
  border-radius: 8px;
  margin-bottom: 4px;
  height: 44px;
  line-height: 44px;
  transition: all 0.2s;
}

.sidebar :deep(.el-menu-item:hover) {
  background: rgba(99, 102, 241, 0.15) !important;
}

.sidebar :deep(.el-menu-item.is-active) {
  background: rgba(99, 102, 241, 0.25) !important;
  color: #fff !important;
}

.sidebar :deep(.el-menu-item.is-active .el-icon) {
  color: #818cf8;
}

.announcement-bar {
  height: 36px;
  background: linear-gradient(90deg, #fef3c7 0%, #fde68a 100%);
  border-bottom: 1px solid #fcd34d;
  display: flex;
  align-items: center;
  padding: 0 16px;
  gap: 12px;
  position: relative;
  overflow: hidden;
}

.announcement-icon {
  color: #d97706;
  flex-shrink: 0;
}

.announcement-marquee {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
}

.marquee-track {
  display: inline-block;
  white-space: nowrap;
  animation: marquee linear infinite;
  padding-left: 100%;
}

@keyframes marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-100%);
  }
}

.marquee-item {
  display: inline-block;
  font-size: 13px;
  color: #92400e;
}

.announcement-tag {
  margin-right: 8px;
}

.announcement-title {
  font-weight: 500;
}

.announcement-sep {
  color: #d97706;
  opacity: 0.6;
}

.announcement-close {
  flex-shrink: 0;
  color: #92400e !important;
}

.announcement-close :deep(.el-icon) {
  font-size: 16px;
}

.top-header {
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  height: 56px;
  display: flex;
  align-items: center;
  padding: 0 24px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.header-content {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f0f0ff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-avatar .el-icon {
  color: #6366f1;
}

.user-name {
  font-size: 14px;
  color: #475569;
  font-weight: 500;
}

.main-content {
  background: #f5f7fa;
  padding: 24px;
}
</style>
