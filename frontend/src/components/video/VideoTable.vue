<template>
  <el-table :data="data" border stripe v-loading="loading" :row-class-name="getRowClassName">
    <el-table-column prop="id" label="ID" width="80" />
    <el-table-column prop="title" label="影片标题" min-width="200">
      <template #default="{ row }">
        <div class="title-cell">
          <span>{{ row.title }}</span>
          <el-tag v-if="!row.content_rating_code" type="warning" size="small" effect="light" class="unrated-tag">未分级</el-tag>
        </div>
      </template>
    </el-table-column>
    <el-table-column prop="cover_url" label="封面" width="120">
      <template #default="{ row }">
        <div v-if="row.cover_url" class="cover-wrapper" @click="$emit('preview', getCoverUrl(row.cover_url))">
          <img
            :src="getCoverUrl(row.cover_url)"
            :alt="row.title"
            class="cover-image"
            :class="{ 'cover-unrated': !row.content_rating_code }"
            loading="lazy"
            @error="handleImageError"
          />
        </div>
        <span v-else class="cover-empty">暂无</span>
      </template>
    </el-table-column>
    <el-table-column label="内容分级" width="160">
      <template #default="{ row }">
        <div v-if="row.content_rating_code" class="rating-cell">
          <span
            class="rating-tag"
            :style="{ backgroundColor: row.content_rating_color }"
          >
            {{ row.content_rating_label }}
          </span>
          <span class="rating-code">{{ row.content_rating_code }}</span>
        </div>
        <span v-else class="rating-unassigned">未设置</span>
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
    <el-table-column label="操作" width="380" fixed="right">
      <template #default="{ row }">
        <el-button size="small" @click="$emit('edit', row)">编辑</el-button>
        <el-button size="small" @click="$emit('sources', row)">播放源</el-button>
        <el-button size="small" @click="$emit('subtitles', row)">字幕管理</el-button>
        <el-button
          size="small"
          :type="row.status == 1 ? 'warning' : 'success'"
          @click="$emit('toggle-status', row)"
        >
          {{ row.status == 1 ? '下架' : '上架' }}
        </el-button>
        <el-button size="small" type="danger" @click="$emit('delete', row)">删除</el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script setup>
import { getFullUrl } from '../../utils/url'

defineProps({
  data: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  getRowClassName: {
    type: Function,
    default: () => ''
  }
})

defineEmits(['edit', 'sources', 'subtitles', 'toggle-status', 'delete', 'preview'])

const getCoverUrl = getFullUrl

const handleImageError = (e) => {
  e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3E加载失败%3C/text%3E%3C/svg%3E'
}
</script>

<style scoped>
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

.title-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.unrated-tag {
  flex-shrink: 0;
}

.cover-unrated {
  filter: grayscale(80%) opacity(0.7);
}

.rating-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.rating-tag {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  color: #fff;
  font-size: 12px;
  font-weight: 500;
  line-height: 1.4;
}

.rating-code {
  font-size: 12px;
  color: #94a3b8;
  font-family: monospace;
}

.rating-unassigned {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 12px;
}
</style>
