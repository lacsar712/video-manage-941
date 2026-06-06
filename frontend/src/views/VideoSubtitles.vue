<template>
  <div class="video-subtitles">
    <el-card>
      <template #header>
        <div class="card-header">
          <div>
            <h3>字幕管理</h3>
            <p class="video-title">影片：{{ videoInfo.title }}</p>
          </div>
          <div>
            <el-button @click="handleBack">返回列表</el-button>
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              上传字幕
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="tableData" border stripe v-loading="loading" empty-text="暂无字幕，请上传">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="language" label="语言" width="120">
          <template #default="{ row }">
            <el-tag :type="getLanguageTagType(row.language)">
              {{ getLanguageLabel(row.language) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="format" label="格式" width="100">
          <template #default="{ row }">
            <span class="format-tag">{{ row.format.toUpperCase() }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="file_name" label="文件名" min-width="200" show-overflow-tooltip />
        <el-table-column label="文件链接" min-width="200">
          <template #default="{ row }">
            <a :href="getFullUrl(row.file_url)" target="_blank" class="file-link">
              查看文件
              <el-icon><Link /></el-icon>
            </a>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="上传时间" width="180" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handlePreview(row)">预览</el-button>
            <el-button
              size="small"
              :type="row.status == 1 ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status == 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="uploadDialogVisible"
      title="上传字幕"
      width="560px"
      :close-on-click-modal="false"
      @closed="handleUploadDialogClosed"
    >
      <el-form
        ref="uploadFormRef"
        :model="uploadForm"
        :rules="uploadRules"
        label-width="100px"
      >
        <el-form-item label="语言" prop="language">
          <el-select
            v-model="uploadForm.language"
            placeholder="请选择字幕语言"
            style="width: 100%"
          >
            <el-option label="中文" value="zh" />
            <el-option label="English" value="en" />
            <el-option label="日本語" value="ja" />
          </el-select>
        </el-form-item>

        <el-form-item label="字幕文件" prop="file">
          <el-upload
            ref="uploadRef"
            :auto-upload="false"
            :limit="1"
            :on-change="handleFileChange"
            :on-exceed="handleFileExceed"
            :before-upload="beforeUpload"
            accept=".vtt,.srt"
            drag
          >
            <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
            <div class="el-upload__text">
              将文件拖到此处，或<em>点击上传</em>
            </div>
            <template #tip>
              <div class="el-upload__tip">
                仅支持 .vtt 和 .srt 格式，文件大小不超过 2MB
              </div>
            </template>
          </el-upload>
          <div v-if="selectedFile" class="selected-file-info">
            <el-icon><Document /></el-icon>
            <span>{{ selectedFile.name }}</span>
            <span class="file-size">({{ formatFileSize(selectedFile.size) }})</span>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="uploadDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleUploadSubmit">
          确定上传
        </el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="previewDialogVisible"
      :title="previewTitle"
      width="700px"
    >
      <div class="preview-content">
        <pre v-if="previewContent">{{ previewContent }}</pre>
        <el-empty v-else description="暂无内容" />
      </div>
      <template #footer>
        <el-button @click="previewDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getSubtitleList,
  uploadSubtitle,
  deleteSubtitle,
  updateSubtitleStatus,
  getSubtitlePreview
} from '../api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const submitLoading = ref(false)
const tableData = ref([])
const videoInfo = ref({
  id: '',
  title: ''
})

const uploadDialogVisible = ref(false)
const uploadFormRef = ref(null)
const uploadRef = ref(null)
const selectedFile = ref(null)

const uploadForm = reactive({
  language: '',
  file: null
})

const uploadRules = {
  language: [
    { required: true, message: '请选择字幕语言', trigger: 'change' }
  ],
  file: [
    { required: true, message: '请选择字幕文件', trigger: 'change' }
  ]
}

const previewDialogVisible = ref(false)
const previewContent = ref('')
const previewTitle = ref('')

const languageMap = {
  zh: '中文',
  en: 'English',
  ja: '日本語'
}

const getLanguageLabel = (lang) => languageMap[lang] || lang

const getLanguageTagType = (lang) => {
  const map = {
    zh: '',
    en: 'success',
    ja: 'warning'
  }
  return map[lang] || 'info'
}

const getFullUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

const fetchData = async () => {
  const videoId = route.params.id
  if (!videoId) {
    ElMessage.error('影片ID不存在')
    router.back()
    return
  }

  loading.value = true
  try {
    const res = await getSubtitleList(videoId)
    videoInfo.value = res.data.video
    tableData.value = res.data.list
  } catch (error) {
    console.error('获取字幕列表失败：', error)
  } finally {
    loading.value = false
  }
}

const handleBack = () => {
  router.push('/videos')
}

const handleAdd = () => {
  uploadForm.language = ''
  uploadForm.file = null
  selectedFile.value = null
  if (uploadRef.value) {
    uploadRef.value.clearFiles()
  }
  uploadDialogVisible.value = true
}

const beforeUpload = (file) => {
  const allowedExts = ['.vtt', '.srt']
  const fileName = file.name.toLowerCase()
  const isValidExt = allowedExts.some(ext => fileName.endsWith(ext))
  if (!isValidExt) {
    ElMessage.error('仅支持 .vtt 和 .srt 格式的字幕文件')
    return false
  }
  const maxSize = 2 * 1024 * 1024
  if (file.size > maxSize) {
    ElMessage.error('文件大小不能超过 2MB')
    return false
  }
  return true
}

const handleFileChange = (file) => {
  if (beforeUpload(file.raw)) {
    selectedFile.value = file.raw
    uploadForm.file = file.raw
  }
}

const handleFileExceed = () => {
  ElMessage.warning('只能上传一个字幕文件')
}

const handleUploadDialogClosed = () => {
  if (uploadFormRef.value) {
    uploadFormRef.value.resetFields()
  }
  selectedFile.value = null
  uploadForm.file = null
  if (uploadRef.value) {
    uploadRef.value.clearFiles()
  }
}

const handleUploadSubmit = async () => {
  if (!uploadFormRef.value) return

  await uploadFormRef.value.validate(async (valid) => {
    if (!valid) return
    if (!selectedFile.value) {
      ElMessage.warning('请选择字幕文件')
      return
    }

    submitLoading.value = true
    try {
      const res = await uploadSubtitle({
        video_id: route.params.id,
        language: uploadForm.language,
        file: selectedFile.value
      })
      ElMessage.success('上传成功')
      uploadDialogVisible.value = false

      if (res.data && res.data.preview) {
        previewTitle.value = `${getLanguageLabel(uploadForm.language)}字幕 - 上传预览`
        previewContent.value = res.data.preview
        previewDialogVisible.value = true
      }

      await new Promise(resolve => setTimeout(resolve, 300))
      await fetchData()
    } catch (error) {
      console.error('上传失败：', error)
    } finally {
      submitLoading.value = false
    }
  })
}

const handlePreview = async (row) => {
  try {
    loading.value = true
    const res = await getSubtitlePreview(row.id)
    previewTitle.value = `${getLanguageLabel(row.language)}字幕预览 - ${row.file_name || ''}`
    previewContent.value = res.data.preview || '（文件为空）'
    previewDialogVisible.value = true
  } catch (error) {
    console.error('获取预览失败：', error)
    ElMessage.error('获取预览失败')
  } finally {
    loading.value = false
  }
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status == 1 ? 0 : 1
  const action = newStatus == 1 ? '启用' : '禁用'

  try {
    await ElMessageBox.confirm(`确定要${action}该字幕吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await updateSubtitleStatus(row.id, newStatus)
    ElMessage.success(`${action}成功`)
    await fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error(`${action}失败：`, error)
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该字幕吗？删除后将无法恢复！', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'error'
    })

    await deleteSubtitle(row.id)
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
.video-subtitles :deep(.el-card) {
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

.video-subtitles :deep(.el-table) {
  border-radius: 8px;
}

.video-subtitles :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.video-subtitles :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.video-subtitles :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.format-tag {
  display: inline-block;
  padding: 2px 8px;
  background: #eef2ff;
  color: #6366f1;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  font-family: monospace;
}

.file-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #6366f1;
  text-decoration: none;
  font-size: 13px;
}

.file-link:hover {
  color: #4f46e5;
  text-decoration: underline;
}

.selected-file-info {
  margin-top: 10px;
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #475569;
}

.selected-file-info .file-size {
  color: #94a3b8;
}

.preview-content {
  max-height: 500px;
  overflow: auto;
  background: #0f172a;
  border-radius: 8px;
  padding: 16px;
}

.preview-content pre {
  margin: 0;
  color: #e2e8f0;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-all;
  font-family: 'Consolas', 'Monaco', monospace;
}

.video-subtitles :deep(.el-dialog) {
  border-radius: 12px;
}

.video-subtitles :deep(.el-upload-dragger) {
  padding: 24px;
}
</style>
