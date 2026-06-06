<template>
  <div class="media-library">
    <el-card class="search-card">
      <div class="search-bar">
        <el-input
          v-model="keyword"
          placeholder="按文件名搜索"
          clearable
          style="width: 300px"
          @keyup.enter="fetchList"
          @clear="fetchList"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-button type="primary" @click="fetchList">
          <el-icon><Search /></el-icon>
          搜索
        </el-button>
        <el-upload
          class="upload-btn"
          :action="uploadAction"
          :headers="uploadHeaders"
          :show-file-list="false"
          :on-success="handleUploadSuccess"
          :on-error="handleUploadError"
          :before-upload="beforeUpload"
          accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
        >
          <el-button type="success">
            <el-icon><Upload /></el-icon>
            上传图片
          </el-button>
        </el-upload>
      </div>
    </el-card>

    <el-card class="list-card">
      <div v-loading="loading" class="media-grid">
        <div
          v-for="item in list"
          :key="item.id"
          class="media-card"
          :class="{ 'is-referenced': item.is_referenced }"
        >
          <div class="media-thumb">
            <img :src="getFullUrl(item.file_path)" :alt="item.original_name" />
            <div class="media-overlay" v-if="item.is_referenced">
              <el-tag type="warning" size="small">已引用</el-tag>
            </div>
          </div>
          <div class="media-info">
            <div class="media-name" :title="item.original_name">{{ item.original_name }}</div>
            <div class="media-meta">
              <span>{{ formatSize(item.size_bytes) }}</span>
              <span class="dot">·</span>
              <span>{{ item.created_at }}</span>
            </div>
          </div>
          <div class="media-actions">
            <el-button
              type="danger"
              text
              size="small"
              :disabled="item.is_referenced"
              @click="handleDelete(item)"
            >
              <el-icon><Delete /></el-icon>
              删除
            </el-button>
          </div>
        </div>

        <el-empty v-if="!loading && list.length === 0" description="暂无图片资源" />
      </div>

      <div class="pagination-wrap" v-if="total > 0">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :page-sizes="[12, 24, 48, 96]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          background
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Upload, Delete } from '@element-plus/icons-vue'
import { getMediaList, deleteMedia } from '../api'

const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const pageSize = ref(12)
const total = ref(0)
const list = ref([])

const uploadAction = computed(() => {
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}/api/upload/media` : '/api/upload/media'
})

const uploadHeaders = computed(() => {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
})

const getFullUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMediaList({
      keyword: keyword.value,
      page: page.value,
      page_size: pageSize.value
    })
    list.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error('获取媒资列表失败：', error)
    ElMessage.error('获取媒资列表失败')
  } finally {
    loading.value = false
  }
}

const beforeUpload = (file) => {
  const isImage = /^image\/(jpeg|jpg|png|gif|webp)$/.test(file.type)
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传 JPG、PNG、GIF、WebP 格式的图片')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB')
    return false
  }
  return true
}

const handleUploadSuccess = (response) => {
  if (response.code === 0) {
    ElMessage.success('上传成功')
    fetchList()
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

const handleUploadError = (error) => {
  console.error('上传失败：', error)
  ElMessage.error('上传失败，请重试')
}

const handleDelete = async (item) => {
  if (item.is_referenced) {
    ElMessage.warning('该文件已被影片引用，无法删除。请先解除引用后再操作。')
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除文件「${item.original_name}」吗？`,
      '删除确认',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    await deleteMedia(item.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.media-library {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.search-card :deep(.el-card__body) {
  padding: 16px 20px;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}

.upload-btn {
  margin-left: auto;
}

.list-card :deep(.el-card__body) {
  padding: 20px;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  min-height: 300px;
}

.media-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
}

.media-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.media-card.is-referenced {
  border-color: #e6a23c;
}

.media-thumb {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
  background: #f8fafc;
  overflow: hidden;
}

.media-thumb img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
}

.media-info {
  padding: 10px 12px;
  flex: 1;
}

.media-name {
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.media-meta {
  font-size: 12px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 6px;
}

.media-meta .dot {
  opacity: 0.5;
}

.media-actions {
  padding: 8px 12px;
  border-top: 1px solid #f0f0f0;
}

.pagination-wrap {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
</style>
