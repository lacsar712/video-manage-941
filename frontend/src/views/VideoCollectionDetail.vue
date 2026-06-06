<template>
  <div class="collection-detail" v-loading="loading">
    <el-card v-if="collection" class="info-card">
      <template #header>
        <div class="card-header">
          <h3>合集信息</h3>
          <div class="header-actions">
            <el-button @click="goBack">
              <el-icon><ArrowLeft /></el-icon>
              返回列表
            </el-button>
            <el-button type="primary" @click="handleEdit">
              <el-icon><Edit /></el-icon>
              编辑合集
            </el-button>
          </div>
        </div>
      </template>

      <div class="collection-info">
        <div class="collection-cover">
          <img :src="getCoverUrl(collection.cover_url)" :alt="collection.title" @error="handleImageError" />
        </div>
        <div class="collection-meta">
          <div class="meta-row">
            <span class="meta-label">标题：</span>
            <span class="meta-value title">{{ collection.title }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">状态：</span>
            <el-tag :type="collection.status == 1 ? 'success' : 'info'">
              {{ collection.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </div>
          <div class="meta-row">
            <span class="meta-label">排序值：</span>
            <span class="meta-value">{{ collection.sort_order }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">影片数量：</span>
            <span class="meta-value">
              {{ collection.video_count }} 部
              <span v-if="offlineCount > 0" class="offline-tip">
                （含 {{ offlineCount }} 部下架影片）
              </span>
            </span>
          </div>
          <div class="meta-row">
            <span class="meta-label">创建时间：</span>
            <span class="meta-value">{{ collection.created_at }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">更新时间：</span>
            <span class="meta-value">{{ collection.updated_at }}</span>
          </div>
          <div class="meta-row description-row">
            <span class="meta-label">描述：</span>
            <span class="meta-value">{{ collection.description || '暂无描述' }}</span>
          </div>
        </div>
      </div>
    </el-card>

    <el-card v-if="collection" class="videos-card">
      <template #header>
        <div class="card-header">
          <h3>
            影片列表
            <el-tag type="info" size="small" style="margin-left: 8px;">
              共 {{ videos.length }} 部
            </el-tag>
            <el-tag v-if="offlineCount > 0" type="warning" size="small" style="margin-left: 4px;">
              {{ offlineCount }} 部下架
            </el-tag>
          </h3>
          <div class="header-actions">
            <el-button
              v-if="offlineCount > 0"
              type="danger"
              @click="handleRemoveAllOffline"
            >
              <el-icon><Delete /></el-icon>
              一键移除下架影片
            </el-button>
            <el-button type="primary" @click="handleEdit">
              <el-icon><Plus /></el-icon>
              添加/管理影片
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="videos" border stripe>
        <el-table-column label="序号" width="70" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </el-table-column>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="封面" width="140">
          <template #default="{ row }">
            <div class="video-cover-cell" :class="{ offline: row.status != 1 }">
              <img
                :src="getCoverUrl(row.cover_url)"
                :alt="row.title"
                @error="handleImageError"
              />
            </div>
          </template>
        </el-table-column>
        <el-table-column label="影片标题" min-width="200">
          <template #default="{ row }">
            <span :class="{ 'offline-text': row.status != 1 }">{{ row.title }}</span>
            <el-tag v-if="row.status != 1" type="warning" size="small" style="margin-left: 8px;">
              已下架
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            <span :class="{ 'offline-text': row.status != 1 }">
              {{ row.description || '暂无描述' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button
              size="small"
              type="primary"
              link
              @click="goToVideo(row)"
            >
              查看
            </el-button>
            <el-button
              size="small"
              type="danger"
              link
              @click="handleRemoveVideo(row)"
            >
              移除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-empty v-if="videos.length === 0" description="合集暂无影片" style="padding: 60px 0;" />
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowLeft, Edit, Plus, Delete } from '@element-plus/icons-vue'
import { getCollectionDetail, removeVideoFromCollection } from '../api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const collection = ref(null)
const videos = ref([])

const offlineCount = computed(() => {
  return videos.value.filter(v => v.status != 1).length
})

const getCoverUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

const handleImageError = (e) => {
  e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3E加载失败%3C/text%3E%3C/svg%3E'
}

const fetchDetail = async () => {
  const id = route.params.id
  if (!id) return

  loading.value = true
  try {
    const res = await getCollectionDetail(id)
    collection.value = res.data
    videos.value = res.data.videos || []
  } catch (error) {
    console.error('获取详情失败：', error)
    ElMessage.error('获取合集详情失败')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/collections')
}

const handleEdit = () => {
  router.push(`/collections/${route.params.id}/edit`)
}

const goToVideo = (row) => {
  router.push(`/videos/${row.id}/edit`)
}

const handleRemoveVideo = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定要从合集中移除影片「${row.title}」吗？`,
      '提示',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    await removeVideoFromCollection(route.params.id, row.id)
    ElMessage.success('移除成功')
    videos.value = videos.value.filter(v => v.id !== row.id)
    if (collection.value) {
      collection.value.video_count = videos.value.length
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('移除失败：', error)
    }
  }
}

const handleRemoveAllOffline = async () => {
  const offlineVideos = videos.value.filter(v => v.status != 1)
  if (offlineVideos.length === 0) return

  try {
    await ElMessageBox.confirm(
      `确定要移除全部 ${offlineVideos.length} 部下架影片吗？`,
      '提示',
      {
        confirmButtonText: '确定移除',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    for (const video of offlineVideos) {
      try {
        await removeVideoFromCollection(route.params.id, video.id)
      } catch (e) {
        console.error(`移除影片 ${video.id} 失败：`, e)
      }
    }

    ElMessage.success('已移除全部下架影片')
    await fetchDetail()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('操作失败：', error)
    }
  }
}

onMounted(() => {
  fetchDetail()
})
</script>

<style scoped>
.collection-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.collection-detail :deep(.el-card) {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.collection-info {
  display: flex;
  gap: 24px;
}

.collection-cover {
  flex-shrink: 0;
  width: 280px;
  height: 157px;
  border-radius: 10px;
  overflow: hidden;
  background: #f1f5f9;
}

.collection-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.collection-meta {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.meta-row {
  display: flex;
  align-items: flex-start;
  font-size: 14px;
  line-height: 1.6;
}

.meta-row.description-row {
  flex: 1;
}

.meta-label {
  width: 90px;
  flex-shrink: 0;
  color: #64748b;
  font-weight: 500;
}

.meta-value {
  color: #1e293b;
  flex: 1;
}

.meta-value.title {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.offline-tip {
  color: #f59e0b;
  font-size: 13px;
}

.videos-card {
  margin-top: 0;
}

.video-cover-cell {
  width: 100px;
  height: 56px;
  border-radius: 6px;
  overflow: hidden;
  background: #f1f5f9;
}

.video-cover-cell img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.video-cover-cell.offline {
  filter: grayscale(100%);
  opacity: 0.6;
}

.offline-text {
  color: #94a3b8;
}

.collection-detail :deep(.el-table) {
  border-radius: 8px;
}

.collection-detail :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.collection-detail :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.collection-detail :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}
</style>
