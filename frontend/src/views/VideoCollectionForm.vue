<template>
  <div class="collection-form">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>{{ isEdit ? '编辑专题合集' : '新增专题合集' }}</h3>
        </div>
      </template>

      <el-form ref="formRef" :model="form" :rules="rules" label-width="120px" style="max-width: 700px">
        <el-form-item label="合集标题" prop="title">
          <el-input
            v-model="form.title"
            placeholder="请输入合集标题（1-200个字符）"
            maxlength="200"
            show-word-limit
            clearable
          />
        </el-form-item>

        <el-form-item label="合集封面" prop="cover_url">
          <div class="cover-selector">
            <div class="cover-preview">
              <el-upload
                class="cover-uploader"
                :action="uploadAction"
                :headers="uploadHeaders"
                :show-file-list="false"
                :on-success="handleUploadSuccess"
                :on-error="handleUploadError"
                :before-upload="beforeUpload"
                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
              >
                <img v-if="form.cover_url" :src="getCoverUrl(form.cover_url)" class="cover-image" />
                <el-icon v-else class="cover-uploader-icon"><Plus /></el-icon>
              </el-upload>
            </div>
            <div class="cover-actions">
              <div class="action-tip">选择封面方式：</div>
              <el-upload
                class="action-upload"
                :action="uploadAction"
                :headers="uploadHeaders"
                :show-file-list="false"
                :on-success="handleUploadSuccess"
                :on-error="handleUploadError"
                :before-upload="beforeUpload"
                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
              >
                <el-button type="primary">
                  <el-icon><Upload /></el-icon>
                  本地上传
                </el-button>
              </el-upload>
              <el-button type="success" @click="openMediaPicker">
                <el-icon><Picture /></el-icon>
                从媒资库选择
              </el-button>
              <el-button v-if="form.cover_url" type="danger" text @click="clearCover">
                清除封面
              </el-button>
            </div>
          </div>
          <div class="upload-tip">支持 JPG、PNG、GIF、WebP 格式，文件大小不超过 5MB</div>
        </el-form-item>

        <el-form-item label="合集描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="4"
            placeholder="请输入合集描述（选填，最多1000个字符）"
            maxlength="1000"
            show-word-limit
            clearable
          />
        </el-form-item>

        <el-form-item label="排序值" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            :max="9999"
            placeholder="数值越大越靠前"
          />
          <span class="form-tip">数值越大，排序越靠前</span>
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status" size="large">
            <el-radio :label="1" border>上架</el-radio>
            <el-radio :label="0" border>下架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="videos-card">
      <template #header>
        <div class="card-header">
          <h3>合集影片
            <el-tag type="info" size="small" style="margin-left: 8px;">
              已选 {{ selectedVideos.length }} 部
            </el-tag>
          </h3>
          <el-button type="primary" @click="openVideoPicker">
            <el-icon><Plus /></el-icon>
            添加影片
          </el-button>
        </div>
      </template>

      <div v-if="selectedVideos.length === 0" class="empty-tip">
        <el-empty description="暂无影片，请点击上方按钮添加" />
      </div>

      <div v-else class="video-sort-list">
        <div
          v-for="(video, index) in selectedVideos"
          :key="video.id"
          class="video-sort-item"
          :class="{ 'dragging': dragIndex === index, 'drag-over': dragOverIndex === index }"
          draggable="true"
          @dragstart="handleDragStart(index)"
          @dragend="handleDragEnd"
          @dragover.prevent="handleDragOver(index)"
          @dragleave="handleDragLeave"
          @drop="handleDrop(index)"
        >
          <div class="drag-handle">
            <el-icon><Rank /></el-icon>
          </div>
          <div class="video-index">{{ index + 1 }}</div>
          <div class="video-cover">
            <img :src="getCoverUrl(video.cover_url)" :alt="video.title" @error="handleImageError" />
          </div>
          <div class="video-info">
            <div class="video-title">{{ video.title }}</div>
            <div class="video-status">
              <el-tag :type="video.status == 1 ? 'success' : 'info'" size="small">
                {{ video.status == 1 ? '上架' : '下架' }}
              </el-tag>
            </div>
          </div>
          <div class="video-actions">
            <el-button
              v-if="index > 0"
              size="small"
              text
              @click="moveVideo(index, -1)"
            >
              <el-icon><Top /></el-icon>
            </el-button>
            <el-button
              v-if="index < selectedVideos.length - 1"
              size="small"
              text
              @click="moveVideo(index, 1)"
            >
              <el-icon><Bottom /></el-icon>
            </el-button>
            <el-button size="small" type="danger" text @click="removeVideo(index)">
              <el-icon><Delete /></el-icon>
            </el-button>
          </div>
        </div>
      </div>
    </el-card>

    <div class="form-footer">
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        {{ isEdit ? '保存' : '提交' }}
      </el-button>
      <el-button @click="handleCancel">取消</el-button>
    </div>

    <el-dialog
      v-model="videoPickerVisible"
      title="选择影片"
      width="900px"
      class="video-picker-dialog"
      append-to-body
      destroy-on-close
    >
      <div class="video-picker">
        <div class="video-picker-search">
          <el-input
            v-model="videoKeyword"
            placeholder="按影片标题搜索"
            clearable
            style="width: 280px"
            @keyup.enter="fetchPickerVideos"
            @clear="fetchPickerVideos"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-select
            v-model="videoStatusFilter"
            placeholder="状态"
            clearable
            style="width: 150px"
            @change="fetchPickerVideos"
          >
            <el-option label="上架" value="1" />
            <el-option label="下架" value="0" />
          </el-select>
          <el-button type="primary" @click="fetchPickerVideos">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
        </div>

        <div v-loading="videoPickerLoading" class="video-picker-list">
          <div
            v-for="video in pickerVideoList"
            :key="video.id"
            class="picker-video-item"
            :class="{ selected: isVideoSelected(video.id), disabled: isVideoSelected(video.id) }"
            @click="toggleVideoSelection(video)"
          >
            <div class="picker-checkbox">
              <el-icon v-if="isVideoSelected(video.id)"><CircleCheckFilled /></el-icon>
              <span v-else class="empty-circle"></span>
            </div>
            <div class="picker-cover">
              <img :src="getCoverUrl(video.cover_url)" :alt="video.title" @error="handleImageError" />
            </div>
            <div class="picker-info">
              <div class="picker-title">{{ video.title }}</div>
              <div class="picker-desc">{{ video.description || '暂无描述' }}</div>
              <div class="picker-status">
                <el-tag :type="video.status == 1 ? 'success' : 'info'" size="small">
                  {{ video.status == 1 ? '上架' : '下架' }}
                </el-tag>
              </div>
            </div>
          </div>
          <el-empty v-if="!videoPickerLoading && pickerVideoList.length === 0" description="暂无影片" />
        </div>

        <div class="video-picker-pagination" v-if="pickerVideoTotal > 0">
          <el-pagination
            v-model:current-page="pickerVideoPage"
            v-model:page-size="pickerVideoPageSize"
            :page-sizes="[12, 24, 48]"
            :total="pickerVideoTotal"
            layout="total, sizes, prev, pager, next, jumper"
            background
            small
            @size-change="fetchPickerVideos"
            @current-change="fetchPickerVideos"
          />
        </div>
      </div>

      <template #footer>
        <div class="picker-footer">
          <span>已选择 {{ tempSelectedVideos.length }} 部影片</span>
          <div>
            <el-button @click="videoPickerVisible = false">取消</el-button>
            <el-button type="primary" @click="confirmVideoSelection">
              确定选择
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <el-dialog
      v-model="mediaPickerVisible"
      title="从媒资库选择封面"
      width="800px"
      class="media-picker-dialog"
      append-to-body
      destroy-on-close
    >
      <div class="media-picker">
        <div class="media-picker-search">
          <el-input
            v-model="mediaKeyword"
            placeholder="按文件名搜索"
            clearable
            style="width: 280px"
            @keyup.enter="fetchMediaList"
            @clear="fetchMediaList"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-button type="primary" @click="fetchMediaList">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
        </div>

        <div v-loading="mediaLoading" class="media-picker-grid">
          <div
            v-for="item in mediaList"
            :key="item.id"
            class="media-picker-item"
            :class="{ active: selectedMediaId === item.id }"
            @click="selectMedia(item)"
          >
            <div class="media-picker-thumb">
              <img :src="getCoverUrl(item.file_path)" :alt="item.original_name" />
            </div>
            <div class="media-picker-name" :title="item.original_name">{{ item.original_name }}</div>
            <div class="media-picker-size">{{ formatSize(item.size_bytes) }}</div>
          </div>
          <el-empty v-if="!mediaLoading && mediaList.length === 0" description="暂无图片资源" />
        </div>

        <div class="media-picker-pagination" v-if="mediaTotal > 0">
          <el-pagination
            v-model:current-page="mediaPage"
            v-model:page-size="mediaPageSize"
            :page-sizes="[12, 24, 48]"
            :total="mediaTotal"
            layout="total, sizes, prev, pager, next, jumper"
            background
            small
            @size-change="fetchMediaList"
            @current-change="fetchMediaList"
          />
        </div>
      </div>

      <template #footer>
        <el-button @click="mediaPickerVisible = false">取消</el-button>
        <el-button type="primary" :disabled="!selectedMediaId" @click="confirmSelectMedia">
          确定选择
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Plus, Upload, Picture, Search, Rank, Top, Bottom, Delete,
  CircleCheckFilled
} from '@element-plus/icons-vue'
import {
  getCollectionDetail, createCollection, updateCollection,
  getVideoList, getMediaList
} from '../api'

const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)

const selectedVideos = ref([])
const dragIndex = ref(-1)
const dragOverIndex = ref(-1)

const videoPickerVisible = ref(false)
const videoPickerLoading = ref(false)
const videoKeyword = ref('')
const videoStatusFilter = ref('')
const pickerVideoPage = ref(1)
const pickerVideoPageSize = ref(12)
const pickerVideoTotal = ref(0)
const pickerVideoList = ref([])
const tempSelectedVideos = ref([])

const mediaPickerVisible = ref(false)
const mediaLoading = ref(false)
const mediaKeyword = ref('')
const mediaPage = ref(1)
const mediaPageSize = ref(24)
const mediaTotal = ref(0)
const mediaList = ref([])
const selectedMediaId = ref(null)
const selectedMedia = ref(null)

const uploadAction = computed(() => {
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}/api/upload/media` : '/api/upload/media'
})

const uploadHeaders = computed(() => {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
})

const form = reactive({
  title: '',
  cover_url: '',
  description: '',
  sort_order: 0,
  status: 1,
})

const rules = {
  title: [
    { required: true, message: '请输入合集标题', trigger: 'blur' },
    { min: 1, max: 200, message: '标题长度必须在1-200个字符之间', trigger: 'blur' },
  ],
  cover_url: [{ required: true, message: '请上传或选择合集封面', trigger: 'change' }],
  description: [{ max: 1000, message: '描述最多1000个字符', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const getCoverUrl = (url) => {
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
    form.cover_url = response.data.url
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

const handleUploadError = (error) => {
  console.error('上传失败：', error)
  ElMessage.error('上传失败，请重试')
}

const clearCover = () => {
  form.cover_url = ''
}

const openMediaPicker = () => {
  selectedMediaId.value = null
  selectedMedia.value = null
  mediaKeyword.value = ''
  mediaPage.value = 1
  mediaPickerVisible.value = true
  fetchMediaList()
}

const fetchMediaList = async () => {
  mediaLoading.value = true
  try {
    const res = await getMediaList({
      keyword: mediaKeyword.value,
      page: mediaPage.value,
      page_size: mediaPageSize.value
    })
    mediaList.value = res.data.list
    mediaTotal.value = res.data.total
  } catch (error) {
    console.error('获取媒资列表失败：', error)
    ElMessage.error('获取媒资列表失败')
  } finally {
    mediaLoading.value = false
  }
}

const selectMedia = (item) => {
  selectedMediaId.value = item.id
  selectedMedia.value = item
}

const confirmSelectMedia = () => {
  if (selectedMedia.value) {
    form.cover_url = selectedMedia.value.file_path
    ElMessage.success('选择成功')
  }
  mediaPickerVisible.value = false
}

const handleDragStart = (index) => {
  dragIndex.value = index
}

const handleDragEnd = () => {
  dragIndex.value = -1
  dragOverIndex.value = -1
}

const handleDragOver = (index) => {
  if (dragIndex.value === -1 || dragIndex.value === index) return
  dragOverIndex.value = index
}

const handleDragLeave = () => {
  dragOverIndex.value = -1
}

const handleDrop = (index) => {
  if (dragIndex.value === -1 || dragIndex.value === index) return
  const fromIndex = dragIndex.value
  const toIndex = index
  const items = [...selectedVideos.value]
  const [removed] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, removed)
  selectedVideos.value = items
  dragIndex.value = -1
  dragOverIndex.value = -1
}

const moveVideo = (index, direction) => {
  const newIndex = index + direction
  if (newIndex < 0 || newIndex >= selectedVideos.value.length) return
  const items = [...selectedVideos.value]
  const [removed] = items.splice(index, 1)
  items.splice(newIndex, 0, removed)
  selectedVideos.value = items
}

const removeVideo = (index) => {
  selectedVideos.value.splice(index, 1)
}

const openVideoPicker = () => {
  tempSelectedVideos.value = [...selectedVideos.value]
  videoKeyword.value = ''
  videoStatusFilter.value = ''
  pickerVideoPage.value = 1
  videoPickerVisible.value = true
  fetchPickerVideos()
}

const fetchPickerVideos = async () => {
  videoPickerLoading.value = true
  try {
    const res = await getVideoList({
      keyword: videoKeyword.value,
      status: videoStatusFilter.value,
      page: pickerVideoPage.value,
      page_size: pickerVideoPageSize.value
    })
    pickerVideoList.value = res.data.list
    pickerVideoTotal.value = res.data.total
  } catch (error) {
    console.error('获取影片列表失败：', error)
    ElMessage.error('获取影片列表失败')
  } finally {
    videoPickerLoading.value = false
  }
}

const isVideoSelected = (videoId) => {
  return tempSelectedVideos.value.some(v => v.id === videoId)
}

const toggleVideoSelection = (video) => {
  const index = tempSelectedVideos.value.findIndex(v => v.id === video.id)
  if (index > -1) {
    tempSelectedVideos.value.splice(index, 1)
  } else {
    tempSelectedVideos.value.push(video)
  }
}

const confirmVideoSelection = () => {
  selectedVideos.value = [...tempSelectedVideos.value]
  videoPickerVisible.value = false
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
    const data = res.data
    data.status = parseInt(data.status)
    data.sort_order = parseInt(data.sort_order)
    Object.assign(form, data)
    selectedVideos.value = data.videos || []
  } catch (error) {
    console.error('获取详情失败：', error)
    ElMessage.error('获取合集信息失败')
    router.back()
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const submitData = {
        ...form,
        video_ids: selectedVideos.value.map(v => v.id)
      }
      if (isEdit.value) {
        await updateCollection(route.params.id, submitData)
        ElMessage.success('更新成功')
      } else {
        await createCollection(submitData)
        ElMessage.success('添加成功')
      }
      router.push('/collections')
    } catch (error) {
      console.error('提交失败：', error)
    } finally {
      loading.value = false
    }
  })
}

const handleCancel = () => {
  router.back()
}

onMounted(() => {
  isEdit.value = !!route.params.id
  if (isEdit.value) {
    fetchDetail()
  }
})
</script>

<style scoped>
.collection-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.collection-form :deep(.el-card) {
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

.cover-selector {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.cover-preview {
  flex-shrink: 0;
}

.cover-uploader :deep(.el-upload) {
  border: 2px dashed #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.2s;
  width: 280px;
  height: 157px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
}

.cover-uploader :deep(.el-upload:hover) {
  border-color: #6366f1;
  background: #f0f0ff;
}

.cover-uploader-icon {
  font-size: 28px;
  color: #94a3b8;
}

.cover-image {
  width: 280px;
  height: 157px;
  display: block;
  object-fit: cover;
}

.cover-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.action-tip {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.upload-tip {
  margin-top: 8px;
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.5;
}

.form-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #94a3b8;
}

.collection-form :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.collection-form :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.videos-card {
  margin-top: 0;
}

.empty-tip {
  padding: 40px 0;
}

.video-sort-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.video-sort-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
}

.video-sort-item.dragging {
  opacity: 0.5;
  background: #e0e7ff;
}

.video-sort-item.drag-over {
  border-color: #6366f1;
  background: #eef2ff;
}

.drag-handle {
  color: #94a3b8;
  cursor: grab;
  padding: 4px;
}

.drag-handle:active {
  cursor: grabbing;
}

.video-index {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #6366f1;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

.video-cover {
  width: 80px;
  height: 45px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
}

.video-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.video-info {
  flex: 1;
  min-width: 0;
}

.video-title {
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.video-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.video-picker {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.video-picker-search {
  display: flex;
  gap: 10px;
  align-items: center;
}

.video-picker-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  max-height: 450px;
  overflow-y: auto;
  padding: 4px;
}

.picker-video-item {
  display: flex;
  gap: 12px;
  padding: 10px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
}

.picker-video-item:hover {
  border-color: #6366f1;
}

.picker-video-item.selected {
  border-color: #6366f1;
  background: #f0f0ff;
}

.picker-video-item.disabled {
  cursor: default;
}

.picker-checkbox {
  flex-shrink: 0;
  color: #94a3b8;
  display: flex;
  align-items: center;
}

.picker-checkbox .empty-circle {
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  border-radius: 50%;
  box-sizing: border-box;
}

.picker-video-item.selected .picker-checkbox {
  color: #6366f1;
}

.picker-cover {
  width: 80px;
  height: 45px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
}

.picker-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.picker-info {
  flex: 1;
  min-width: 0;
}

.picker-title {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.picker-desc {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.video-picker-pagination {
  display: flex;
  justify-content: center;
}

.picker-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.picker-footer span {
  font-size: 14px;
  color: #64748b;
}

.media-picker {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.media-picker-search {
  display: flex;
  gap: 10px;
  align-items: center;
}

.media-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  min-height: 300px;
  max-height: 400px;
  overflow-y: auto;
  padding: 4px;
}

.media-picker-item {
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
}

.media-picker-item:hover {
  border-color: #6366f1;
  transform: translateY(-1px);
}

.media-picker-item.active {
  border-color: #6366f1;
  background: #f0f0ff;
}

.media-picker-thumb {
  width: 100%;
  padding-top: 56.25%;
  position: relative;
  overflow: hidden;
  border-radius: 6px;
  background: #f1f5f9;
  margin-bottom: 6px;
}

.media-picker-thumb img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-picker-name {
  font-size: 12px;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.media-picker-size {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 2px;
}

.media-picker-pagination {
  display: flex;
  justify-content: center;
}

.form-footer {
  display: flex;
  gap: 12px;
  justify-content: center;
  padding: 20px 0;
}
</style>
