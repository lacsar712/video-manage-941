<template>
  <div class="scheduled-tasks">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>定时任务管理</h3>
          <el-button type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>
            创建任务
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-form :inline="true" :model="queryForm">
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 160px"
              @clear="handleQuery"
              @change="handleQuery"
            >
              <el-option label="待执行" value="pending" />
              <el-option label="已执行" value="executed" />
              <el-option label="已取消" value="cancelled" />
            </el-select>
          </el-form-item>
          <el-form-item label="动作">
            <el-select
              v-model="queryForm.action"
              placeholder="请选择动作"
              clearable
              style="width: 160px"
              @clear="handleQuery"
              @change="handleQuery"
            >
              <el-option label="上架" value="publish" />
              <el-option label="下架" value="unpublish" />
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
        <el-table-column prop="video_title" label="影片名称" min-width="200" />
        <el-table-column prop="action" label="动作" width="100">
          <template #default="{ row }">
            <el-tag :type="row.action === 'publish' ? 'success' : 'warning'">
              {{ row.action === 'publish' ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="执行时间" width="200">
          <template #default="{ row }">
            <div>{{ row.execute_at }}</div>
            <div v-if="row.status === 'pending'" class="countdown">
              <span class="countdown-label">剩余：</span>
              <span class="countdown-value">{{ getCountdown(row) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'pending'" type="primary">待执行</el-tag>
            <el-tag v-else-if="row.status === 'executed'" type="success">已执行</el-tag>
            <el-tag v-else-if="row.status === 'cancelled'" type="info">已取消</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="creator_name" label="创建人" width="120" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="执行结果" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.status === 'executed'">{{ row.result_message || '-' }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.status === 'pending'"
              size="small"
              type="danger"
              @click="handleCancel(row)"
            >
              取消
            </el-button>
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

    <el-dialog
      v-model="dialogVisible"
      title="创建定时任务"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="选择影片" prop="video_id">
          <el-select
            v-model="formData.video_id"
            placeholder="请选择影片"
            filterable
            style="width: 100%"
            v-loading="videoLoading"
          >
            <el-option
              v-for="video in videoList"
              :key="video.id"
              :label="video.title"
              :value="video.id"
            >
              <span>{{ video.title }}</span>
              <el-tag
                :type="video.status == 1 ? 'success' : 'info'"
                size="small"
                style="margin-left: 8px"
              >
                {{ video.status == 1 ? '已上架' : '已下架' }}
              </el-tag>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="动作类型" prop="action">
          <el-radio-group v-model="formData.action">
            <el-radio value="publish">上架</el-radio>
            <el-radio value="unpublish">下架</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="执行时间" prop="execute_at">
          <el-date-picker
            v-model="formData.execute_at"
            type="datetime"
            placeholder="选择执行时间"
            style="width: 100%"
            :disabled-date="disabledDate"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
          <div class="tip">执行时间须晚于当前时间 5 分钟</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定创建
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getScheduledTaskList,
  getVideoList,
  createScheduledTask,
  cancelScheduledTask
} from '../api'

const loading = ref(false)
const videoLoading = ref(false)
const submitLoading = ref(false)
const tableData = ref([])
const videoList = ref([])
const total = ref(0)
const dialogVisible = ref(false)
const formRef = ref(null)
let countdownTimer = null

const queryForm = reactive({
  page: 1,
  page_size: 10,
  status: '',
  action: ''
})

const formData = reactive({
  video_id: '',
  action: 'publish',
  execute_at: ''
})

const formRules = {
  video_id: [{ required: true, message: '请选择影片', trigger: 'change' }],
  action: [{ required: true, message: '请选择动作类型', trigger: 'change' }],
  execute_at: [{ required: true, message: '请选择执行时间', trigger: 'change' }]
}

const disabledDate = (time) => {
  const minTime = Date.now() + 5 * 60 * 1000
  return time.getTime() < minTime - 86400000
}

const getCountdown = (row) => {
  if (row.status !== 'pending') return ''
  const target = new Date(row.execute_at.replace(/-/g, '/')).getTime()
  const now = Date.now()
  const diff = target - now
  if (diff <= 0) return '即将执行'
  const days = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  const minutes = Math.floor((diff % 3600000) / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  if (days > 0) return `${days}天${hours}时${minutes}分`
  if (hours > 0) return `${hours}时${minutes}分${seconds}秒`
  if (minutes > 0) return `${minutes}分${seconds}秒`
  return `${seconds}秒`
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getScheduledTaskList(queryForm)
    tableData.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error('获取列表失败：', error)
  } finally {
    loading.value = false
  }
}

const fetchVideoList = async () => {
  videoLoading.value = true
  try {
    const res = await getVideoList({ page: 1, page_size: 100 })
    videoList.value = res.data.list
  } catch (error) {
    console.error('获取影片列表失败：', error)
  } finally {
    videoLoading.value = false
  }
}

const handleQuery = () => {
  queryForm.page = 1
  fetchData()
}

const handleReset = () => {
  queryForm.status = ''
  queryForm.action = ''
  handleQuery()
}

const handlePageChange = () => {
  fetchData()
}

const handleSizeChange = () => {
  queryForm.page = 1
  fetchData()
}

const handleCreate = async () => {
  formData.video_id = ''
  formData.action = 'publish'
  formData.execute_at = ''
  dialogVisible.value = true
  await fetchVideoList()
}

const handleSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()

    const executeTime = new Date(formData.execute_at.replace(/-/g, '/')).getTime()
    const minTime = Date.now() + 5 * 60 * 1000
    if (executeTime < minTime) {
      ElMessage.warning('执行时间必须晚于当前时间 5 分钟')
      return
    }

    submitLoading.value = true
    await createScheduledTask({
      video_id: formData.video_id,
      action: formData.action,
      execute_at: formData.execute_at
    })
    ElMessage.success('创建成功')
    dialogVisible.value = false
    fetchData()
  } catch (error) {
    if (error !== false) {
      console.error('创建失败：', error)
    }
  } finally {
    submitLoading.value = false
  }
}

const handleCancel = async (row) => {
  try {
    await ElMessageBox.confirm('确定要取消该定时任务吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await cancelScheduledTask(row.id)
    ElMessage.success('取消成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('取消失败：', error)
    }
  }
}

onMounted(() => {
  fetchData()
  countdownTimer = setInterval(() => {
    tableData.value = [...tableData.value]
  }, 1000)
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>

<style scoped>
.scheduled-tasks :deep(.el-card) {
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

.countdown {
  margin-top: 4px;
  font-size: 12px;
}

.countdown-label {
  color: #94a3b8;
}

.countdown-value {
  color: #f59e0b;
  font-weight: 600;
}

.tip {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.scheduled-tasks :deep(.el-table) {
  border-radius: 8px;
}

.scheduled-tasks :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.scheduled-tasks :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.scheduled-tasks :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}
</style>
