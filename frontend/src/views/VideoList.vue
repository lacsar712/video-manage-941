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
              style="width: 160px"
              @clear="handleQuery"
            >
              <el-option label="上架" value="1" />
              <el-option label="下架" value="0" />
            </el-select>
          </el-form-item>
          <el-form-item label="内容分级">
            <el-select
              v-model="queryForm.content_rating_code"
              placeholder="全部分级"
              clearable
              style="width: 180px"
              @clear="handleQuery"
            >
              <el-option label="未设置分级" value="__unrated__" />
              <el-option
                v-for="item in ratingOptions"
                :key="item.code"
                :label="item.label"
                :value="item.code"
              >
                <div class="filter-rating-option">
                  <span
                    class="filter-rating-tag"
                    :style="{ backgroundColor: item.color_hex }"
                  >{{ item.label }}</span>
                  <span class="filter-rating-code">{{ item.code }}</span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleQuery">查询</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <VideoTable
        :data="tableData"
        :loading="loading"
        :get-row-class-name="getRowClassName"
        @edit="handleEdit"
        @sources="handleSources"
        @subtitles="handleSubtitles"
        @toggle-status="handleToggleStatus"
        @delete="handleDelete"
        @preview="handlePreview"
      />

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

    <el-dialog v-model="showViewer" width="800px" :show-close="true">
      <img :src="previewUrl" style="width: 100%; display: block;" />
    </el-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { deleteVideo, updateVideoStatus } from '../api'
import { useVideoList } from '../composables/useVideoList'
import VideoTable from '../components/video/VideoTable.vue'

const router = useRouter()

const {
  loading,
  tableData,
  total,
  queryForm,
  ratingOptions,
  fetchData,
  handleQuery,
  handlePageChange,
  handleSizeChange,
  handleReset,
  getRowClassName,
  refresh
} = useVideoList()

const previewUrl = ref('')
const showViewer = ref(false)

const handleAdd = () => {
  router.push('/videos/new')
}

const handleEdit = (row) => {
  router.push(`/videos/${row.id}/edit`)
}

const handleSources = (row) => {
  router.push(`/videos/${row.id}/sources`)
}

const handleSubtitles = (row) => {
  router.push(`/videos/${row.id}/subtitles`)
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
    await refresh()
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
    refresh()
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

.video-list :deep(.el-table .row-unrated) {
  --el-table-tr-bg-color: #fafafa;
}

.video-list :deep(.el-table .row-unrated td) {
  color: #94a3b8;
}

.video-list :deep(.el-table .row-unrated .el-tag--warning) {
  opacity: 1;
}

.filter-rating-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-rating-tag {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 3px;
  color: #fff;
  font-size: 11px;
  font-weight: 500;
  line-height: 1.4;
}

.filter-rating-code {
  font-size: 12px;
  color: #64748b;
  font-family: monospace;
}
</style>
