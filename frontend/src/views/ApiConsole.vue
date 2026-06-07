<template>
  <div class="api-console">
    <div class="page-header">
      <h2 class="page-title">
        <el-icon><Cpu /></el-icon>
        API 调试台
      </h2>
      <p class="page-desc">可视化接口调试工具，快速验证后端接口</p>
    </div>

    <div class="console-layout">
      <div class="left-panel">
        <div class="panel-section">
          <div class="section-title">
            <el-icon><Collection /></el-icon>
            预置接口模板
          </div>
          <div class="template-list">
            <div
              v-for="tpl in apiTemplates"
              :key="tpl.name"
              class="template-item"
              :class="{ active: selectedTemplate === tpl.name }"
              @click="selectTemplate(tpl)"
            >
              <el-tag :type="getMethodTagType(tpl.method)" size="small" effect="dark" round>
                {{ tpl.method }}
              </el-tag>
              <span class="template-name">{{ tpl.name }}</span>
            </div>
          </div>
        </div>

        <div class="panel-section" style="margin-top: 20px;">
          <div class="section-title">
            <el-icon><Timer /></el-icon>
            请求历史
            <el-button link size="small" class="clear-history-btn" @click="clearHistory">清空</el-button>
          </div>
          <div class="history-list" v-if="historyList.length > 0">
            <div
              v-for="(item, idx) in historyList"
              :key="idx"
              class="history-item"
              @click="replayRequest(item)"
            >
              <div class="history-top">
                <el-tag :type="getMethodTagType(item.method)" size="small" effect="dark" round>
                  {{ item.method }}
                </el-tag>
                <el-tag
                  :type="item.status >= 200 && item.status < 300 ? 'success' : 'danger'"
                  size="small"
                  round
                >
                  {{ item.status }}
                </el-tag>
              </div>
              <div class="history-path" :title="item.path">{{ item.path }}</div>
              <div class="history-time">{{ formatTime(item.timestamp) }} · {{ item.duration }}ms</div>
            </div>
          </div>
          <el-empty v-else description="暂无请求记录" :image-size="60" />
        </div>
      </div>

      <div class="right-panel">
        <el-card class="request-card" shadow="never">
          <div class="request-row">
            <el-select v-model="requestConfig.method" class="method-select" size="large">
              <el-option label="GET" value="GET" />
              <el-option label="POST" value="POST" />
              <el-option label="PUT" value="PUT" />
              <el-option label="DELETE" value="DELETE" />
              <el-option label="PATCH" value="PATCH" />
            </el-select>
            <el-input
              v-model="requestConfig.path"
              placeholder="输入接口路径，如 /videos"
              class="path-input"
              size="large"
            />
            <el-button type="primary" size="large" :loading="loading" @click="sendRequest">
              <el-icon><Promotion /></el-icon>
              发送
            </el-button>
          </div>

          <el-tabs v-model="activeTab" class="request-tabs">
            <el-tab-pane label="Query 参数" name="query">
              <div class="kv-editor">
                <div
                  v-for="(item, idx) in queryParams"
                  :key="idx"
                  class="kv-row"
                >
                  <el-input
                    v-model="item.key"
                    placeholder="key"
                    class="kv-input"
                  />
                  <el-input
                    v-model="item.value"
                    placeholder="value"
                    class="kv-input"
                  />
                  <el-button link type="danger" @click="removeQueryParam(idx)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
                <el-button size="small" @click="addQueryParam">
                  <el-icon><Plus /></el-icon>添加参数
                </el-button>
              </div>
            </el-tab-pane>

            <el-tab-pane label="Body" name="body">
              <el-input
                v-model="requestConfig.body"
                type="textarea"
                :rows="10"
                placeholder='输入 JSON body，如 {"title": "影片标题"}'
                class="body-editor"
              />
              <div class="format-actions">
                <el-button size="small" @click="formatBody">格式化 JSON</el-button>
                <el-button size="small" @click="clearBody">清空</el-button>
              </div>
            </el-tab-pane>

            <el-tab-pane label="Headers" name="headers">
              <div class="auto-header-note">
                <el-icon color="#6366f1"><InfoFilled /></el-icon>
                自动携带 Authorization: Bearer {{ displayToken }}
              </div>
              <div class="kv-editor" style="margin-top: 12px;">
                <div
                  v-for="(item, idx) in customHeaders"
                  :key="idx"
                  class="kv-row"
                >
                  <el-input
                    v-model="item.key"
                    placeholder="Header 名称"
                    class="kv-input"
                  />
                  <el-input
                    v-model="item.value"
                    placeholder="Header 值"
                    class="kv-input"
                  />
                  <el-button link type="danger" @click="removeHeader(idx)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
                <el-button size="small" @click="addHeader">
                  <el-icon><Plus /></el-icon>添加 Header
                </el-button>
              </div>
            </el-tab-pane>
          </el-tabs>
        </el-card>

        <el-card class="response-card" shadow="never">
          <div class="response-header">
            <div class="response-meta">
              <span v-if="responseInfo.status !== null" class="status-badge" :class="getStatusClass(responseInfo.status)">
                {{ responseInfo.status }}
              </span>
              <span v-if="responseInfo.duration !== null" class="duration-badge">
                <el-icon><Timer /></el-icon>
                {{ responseInfo.duration }}ms
              </span>
              <span v-if="responseInfo.timestamp" class="time-badge">
                <el-icon><Clock /></el-icon>
                {{ formatTime(responseInfo.timestamp) }}
              </span>
            </div>
            <el-button size="small" v-if="responseBody" @click="copyResponse">
              <el-icon><CopyDocument /></el-icon>
              复制
            </el-button>
          </div>
          <div class="response-body" v-if="responseBody">
            <pre><code>{{ responseBody }}</code></pre>
          </div>
          <el-empty v-else description="暂无响应数据，点击「发送」发起请求" :image-size="80" />
        </el-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Cpu, Collection, Timer, Promotion, Delete, Plus,
  InfoFilled, Clock, CopyDocument
} from '@element-plus/icons-vue'
import axios from 'axios'

const HISTORY_KEY = 'api_console_history'
const MAX_HISTORY = 20

const activeTab = ref('query')
const loading = ref(false)
const selectedTemplate = ref('')
const historyList = ref([])

const requestConfig = reactive({
  method: 'GET',
  path: '',
  body: ''
})

const queryParams = ref([{ key: '', value: '' }])
const customHeaders = ref([{ key: '', value: '' }])

const responseInfo = reactive({
  status: null,
  duration: null,
  timestamp: null
})
const responseBody = ref('')

const apiTemplates = [
  {
    name: '管理员登录',
    method: 'POST',
    path: '/admin/login',
    query: [],
    body: '{\n  "username": "admin",\n  "password": "admin123"\n}',
    bodyType: 'form'
  },
  {
    name: '管理员退出',
    method: 'POST',
    path: '/admin/logout',
    query: [],
    body: ''
  },
  {
    name: '影片列表',
    method: 'GET',
    path: '/videos',
    query: [
      { key: 'page', value: '1' },
      { key: 'page_size', value: '20' }
    ],
    body: ''
  },
  {
    name: '影片详情',
    method: 'GET',
    path: '/videos/1',
    query: [],
    body: ''
  },
  {
    name: '新增影片',
    method: 'POST',
    path: '/videos',
    query: [],
    body: '{\n  "title": "示例影片",\n  "cover_url": "https://example.com/cover.jpg",\n  "description": "影片描述",\n  "status": "published"\n}',
    bodyType: 'form'
  },
  {
    name: '更新影片',
    method: 'POST',
    path: '/videos/1',
    query: [],
    body: '{\n  "title": "更新后的标题",\n  "status": "published"\n}',
    bodyType: 'form'
  },
  {
    name: '删除影片',
    method: 'DELETE',
    path: '/videos/1',
    query: [],
    body: ''
  },
  {
    name: '播放源列表',
    method: 'GET',
    path: '/sources',
    query: [
      { key: 'video_id', value: '1' }
    ],
    body: ''
  },
  {
    name: '新增播放源',
    method: 'POST',
    path: '/sources',
    query: [],
    body: '{\n  "video_id": "1",\n  "source_name": "默认源",\n  "m3u8_url": "https://example.com/video/index.m3u8"\n}',
    bodyType: 'form'
  },
  {
    name: '删除播放源',
    method: 'DELETE',
    path: '/sources/1',
    query: [],
    body: ''
  },
  {
    name: '字幕列表',
    method: 'GET',
    path: '/subtitles',
    query: [
      { key: 'video_id', value: '1' }
    ],
    body: ''
  },
  {
    name: '定时任务列表',
    method: 'GET',
    path: '/scheduled_tasks',
    query: [
      { key: 'page', value: '1' },
      { key: 'page_size', value: '20' }
    ],
    body: ''
  },
  {
    name: '即将执行的任务',
    method: 'GET',
    path: '/scheduled_tasks/upcoming',
    query: [
      { key: 'limit', value: '10' }
    ],
    body: ''
  },
  {
    name: '内容分级列表',
    method: 'GET',
    path: '/content_ratings',
    query: [],
    body: ''
  },
  {
    name: '启用的内容分级',
    method: 'GET',
    path: '/content_ratings/active',
    query: [],
    body: ''
  },
  {
    name: '推荐位槽位列表',
    method: 'GET',
    path: '/recommend_slots',
    query: [],
    body: ''
  },
  {
    name: '推荐位预览',
    method: 'GET',
    path: '/recommend_slots/preview',
    query: [],
    body: ''
  },
  {
    name: '公告列表',
    method: 'GET',
    path: '/announcements',
    query: [
      { key: 'page', value: '1' }
    ],
    body: ''
  },
  {
    name: '生效中的公告',
    method: 'GET',
    path: '/announcements/active',
    query: [],
    body: ''
  },
  {
    name: '数据快照',
    method: 'GET',
    path: '/reports/snapshot',
    query: [],
    body: ''
  }
]

const displayToken = computed(() => {
  const token = localStorage.getItem('token')
  if (!token) return '(未登录)'
  if (token.length > 16) return token.slice(0, 16) + '...'
  return token
})

const getMethodTagType = (method) => {
  const map = {
    GET: 'success',
    POST: 'warning',
    PUT: 'primary',
    DELETE: 'danger',
    PATCH: 'info'
  }
  return map[method] || 'info'
}

const getStatusClass = (status) => {
  if (status >= 200 && status < 300) return 'status-success'
  if (status >= 300 && status < 400) return 'status-redirect'
  if (status >= 400 && status < 500) return 'status-client-error'
  return 'status-server-error'
}

const formatTime = (ts) => {
  if (!ts) return ''
  const d = new Date(ts)
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

const selectTemplate = (tpl) => {
  selectedTemplate.value = tpl.name
  requestConfig.method = tpl.method
  requestConfig.path = tpl.path
  requestConfig.body = tpl.body || ''
  queryParams.value = tpl.query && tpl.query.length > 0
    ? [...tpl.query]
    : [{ key: '', value: '' }]
  activeTab.value = (tpl.query && tpl.query.length > 0) ? 'query' : (tpl.body ? 'body' : 'query')
}

const addQueryParam = () => {
  queryParams.value.push({ key: '', value: '' })
}

const removeQueryParam = (idx) => {
  if (queryParams.value.length === 1) {
    queryParams.value = [{ key: '', value: '' }]
  } else {
    queryParams.value.splice(idx, 1)
  }
}

const addHeader = () => {
  customHeaders.value.push({ key: '', value: '' })
}

const removeHeader = (idx) => {
  if (customHeaders.value.length === 1) {
    customHeaders.value = [{ key: '', value: '' }]
  } else {
    customHeaders.value.splice(idx, 1)
  }
}

const formatBody = () => {
  if (!requestConfig.body.trim()) return
  try {
    const parsed = JSON.parse(requestConfig.body)
    requestConfig.body = JSON.stringify(parsed, null, 2)
  } catch (e) {
    ElMessage.warning('Body 不是有效的 JSON，无法格式化')
  }
}

const clearBody = () => {
  requestConfig.body = ''
}

const buildQueryObject = () => {
  const obj = {}
  queryParams.value.forEach(item => {
    if (item.key && item.key.trim()) {
      obj[item.key.trim()] = item.value
    }
  })
  return obj
}

const buildHeadersObject = () => {
  const obj = {}
  customHeaders.value.forEach(item => {
    if (item.key && item.key.trim()) {
      obj[item.key.trim()] = item.value
    }
  })
  const token = localStorage.getItem('token')
  if (token) {
    obj.Authorization = `Bearer ${token}`
  }
  return obj
}

const isDeleteRequest = () => {
  return requestConfig.method.toUpperCase() === 'DELETE'
}

const sendRequest = async () => {
  if (!requestConfig.path.trim()) {
    ElMessage.warning('请输入接口路径')
    return
  }

  if (isDeleteRequest()) {
    try {
      await ElMessageBox.confirm(
        `您即将发送 DELETE 请求到 ${requestConfig.path}，此操作可能删除数据，确认继续吗？`,
        '删除操作确认',
        {
          confirmButtonText: '确认发送',
          cancelButtonText: '取消',
          type: 'warning',
          confirmButtonClass: 'el-button--danger'
        }
      )
    } catch {
      return
    }
  }

  loading.value = true
  responseBody.value = ''
  const startTime = Date.now()

  try {
    const headers = buildHeadersObject()
    const params = buildQueryObject()
    const method = requestConfig.method.toUpperCase()

    let data = undefined
    if (['POST', 'PUT', 'PATCH'].includes(method) && requestConfig.body.trim()) {
      try {
        data = JSON.parse(requestConfig.body)
        headers['Content-Type'] = headers['Content-Type'] || 'application/json'
      } catch {
        data = requestConfig.body
      }
    }

    const axiosInstance = axios.create({
      baseURL: '/api',
      timeout: 30000
    })

    let response
    try {
      response = await axiosInstance.request({
        method,
        url: requestConfig.path,
        params,
        data,
        headers
      })
    } catch (err) {
      if (err.response) {
        response = err.response
      } else {
        throw err
      }
    }

    const duration = Date.now() - startTime
    responseInfo.status = response.status
    responseInfo.duration = duration
    responseInfo.timestamp = Date.now()

    try {
      responseBody.value = JSON.stringify(response.data, null, 2)
    } catch {
      responseBody.value = String(response.data)
    }

    saveToHistory({
      method,
      path: requestConfig.path,
      query: [...queryParams.value],
      body: requestConfig.body,
      headers: [...customHeaders.value],
      status: response.status,
      duration,
      timestamp: Date.now()
    })

    if (response.status >= 200 && response.status < 300) {
      ElMessage.success(`请求成功 (${response.status})`)
    } else {
      ElMessage.warning(`请求返回 ${response.status}`)
    }
  } catch (error) {
    const duration = Date.now() - startTime
    responseInfo.status = 0
    responseInfo.duration = duration
    responseInfo.timestamp = Date.now()
    responseBody.value = error.message || '网络请求失败'
    ElMessage.error('请求失败：' + (error.message || '未知错误'))
  } finally {
    loading.value = false
  }
}

const replayRequest = (item) => {
  requestConfig.method = item.method
  requestConfig.path = item.path
  requestConfig.body = item.body || ''
  queryParams.value = item.query && item.query.length > 0
    ? [...item.query]
    : [{ key: '', value: '' }]
  customHeaders.value = item.headers && item.headers.length > 0
    ? [...item.headers]
    : [{ key: '', value: '' }]
  selectedTemplate.value = ''
  ElMessage.info('已载入历史请求')
}

const saveToHistory = (record) => {
  const list = loadHistory()
  list.unshift(record)
  if (list.length > MAX_HISTORY) {
    list.length = MAX_HISTORY
  }
  try {
    sessionStorage.setItem(HISTORY_KEY, JSON.stringify(list))
  } catch {}
  historyList.value = list
}

const loadHistory = () => {
  try {
    const raw = sessionStorage.getItem(HISTORY_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

const clearHistory = () => {
  sessionStorage.removeItem(HISTORY_KEY)
  historyList.value = []
  ElMessage.success('已清空请求历史')
}

const copyResponse = async () => {
  try {
    await navigator.clipboard.writeText(responseBody.value)
    ElMessage.success('已复制到剪贴板')
  } catch {
    ElMessage.error('复制失败')
  }
}

onMounted(() => {
  historyList.value = loadHistory()
})
</script>

<style scoped>
.api-console {
  height: 100%;
}

.page-header {
  margin-bottom: 20px;
}

.page-title {
  margin: 0 0 6px;
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-title .el-icon {
  color: #6366f1;
}

.page-desc {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.console-layout {
  display: flex;
  gap: 20px;
  height: calc(100vh - 180px);
}

.left-panel {
  width: 260px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  padding: 16px;
  overflow-y: auto;
}

.right-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
}

.panel-section .section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 12px;
  gap: 8px;
}

.panel-section .section-title .el-icon {
  color: #6366f1;
}

.clear-history-btn {
  padding: 0;
  margin: 0;
}

.template-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.template-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.template-item:hover {
  background: #f8fafc;
}

.template-item.active {
  background: rgba(99, 102, 241, 0.08);
  border-color: rgba(99, 102, 241, 0.2);
}

.template-name {
  font-size: 13px;
  color: #334155;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.template-item.active .template-name {
  color: #6366f1;
  font-weight: 500;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-item {
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.history-item:hover {
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.history-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.history-path {
  font-size: 12px;
  color: #475569;
  font-family: Consolas, Monaco, monospace;
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.history-time {
  font-size: 11px;
  color: #94a3b8;
}

.request-card,
.response-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.request-row {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.method-select {
  width: 130px;
  flex-shrink: 0;
}

.path-input {
  flex: 1;
}

.request-tabs {
  margin-top: 16px;
}

.kv-editor {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.kv-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.kv-input {
  flex: 1;
}

.auto-header-note {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: rgba(99, 102, 241, 0.06);
  border-radius: 8px;
  font-size: 13px;
  color: #475569;
  font-family: Consolas, Monaco, monospace;
}

.body-editor :deep(.el-textarea__inner) {
  font-family: Consolas, Monaco, monospace;
  font-size: 13px;
}

.format-actions {
  margin-top: 10px;
  display: flex;
  gap: 10px;
}

.response-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f0f0f0;
}

.response-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  font-family: Consolas, Monaco, monospace;
}

.status-success {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-redirect {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.status-client-error {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.status-server-error {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.duration-badge,
.time-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
}

.response-body {
  background: #0f172a;
  border-radius: 8px;
  padding: 16px;
  max-height: 400px;
  overflow: auto;
}

.response-body pre {
  margin: 0;
}

.response-body code {
  font-family: Consolas, Monaco, monospace;
  font-size: 13px;
  color: #e2e8f0;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
