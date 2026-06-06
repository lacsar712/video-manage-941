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

  if (to.meta.requiresAuth && !token) {
    ElMessage.warning('请先登录')
    next('/login')
  } else if (to.path === '/login' && token) {
    next('/')
  } else {
    next()
  }
})

export default router
