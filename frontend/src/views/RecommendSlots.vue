<template>
  <div class="recommend-slots" v-loading="pageLoading">
    <div class="page-header">
      <h2 class="page-title">推荐位编排</h2>
      <div class="header-actions">
        <el-button @click="refreshJsonPreview">
          <el-icon><Refresh /></el-icon>
          刷新预览
        </el-button>
        <el-button type="primary" @click="openSlotDialog(null)">
          <el-icon><Plus /></el-icon>
          新增槽位
        </el-button>
      </div>
    </div>

    <el-row :gutter="20">
      <el-col :xs="24" :lg="16">
        <div v-if="slotList.length === 0" class="empty-tip">
          <el-empty description="暂无推荐槽位，请点击右上角新增" />
        </div>

        <el-collapse v-model="activeSlots" class="slot-panels">
          <el-collapse-item
            v-for="slot in slotList"
            :key="slot.id"
            :name="String(slot.id)"
          >
            <template #title>
              <div class="panel-title">
                <div class="title-main">
                  <span class="slot-title">{{ slot.title }}</span>
                  <el-tag type="info" size="small" class="slot-key">{{ slot.slot_key }}</el-tag>
                  <el-tag
                    :type="slot.status == 1 ? 'success' : 'info'"
                    size="small"
                    class="slot-status"
                  >
                    {{ slot.status == 1 ? '启用' : '禁用' }}
                  </el-tag>
                </div>
                <div class="title-meta">
                  <el-tag size="small" type="warning">
                    {{ slot.item_count }} / {{ slot.max_items }}
                  </el-tag>
                </div>
              </div>
            </template>

            <div class="panel-toolbar">
              <div class="toolbar-left">
                <el-button
                  type="primary"
                  size="small"
                  :disabled="slot.item_count >= slot.max_items"
                  @click="openVideoPicker(slot)"
                >
                  <el-icon><Plus /></el-icon>
                  添加影片
                </el-button>
                <el-tooltip content="仅可选择已上架影片">
                  <el-icon class="help-icon"><QuestionFilled /></el-icon>
                </el-tooltip>
              </div>
              <div class="toolbar-right">
                <el-button size="small" @click="openSlotDialog(slot)">
                  <el-icon><Edit /></el-icon>
                  编辑槽位
                </el-button>
                <el-button
                  size="small"
                  :type="slot.status == 1 ? 'warning' : 'success'"
                  @click="handleToggleSlotStatus(slot)"
                >
                  {{ slot.status == 1 ? '禁用' : '启用' }}
                </el-button>
                <el-button size="small" type="danger" @click="handleDeleteSlot(slot)">
                  <el-icon><Delete /></el-icon>
                  删除
                </el-button>
              </div>
            </div>

            <div v-if="slot.items && slot.items.length === 0" class="empty-items">
              <el-empty description="暂无推荐影片，点击上方按钮添加" :image-size="80" />
            </div>

            <div v-else class="video-sort-list">
              <div
                v-for="(video, index) in slot.items"
                :key="video.id"
                class="video-sort-item"
                :class="{
                  'dragging': dragState.slotId === slot.id && dragState.index === index,
                  'drag-over': dragState.slotId === slot.id && dragState.overIndex === index
                }"
                draggable="true"
                @dragstart="handleDragStart(slot.id, index)"
                @dragend="handleDragEnd"
                @dragover.prevent="handleDragOver(slot.id, index)"
                @dragleave="handleDragLeave"
                @drop="handleDrop(slot, index)"
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
                    <el-tag :type="video.status == 1 ? 'success' : 'warning'" size="small">
                      {{ video.status == 1 ? '上架' : '已下架' }}
                    </el-tag>
                    <span v-if="video.status != 1" class="offline-warn">该影片已下架，建议移除</span>
                  </div>
                </div>
                <div class="video-actions">
                  <el-button
                    v-if="index > 0"
                    size="small"
                    text
                    @click="moveVideo(slot, index, -1)"
                  >
                    <el-icon><Top /></el-icon>
                  </el-button>
                  <el-button
                    v-if="index < slot.items.length - 1"
                    size="small"
                    text
                    @click="moveVideo(slot, index, 1)"
                  >
                    <el-icon><Bottom /></el-icon>
                  </el-button>
                  <el-button size="small" type="danger" text @click="handleRemoveVideo(slot, video)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
              </div>
            </div>
          </el-collapse-item>
        </el-collapse>
      </el-col>

      <el-col :xs="24" :lg="8">
        <el-card class="json-preview-card" shadow="never">
          <template #header>
            <div class="json-header">
              <span>JSON 预览</span>
              <el-tag size="small" type="info">客户端展示配置</el-tag>
            </div>
          </template>
          <div class="json-content">
            <pre><code>{{ jsonPreviewText }}</code></pre>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog
      v-model="slotDialogVisible"
      :title="editingSlot ? '编辑槽位' : '新增槽位'"
      width="560px"
      append-to-body
      destroy-on-close
    >
      <el-form ref="slotFormRef" :model="slotForm" :rules="slotRules" label-width="100px">
        <el-form-item label="槽位标识" prop="slot_key">
          <el-input
            v-model="slotForm.slot_key"
            placeholder="如 home_hot，英文下划线命名"
            :disabled="!!editingSlot"
          />
          <div class="form-tip">槽位标识创建后不可修改，需与客户端约定</div>
        </el-form-item>
        <el-form-item label="显示标题" prop="title">
          <el-input v-model="slotForm.title" placeholder="如 热门推荐" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="最大条目" prop="max_items">
          <el-input-number v-model="slotForm.max_items" :min="1" :max="100" />
          <span class="form-tip">该槽位最多展示的影片数</span>
        </el-form-item>
        <el-form-item label="排序值" prop="sort_order">
          <el-input-number v-model="slotForm.sort_order" :min="0" :max="9999" />
          <span class="form-tip">数值越大，槽位顺序越靠前</span>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="slotForm.status">
            <el-radio :label="1" border>启用</el-radio>
            <el-radio :label="0" border>禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="slotDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="slotSubmitting" @click="handleSubmitSlot">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="videoPickerVisible"
      title="选择影片（仅显示已上架）"
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
                <el-tag type="success" size="small">上架</el-tag>
              </div>
            </div>
          </div>
          <el-empty v-if="!videoPickerLoading && pickerVideoList.length === 0" description="暂无可选影片" />
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
          <span>
            已选择 {{ tempSelectedVideos.length }} 部
            <span v-if="currentSlot" class="picker-limit">
              （槽位上限 {{ currentSlot.max_items }}，还可添加 {{ Math.max(0, currentSlot.max_items - (currentSlot.item_count || 0)) }}）
            </span>
          </span>
          <div>
            <el-button @click="videoPickerVisible = false">取消</el-button>
            <el-button type="primary" :disabled="tempSelectedVideos.length === 0" @click="confirmVideoSelection">
              确定选择
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Plus, Edit, Delete, Refresh, Rank, Top, Bottom, Search,
  CircleCheckFilled, QuestionFilled
} from '@element-plus/icons-vue'
import {
  getRecommendSlotList, getRecommendSlotDetail, getRecommendSlotsPreview,
  createRecommendSlot, updateRecommendSlot, deleteRecommendSlot,
  addVideosToRecommendSlot, removeVideoFromRecommendSlot, updateRecommendItemSort,
  getVideoList
} from '../api'

const pageLoading = ref(false)
const slotList = ref([])
const activeSlots = ref([])

const dragState = reactive({
  slotId: null,
  index: -1,
  overIndex: -1
})

const jsonPreviewData = ref([])
const jsonPreviewText = computed(() => {
  return JSON.stringify(jsonPreviewData.value, null, 2)
})

const slotDialogVisible = ref(false)
const slotFormRef = ref(null)
const slotSubmitting = ref(false)
const editingSlot = ref(null)
const slotForm = reactive({
  slot_key: '',
  title: '',
  max_items: 10,
  sort_order: 0,
  status: 1
})

const slotRules = {
  slot_key: [
    { required: true, message: '请输入槽位标识', trigger: 'blur' },
    { pattern: /^[a-z][a-z0-9_]*$/, message: '标识需以小写字母开头，仅包含小写字母、数字和下划线', trigger: 'blur' }
  ],
  title: [
    { required: true, message: '请输入显示标题', trigger: 'blur' },
    { min: 1, max: 100, message: '标题长度 1-100 字符', trigger: 'blur' }
  ],
  max_items: [
    { required: true, message: '请输入最大条目数', trigger: 'blur' }
  ],
  status: [
    { required: true, message: '请选择状态', trigger: 'change' }
  ]
}

const videoPickerVisible = ref(false)
const videoPickerLoading = ref(false)
const videoKeyword = ref('')
const pickerVideoPage = ref(1)
const pickerVideoPageSize = ref(12)
const pickerVideoTotal = ref(0)
const pickerVideoList = ref([])
const tempSelectedVideos = ref([])
const currentSlot = ref(null)

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

const fetchSlotList = async () => {
  pageLoading.value = true
  try {
    const res = await getRecommendSlotList()
    const list = res.data || []

    const detailPromises = list.map(async (slot) => {
      try {
        const detailRes = await getRecommendSlotDetail(slot.id)
        return detailRes.data
      } catch (e) {
        return slot
      }
    })

    slotList.value = await Promise.all(detailPromises)
    if (activeSlots.value.length === 0 && slotList.value.length > 0) {
      activeSlots.value = slotList.value.map(s => String(s.id))
    }
  } catch (error) {
    console.error('获取槽位列表失败：', error)
    ElMessage.error('获取推荐位列表失败')
  } finally {
    pageLoading.value = false
  }
}

const refreshJsonPreview = async () => {
  try {
    const res = await getRecommendSlotsPreview()
    jsonPreviewData.value = res.data || []
    ElMessage.success('预览已刷新')
  } catch (error) {
    console.error('刷新预览失败：', error)
  }
}

const openSlotDialog = (slot) => {
  editingSlot.value = slot
  if (slot) {
    Object.assign(slotForm, {
      slot_key: slot.slot_key,
      title: slot.title,
      max_items: slot.max_items,
      sort_order: slot.sort_order,
      status: slot.status
    })
  } else {
    Object.assign(slotForm, {
      slot_key: '',
      title: '',
      max_items: 10,
      sort_order: 0,
      status: 1
    })
  }
  slotDialogVisible.value = true
  nextTick(() => {
    if (slotFormRef.value) {
      slotFormRef.value.clearValidate()
    }
  })
}

const handleSubmitSlot = async () => {
  if (!slotFormRef.value) return
  await slotFormRef.value.validate(async (valid) => {
    if (!valid) return
    slotSubmitting.value = true
    try {
      if (editingSlot.value) {
        await updateRecommendSlot(editingSlot.value.id, slotForm)
        ElMessage.success('更新成功')
      } else {
        await createRecommendSlot(slotForm)
        ElMessage.success('新增成功')
      }
      slotDialogVisible.value = false
      await fetchSlotList()
      await refreshJsonPreview()
    } catch (error) {
      console.error('提交失败：', error)
    } finally {
      slotSubmitting.value = false
    }
  })
}

const handleToggleSlotStatus = async (slot) => {
  const newStatus = slot.status == 1 ? 0 : 1
  const action = newStatus == 1 ? '启用' : '禁用'
  try {
    await ElMessageBox.confirm(`确定要${action}该槽位吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await updateRecommendSlot(slot.id, {
      slot_key: slot.slot_key,
      title: slot.title,
      max_items: slot.max_items,
      sort_order: slot.sort_order,
      status: newStatus
    })
    ElMessage.success(`${action}成功`)
    await fetchSlotList()
    await refreshJsonPreview()
  } catch (error) {
    if (error !== 'cancel') {
      console.error(`${action}失败：`, error)
    }
  }
}

const handleDeleteSlot = async (slot) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除槽位「${slot.title}」吗？删除后所有推荐配置将丢失，无法恢复！`,
      '警告',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'error'
      }
    )
    await deleteRecommendSlot(slot.id)
    ElMessage.success('删除成功')
    activeSlots.value = activeSlots.value.filter(id => id !== String(slot.id))
    await fetchSlotList()
    await refreshJsonPreview()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

const openVideoPicker = (slot) => {
  currentSlot.value = slot
  videoKeyword.value = ''
  pickerVideoPage.value = 1
  tempSelectedVideos.value = []
  videoPickerVisible.value = true
  fetchPickerVideos()
}

const fetchPickerVideos = async () => {
  videoPickerLoading.value = true
  try {
    const res = await getVideoList({
      keyword: videoKeyword.value,
      status: '1',
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
  if (tempSelectedVideos.value.some(v => v.id === videoId)) return true
  if (currentSlot.value && currentSlot.value.items) {
    return currentSlot.value.items.some(v => v.id === videoId)
  }
  return false
}

const toggleVideoSelection = (video) => {
  const idx = tempSelectedVideos.value.findIndex(v => v.id === video.id)
  if (idx > -1) {
    tempSelectedVideos.value.splice(idx, 1)
  } else {
    if (currentSlot.value && currentSlot.value.items &&
        currentSlot.value.items.some(v => v.id === video.id)) {
      return
    }
    if (currentSlot.value) {
      const remaining = Math.max(0, currentSlot.value.max_items - (currentSlot.value.item_count || 0))
      if (tempSelectedVideos.value.length >= remaining) {
        ElMessage.warning(`超出槽位最大条目数限制，还可添加 ${remaining} 部`)
        return
      }
    }
    tempSelectedVideos.value.push(video)
  }
}

const confirmVideoSelection = async () => {
  if (!currentSlot.value || tempSelectedVideos.value.length === 0) return
  try {
    const videoIds = tempSelectedVideos.value.map(v => v.id)
    await addVideosToRecommendSlot(currentSlot.value.id, videoIds)
    ElMessage.success(`已添加 ${videoIds.length} 部影片`)
    videoPickerVisible.value = false
    await fetchSlotList()
    await refreshJsonPreview()
  } catch (error) {
    console.error('添加影片失败：', error)
  }
}

const handleRemoveVideo = async (slot, video) => {
  try {
    await ElMessageBox.confirm(
      `确定要从槽位移除影片「${video.title}」吗？`,
      '提示',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    await removeVideoFromRecommendSlot(slot.id, video.id)
    ElMessage.success('移除成功')
    await fetchSlotList()
    await refreshJsonPreview()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('移除失败：', error)
    }
  }
}

const handleDragStart = (slotId, index) => {
  dragState.slotId = slotId
  dragState.index = index
}

const handleDragEnd = () => {
  dragState.slotId = null
  dragState.index = -1
  dragState.overIndex = -1
}

const handleDragOver = (slotId, index) => {
  if (dragState.slotId !== slotId) return
  if (dragState.index === -1 || dragState.index === index) return
  dragState.overIndex = index
}

const handleDragLeave = () => {
  dragState.overIndex = -1
}

const handleDrop = async (slot, index) => {
  if (!dragState.slotId || dragState.slotId !== slot.id) return
  if (dragState.index === -1 || dragState.index === index) return

  const fromIndex = dragState.index
  const toIndex = index
  const items = [...slot.items]
  const [removed] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, removed)

  slot.items = items

  handleDragEnd()

  try {
    const videoOrders = items.map((v, i) => ({
      video_id: v.id,
      sort_order: items.length - i
    }))
    await updateRecommendItemSort(slot.id, videoOrders)
    await refreshJsonPreview()
  } catch (error) {
    console.error('更新排序失败：', error)
    ElMessage.error('更新排序失败')
    fetchSlotList()
  }
}

const moveVideo = async (slot, index, direction) => {
  const newIndex = index + direction
  if (newIndex < 0 || newIndex >= slot.items.length) return

  const items = [...slot.items]
  const [removed] = items.splice(index, 1)
  items.splice(newIndex, 0, removed)
  slot.items = items

  try {
    const videoOrders = items.map((v, i) => ({
      video_id: v.id,
      sort_order: items.length - i
    }))
    await updateRecommendItemSort(slot.id, videoOrders)
    await refreshJsonPreview()
  } catch (error) {
    console.error('更新排序失败：', error)
    ElMessage.error('更新排序失败')
    fetchSlotList()
  }
}

onMounted(async () => {
  await fetchSlotList()
  await refreshJsonPreview()
})
</script>

<style scoped>
.recommend-slots {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.page-title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.empty-tip {
  padding: 60px 0;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.slot-panels {
  border: none;
  background: transparent;
}

.slot-panels :deep(.el-collapse-item) {
  border: 1px solid #f0f0f0;
  border-radius: 12px;
  margin-bottom: 16px;
  background: #fff;
  overflow: hidden;
}

.slot-panels :deep(.el-collapse-item__header) {
  padding: 0 20px;
  height: 60px;
  line-height: 60px;
  border-bottom: none;
  background: #fff;
}

.slot-panels :deep(.el-collapse-item__wrap) {
  border-top: 1px solid #f0f0f0;
  background: #fff;
}

.slot-panels :deep(.el-collapse-item__content) {
  padding: 20px;
}

.panel-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: calc(100% - 40px);
}

.title-main {
  display: flex;
  align-items: center;
  gap: 10px;
}

.slot-title {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.slot-key {
  background: #f1f5f9;
  color: #64748b;
  border: none;
}

.slot-status {
  margin-left: 2px;
}

.panel-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px dashed #e2e8f0;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.toolbar-right {
  display: flex;
  gap: 8px;
}

.help-icon {
  color: #94a3b8;
  font-size: 18px;
}

.empty-items {
  padding: 30px 0;
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

.video-status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.offline-warn {
  font-size: 12px;
  color: #f59e0b;
}

.video-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.json-preview-card {
  position: sticky;
  top: 0;
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.json-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #1e293b;
}

.json-content {
  max-height: 600px;
  overflow-y: auto;
  background: #0f172a;
  border-radius: 8px;
  padding: 16px;
}

.json-content pre {
  margin: 0;
}

.json-content code {
  font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
  font-size: 12px;
  line-height: 1.6;
  color: #e2e8f0;
  white-space: pre-wrap;
  word-break: break-all;
}

.form-tip {
  margin-left: 8px;
  font-size: 12px;
  color: #94a3b8;
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

.picker-limit {
  color: #f59e0b;
  margin-left: 8px;
}

.recommend-slots :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.recommend-slots :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.recommend-slots :deep(.el-button--primary:disabled) {
  background: #a5b4fc;
  border-color: #a5b4fc;
}
</style>
