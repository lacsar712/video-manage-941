<template>
  <div class="login-container">
    <div class="login-left">
      <div class="brand-area">
        <div class="brand-icon">
          <el-icon :size="48"><VideoCamera /></el-icon>
        </div>
        <h1 class="brand-title">影视管理后台</h1>
        <p class="brand-desc">高效管理影片资源，一站式内容运营平台</p>
      </div>
    </div>
    <div class="login-right">
      <div class="login-form-wrapper">
        <h2 class="form-title">欢迎登录</h2>
        <p class="form-subtitle">请输入您的账号信息</p>

        <el-form
          ref="loginFormRef"
          :model="loginForm"
          :rules="loginRules"
          label-position="top"
          size="large"
        >
          <el-form-item prop="username">
            <el-input
              v-model="loginForm.username"
              placeholder="请输入用户名"
              :prefix-icon="User"
              clearable
            />
          </el-form-item>

          <el-form-item prop="password">
            <el-input
              v-model="loginForm.password"
              type="password"
              placeholder="请输入密码"
              :prefix-icon="Lock"
              show-password
              clearable
              @keyup.enter="handleLogin"
            />
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              style="width: 100%"
              :loading="loading"
              @click="handleLogin"
            >
              登录
            </el-button>
          </el-form-item>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock, VideoCamera } from '@element-plus/icons-vue'
import { login } from '../api'

const router = useRouter()
const loginFormRef = ref(null)
const loading = ref(false)

const loginForm = reactive({
  username: '',
  password: ''
})

const loginRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}

const handleLogin = async () => {
  if (!loginFormRef.value) return

  await loginFormRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const res = await login(loginForm)
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('username', res.data.username)
      ElMessage.success('登录成功')
      router.push('/')
    } catch (error) {
      console.error('登录失败：', error)
    } finally {
      loading.value = false
    }
  })
}
</script>

<style scoped>
.login-container {
  display: flex;
  width: 100%;
  height: 100vh;
}

.login-left {
  flex: 1;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.login-left::before {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.15);
  top: -50px;
  right: -50px;
}

.login-left::after {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.1);
  bottom: 80px;
  left: -40px;
}

.brand-area {
  text-align: center;
  color: #fff;
  z-index: 1;
  padding: 40px;
}

.brand-icon {
  width: 88px;
  height: 88px;
  border-radius: 20px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 28px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.brand-icon .el-icon {
  color: #818cf8;
}

.brand-title {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 12px;
  letter-spacing: 2px;
}

.brand-desc {
  font-size: 15px;
  color: rgba(255, 255, 255, 0.6);
  margin: 0;
  letter-spacing: 1px;
}

.login-right {
  width: 480px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
}

.login-form-wrapper {
  width: 340px;
}

.form-title {
  font-size: 26px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px;
}

.form-subtitle {
  font-size: 14px;
  color: #94a3b8;
  margin: 0 0 36px;
}

.login-form-wrapper :deep(.el-form-item) {
  margin-bottom: 22px;
}

.login-form-wrapper :deep(.el-input__wrapper) {
  border-radius: 10px;
  padding: 4px 12px;
  box-shadow: 0 0 0 1px #e2e8f0;
  transition: all 0.2s;
}

.login-form-wrapper :deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #94a3b8;
}

.login-form-wrapper :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px #6366f1;
}

.login-form-wrapper :deep(.el-button--primary) {
  height: 48px;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  background: #6366f1;
  border-color: #6366f1;
  letter-spacing: 2px;
  transition: all 0.2s;
}

.login-form-wrapper :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
}

.login-form-wrapper :deep(.el-button--primary:active) {
  transform: translateY(0);
}

@media (max-width: 768px) {
  .login-left {
    display: none;
  }
  .login-right {
    width: 100%;
  }
}
</style>
