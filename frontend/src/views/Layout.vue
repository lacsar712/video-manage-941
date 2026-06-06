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
      </el-menu>
    </el-aside>

    <el-container class="main-area">
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
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { HomeFilled, VideoCamera, Film, UserFilled, SwitchButton } from '@element-plus/icons-vue'
import { logout } from '../api'

const router = useRouter()
const route = useRoute()

const username = ref(localStorage.getItem('username') || 'admin')

const activeMenu = computed(() => {
  const path = route.path
  if (path.startsWith('/videos')) {
    return '/videos'
  }
  return path
})

const breadcrumbName = computed(() => {
  const path = route.path
  if (path === '/dashboard') return '首页'
  if (path === '/videos') return '影片管理'
  if (path === '/videos/new') return '新增影片'
  if (path.includes('/edit')) return '编辑影片'
  if (path.includes('/sources')) return '播放源管理'
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
    ElMessage.success('退出成功')
    router.push('/login')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('退出失败：', error)
    }
  }
}
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
