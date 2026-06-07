import { createRouter, createWebHistory } from 'vue-router'
import { ElMessage } from 'element-plus'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('../views/Layout.vue'),
    meta: { requiresAuth: true },
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue')
      },
      {
        path: 'videos',
        name: 'VideoList',
        component: () => import('../views/VideoList.vue')
      },
      {
        path: 'videos/new',
        name: 'VideoNew',
        component: () => import('../views/VideoForm.vue')
      },
      {
        path: 'videos/:id/edit',
        name: 'VideoEdit',
        component: () => import('../views/VideoForm.vue')
      },
      {
        path: 'videos/:id/sources',
        name: 'VideoSources',
        component: () => import('../views/VideoSources.vue')
      },
      {
        path: 'videos/:id/subtitles',
        name: 'VideoSubtitles',
        component: () => import('../views/VideoSubtitles.vue')
      },
      {
        path: 'scheduled-tasks',
        name: 'ScheduledTasks',
        component: () => import('../views/ScheduledTasks.vue')
      },
      {
        path: 'media',
        name: 'MediaLibrary',
        component: () => import('../views/MediaLibrary.vue')
      },
      {
        path: 'client-releases',
        name: 'ClientReleases',
        component: () => import('../views/ClientReleases.vue')
      },
      {
        path: 'collections',
        name: 'VideoCollectionList',
        component: () => import('../views/VideoCollectionList.vue')
      },
      {
        path: 'collections/new',
        name: 'VideoCollectionNew',
        component: () => import('../views/VideoCollectionForm.vue')
      },
      {
        path: 'collections/:id/edit',
        name: 'VideoCollectionEdit',
        component: () => import('../views/VideoCollectionForm.vue')
      },
      {
        path: 'collections/:id',
        name: 'VideoCollectionDetail',
        component: () => import('../views/VideoCollectionDetail.vue')
      },
      {
        path: 'content-ratings',
        name: 'ContentRating',
        component: () => import('../views/ContentRating.vue')
      },
      {
        path: 'recommend-slots',
        name: 'RecommendSlots',
        component: () => import('../views/RecommendSlots.vue')
      },
      {
        path: 'announcements',
        name: 'AnnouncementManage',
        component: () => import('../views/AnnouncementManage.vue')
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('../views/Reports.vue')
      },
      {
        path: 'dev/api-console',
        name: 'ApiConsole',
        component: () => import('../views/ApiConsole.vue'),
        meta: { requiresAdmin: true }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes
})

// 路由守卫
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token')
  const role = localStorage.getItem('role')

  if (to.meta.requiresAuth && !token) {
    ElMessage.warning('请先登录')
    next('/login')
  } else if (to.meta.requiresAdmin && role !== 'admin') {
    ElMessage.warning('该功能仅管理员可访问')
    next('/dashboard')
  } else if (to.path === '/login' && token) {
    next('/')
  } else {
    next()
  }
})

export default router
