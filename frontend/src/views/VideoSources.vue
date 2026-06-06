<template>
  <div class="video-sources">
    <el-card>
      <template #header>
        <div class="card-header">
          <div>
            <h3>播放源管理</h3>
            <p class="video-title">影片：{{ videoInfo.title }}</p>
          </div>
          <div>
            <el-button @click="handleBack">返回列表</el-button>
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增播放源
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="source_name" label="线路名称" width="150" />
        <el-table-column prop="m3u8_url" label="M3U8地址" min-width="300" show-overflow-tooltip />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      :close-on-click-modal="false"
      @closed="handleDialogClosed"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="线路名称" prop="source_name">
          <el-input
            v-model="form.source_name"
            placeholder="请输入线路名称（如：线路1、线路2）"
            maxlength="50"
            clearable
          />
        </el-form-item>

        <el-form-item label="M3U8地址" prop="m3u8_url">
          <el-input
            v-model="form.m3u8_url"
            placeholder="请输入M3U8播放地址（必须以.m3u8结尾）"
            clearable
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSourceList, createSource, updateSource, deleteSource } from '../api'

const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(null)

const tableData = ref([])
const videoInfo = ref({
  id: '',
  title: ''
})

const form = reactive({
  video_id: '',
  source_name: '',
  m3u8_url: ''
})

const dialogTitle = computed(() => {
  return isEdit.value ? '编辑播放源' : '新增播放源'
})

const validateM3u8Url = (rule, value, callback) => {
  if (!value) {
    callback(new Error('M3U8地址不能为空'))
  } else {
    const urlPattern = /^https?:\/\/.+/
    if (!urlPattern.test(value)) {
      callback(new Error('请输入有效的URL地址'))
    } else if (!value.toLowerCase().endsWith('.m3u8')) {
      callback(new Error('M3U8地址必须以.m3u8结尾'))
    } else {
      callback()
    }
  }
}

const rules = {
  source_name: [
    { required: true, message: '请输入线路名称', trigger: 'blur' },
    { min: 1, max: 50, message: '线路名称长度必须在1-50个字符之间', trigger: 'blur' }
  ],
  m3u8_url: [
    { required: true, validator: validateM3u8Url, trigger: 'blur' }
  ]
}

const fetchData = async () => {
  const videoId = route.params.id
  if (!videoId) {
    ElMessage.error('影片ID不存在')
    router.back()
    return
  }

  console.log('正在获取播放源列表，影片ID:', videoId)
  loading.value = true
  try {
    const res = await getSourceList(videoId)
    videoInfo.value = res.data.video
    tableData.value = res.data.list
    console.log('播放源列表更新成功，共', res.data.list.length, '条记录')
  } catch (error) {
    console.error('获取列表失败：', error)
  } finally {
    loading.value = false
  }
}

const handleBack = () => {
  router.push('/videos')
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = null
  form.video_id = route.params.id
  form.source_name = ''
  form.m3u8_url = ''
  dialogVisible.value = true
}

const handleEdit = (row) => {
  isEdit.value = true
  editId.value = row.id
  form.video_id = row.video_id
  form.source_name = row.source_name
  form.m3u8_url = row.m3u8_url
  dialogVisible.value = true
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitLoading.value = true
    try {
      if (isEdit.value) {
        console.log('正在更新播放源，ID:', editId.value)
        await updateSource(editId.value, form)
        ElMessage.success('更新成功')
      } else {
        console.log('正在添加播放源，数据:', form)
        await createSource(form)
        ElMessage.success('添加成功')
      }
      dialogVisible.value = false
      // 等待对话框关闭动画完成后再刷新列表
      console.log('等待对话框关闭...')
      await new Promise(resolve => setTimeout(resolve, 300))
      console.log('开始刷新列表...')
      await fetchData()
    } catch (error) {
      console.error('提交失败：', error)
    } finally {
      submitLoading.value = false
    }
  })
}

const handleDialogClosed = () => {
  // 重置表单
  if (formRef.value) {
    formRef.value.resetFields()
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该播放源吗？', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'error'
    })

    await deleteSource(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.video-sources :deep(.el-card) {
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

.video-title {
  margin: 5px 0 0 0;
  font-size: 14px;
  color: #94a3b8;
}

.video-sources :deep(.el-table) {
  border-radius: 8px;
}

.video-sources :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.video-sources :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.video-sources :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.video-sources :deep(.el-dialog) {
  border-radius: 12px;
}
</style>
