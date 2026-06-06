<template>
  <div class="video-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>影片管理</h3>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增影片
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-form :inline="true" :model="queryForm">
          <el-form-item label="关键词">
            <el-input
              v-model="queryForm.keyword"
              placeholder="请输入影片标题"
              clearable
              style="width: 200px"
              @clear="handleQuery"
            />
          </el-form-item>
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 200px"
              @clear="handleQuery"
            >
              <el-option label="上架" value="1" />
              <el-option label="下架" value="0" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleQuery">查询</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="影片标题" min-width="200" />
        <el-table-column prop="cover_url" label="封面" width="120">
          <template #default="{ row }">
            <div v-if="row.cover_url" class="cover-wrapper" @click="handlePreview(getCoverUrl(row.cover_url))">
              <img
                :src="getCoverUrl(row.cover_url)"
                :alt="row.title"
                class="cover-image"
                loading="lazy"
                @error="handleImageError"
              />
            </div>
            <span v-else class="cover-empty">暂无</span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="300" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" @click="handleSources(row)">播放源</el-button>
            <el-button
              size="small"
              :type="row.status == 1 ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status == 1 ? '下架' : '上架' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 图片预览对话框 -->
    <el-dialog v-model="showViewer" width="800px" :show-close="true">
      <img :src="previewUrl" style="width: 100%; display: block;" />
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getVideoList, deleteVideo, updateVideoStatus } from '../api'

const router = useRouter()
const loading = ref(false)
const tableData = ref([])
const total = ref(0)
const previewUrl = ref('')
const showViewer = ref(false)

const queryForm = reactive({
  page: 1,
  page_size: 10,
  keyword: '',
  status: ''
})

// 获取封面完整URL
const getCoverUrl = (url) => {
  if (!url) return ''
  // 如果是完整URL，直接返回
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  // 如果是相对路径，拼接API基础URL
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getVideoList(queryForm)
    tableData.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error('获取列表失败：', error)
  } finally {
    loading.value = false
  }
}

const handleQuery = () => {
  queryForm.page = 1
  fetchData()
}

const handlePageChange = () => {
  // 翻页时不重置页码，直接获取数据
  fetchData()
}

const handleSizeChange = () => {
  // 改变每页条数时重置到第一页
  queryForm.page = 1
  fetchData()
}

const handleReset = () => {
  queryForm.keyword = ''
  queryForm.status = ''
  handleQuery()
}

const handleAdd = () => {
  router.push('/videos/new')
}

const handleEdit = (row) => {
  router.push(`/videos/${row.id}/edit`)
}

const handleSources = (row) => {
  router.push(`/videos/${row.id}/sources`)
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status == 1 ? 0 : 1
  const action = newStatus == 1 ? '上架' : '下架'

  console.log('当前状态:', row.status, '新状态:', newStatus, '操作:', action)

  try {
    await ElMessageBox.confirm(`确定要${action}该影片吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    console.log('开始调用API更新状态...')
    const result = await updateVideoStatus(row.id, newStatus)
    console.log('API调用成功:', result)

    ElMessage.success(`${action}成功`)

    console.log('刷新列表数据...')
    await fetchData()
    console.log('列表数据已刷新')
  } catch (error) {
    if (error !== 'cancel') {
      console.error(`${action}失败：`, error)
      ElMessage.error(`${action}失败：${error.message || '未知错误'}`)
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该影片吗？删除后将无法恢复！', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'error'
    })

    await deleteVideo(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

const handlePreview = (url) => {
  previewUrl.value = url
  showViewer.value = true
}

const handleImageError = (e) => {
  e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3E加载失败%3C/text%3E%3C/svg%3E'
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.video-list :deep(.el-card) {
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

.filter-bar {
  margin-bottom: 20px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 8px;
}

.filter-bar :deep(.el-form-item) {
  margin-bottom: 0;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.cover-wrapper {
  cursor: pointer;
  transition: transform 0.2s;
}

.cover-wrapper:hover {
  transform: scale(1.05);
}

.cover-image {
  width: 80px;
  height: 45px;
  border-radius: 6px;
  object-fit: cover;
  display: block;
}

.cover-empty {
  display: inline-flex;
  width: 80px;
  height: 45px;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: #f0f0ff;
  color: #94a3b8;
  font-size: 12px;
}

.video-list :deep(.el-table) {
  border-radius: 8px;
}

.video-list :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.video-list :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.video-list :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}
</style>
