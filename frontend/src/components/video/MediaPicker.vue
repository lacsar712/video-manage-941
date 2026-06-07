<template>
  <el-dialog
    v-model="dialogVisible"
    title="从媒资库选择封面"
    width="800px"
    class="media-picker-dialog"
    append-to-body
    destroy-on-close
    @update:model-value="handleVisibleChange"
  >
    <div class="media-picker">
      <div class="media-picker-search">
        <el-input
          v-model="keyword"
          placeholder="按文件名搜索"
          clearable
          style="width: 280px"
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
      </div>

      <div v-loading="loading" class="media-picker-grid">
        <div
          v-for="item in list"
          :key="item.id"
          class="media-picker-item"
          :class="{ active: selectedId === item.id }"
          @click="selectItem(item)"
        >
          <div class="media-picker-thumb">
            <img :src="getCoverUrl(item.file_path)" :alt="item.original_name" />
          </div>
          <div class="media-picker-name" :title="item.original_name">{{ item.original_name }}</div>
          <div class="media-picker-size">{{ formatSize(item.size_bytes) }}</div>
        </div>
        <el-empty v-if="!loading && list.length === 0" description="暂无图片资源" />
      </div>

      <div class="media-picker-pagination" v-if="total > 0">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :page-sizes="[12, 24, 48]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          background
          small
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </div>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :disabled="!selectedId" @click="confirmSelect">
        确定选择
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { getMediaList } from '../../api'
import { getFullUrl, formatFileSize } from '../../utils/url'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'select'])

const dialogVisible = ref(props.modelValue)
const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const pageSize = ref(24)
const total = ref(0)
const list = ref([])
const selectedId = ref(null)
const selectedItem = ref(null)

const getCoverUrl = getFullUrl
const formatSize = formatFileSize

watch(() => props.modelValue, (val) => {
  dialogVisible.value = val
  if (val) {
    resetState()
    fetchList()
  }
})

watch(dialogVisible, (val) => {
  emit('update:modelValue', val)
})

const handleVisibleChange = (val) => {
  dialogVisible.value = val
}

const resetState = () => {
  selectedId.value = null
  selectedItem.value = null
  keyword.value = ''
  page.value = 1
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
  } finally {
    loading.value = false
  }
}

const selectItem = (item) => {
  selectedId.value = item.id
  selectedItem.value = item
}

const confirmSelect = () => {
  if (selectedItem.value) {
    emit('select', selectedItem.value)
  }
  dialogVisible.value = false
}
</script>

<style scoped>
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
</style>
